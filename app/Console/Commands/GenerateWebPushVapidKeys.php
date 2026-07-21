<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateWebPushVapidKeys extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Generate a fresh VAPID keypair for Web Push and print the .env lines to add — run this directly on each environment (local/staging/production), never copy the same keypair between them.';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->line('Add these to this environment\'s .env file (never commit them):');
        $this->newLine();
        $this->line("WEBPUSH_VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("WEBPUSH_VAPID_PRIVATE_KEY={$keys['privateKey']}");
        $this->newLine();
        $this->line('WEBPUSH_VAPID_SUBJECT can stay unset (defaults to mailto:'.config('mail.from.address').') or be set to your site URL.');

        return self::SUCCESS;
    }
}
