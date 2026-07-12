<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downloads real, royalty-free stock photos from the Pexels API for demo
 * catalog seeding and stores them under storage/app/public. Every download
 * is validated (MIME type + minimum byte size) and wrapped in its own
 * try/catch so one bad photo never aborts an entire import batch — callers
 * always get back only the paths that actually succeeded. Respects Pexels'
 * rate-limit headers: if a request comes back 429, it backs off and retries
 * once rather than failing the whole batch.
 */
class DemoImageImporter
{
    protected const ALLOWED_MIME_PREFIXES = ['image/jpeg', 'image/png', 'image/webp'];

    protected const MIN_BYTES = 4096;

    protected const MAX_PER_REQUEST = 80;

    public function __construct(protected ?string $apiKey = null)
    {
        $this->apiKey ??= config('services.pexels.key');
    }

    public function hasApiKey(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Fetch up to $count real stock photos matching $query and store them
     * under storage/app/public/{$directory}. Returns the relative storage
     * paths of the photos that were successfully downloaded and validated —
     * this can be shorter than $count if some downloads failed or Pexels
     * didn't have that many relevant results.
     *
     * @return array<int, string>
     */
    public function fetchAndStore(string $query, int $count, string $directory, string $filenamePrefix): array
    {
        if (! $this->hasApiKey()) {
            Log::warning('DemoImageImporter: no PEXELS_API_KEY configured, skipping import.');

            return [];
        }

        $count = max(1, min($count, self::MAX_PER_REQUEST));

        $response = $this->searchWithRateLimitHandling($query, $count);

        if (! $response || ! is_array($response['photos'] ?? null)) {
            Log::warning("DemoImageImporter: no usable results for query [{$query}]");

            return [];
        }

        $stored = [];

        foreach ($response['photos'] as $index => $photo) {
            $url = $photo['src']['large'] ?? $photo['src']['medium'] ?? null;

            if (! $url) {
                continue;
            }

            $path = $this->downloadOne($url, $directory, "{$filenamePrefix}-{$index}");

            if ($path) {
                $stored[] = $path;
            }
        }

        return $stored;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function searchWithRateLimitHandling(string $query, int $count): ?array
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['Authorization' => $this->apiKey])
                    ->get('https://api.pexels.com/v1/search', [
                        'query' => $query,
                        'per_page' => $count,
                        'orientation' => 'portrait',
                    ]);
            } catch (\Throwable $e) {
                Log::warning("DemoImageImporter: metadata request failed for query [{$query}]: {$e->getMessage()}");

                return null;
            }

            if ($response->status() === 429) {
                $resetIn = max(1, (int) $response->header('X-Ratelimit-Reset') - time());
                Log::warning("DemoImageImporter: rate limited on query [{$query}], waiting {$resetIn}s before retry.");
                sleep(min($resetIn, 30));

                continue;
            }

            if (! $response->successful()) {
                Log::warning("DemoImageImporter: unexpected metadata response for query [{$query}] (status {$response->status()})");

                return null;
            }

            return $response->json();
        }

        return null;
    }

    protected function downloadOne(string $url, string $directory, string $filenameBase): ?string
    {
        try {
            $response = Http::timeout(20)->retry(2, 500)->get($url);
        } catch (\Throwable $e) {
            Log::warning("DemoImageImporter: download failed for {$url}: {$e->getMessage()}");

            return null;
        }

        if (! $response->successful()) {
            Log::warning("DemoImageImporter: non-successful response ({$response->status()}) for {$url}");

            return null;
        }

        $contentType = (string) $response->header('Content-Type');

        if (! Str::startsWith($contentType, self::ALLOWED_MIME_PREFIXES)) {
            Log::warning("DemoImageImporter: rejected content type [{$contentType}] for {$url}");

            return null;
        }

        $body = $response->body();

        if (strlen($body) < self::MIN_BYTES) {
            Log::warning("DemoImageImporter: rejected undersized download ({$url})");

            return null;
        }

        $extension = match (true) {
            Str::contains($contentType, 'png') => 'png',
            Str::contains($contentType, 'webp') => 'webp',
            default => 'jpg',
        };

        $path = trim($directory, '/').'/'.$filenameBase.'-'.Str::random(8).'.'.$extension;

        Storage::disk('public')->put($path, $body);

        return $path;
    }
}
