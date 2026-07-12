<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Reads/writes the single JSON manifest (storage/app/demo-images/manifest.json)
 * that maps demo catalog entities to the real photo paths ImportDemoData
 * downloaded for them. Seeders read this instead of hitting Pexels
 * themselves, so re-running a seeder never re-downloads anything — it's
 * purely a local lookup.
 */
class DemoImageManifest
{
    protected const PATH = 'demo-images/manifest.json';

    protected const DEFAULTS = [
        'categories' => [],   // slug => single path (category cover)
        'products' => [],     // category slug => flat pool of paths, consumed N-at-a-time per product
        'blog' => [],         // flat pool of paths, one per post
        'brand_banners' => [],// flat pool of paths, one per brand
        'collections' => [],  // slug => single path (collection cover)
        'banners' => [],      // flat pool of paths, one per homepage banner
    ];

    public static function load(): array
    {
        if (! Storage::disk('local')->exists(self::PATH)) {
            return self::DEFAULTS;
        }

        $data = json_decode(Storage::disk('local')->get(self::PATH), true);

        return is_array($data) ? array_merge(self::DEFAULTS, $data) : self::DEFAULTS;
    }

    public static function save(array $manifest): void
    {
        Storage::disk('local')->put(self::PATH, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
