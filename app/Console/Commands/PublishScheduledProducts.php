<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class PublishScheduledProducts extends Command
{
    protected $signature = 'products:publish-scheduled';

    protected $description = 'Publish any scheduled product whose scheduled_publish_at time has passed.';

    public function handle(): int
    {
        $due = Product::where('status', Product::STATUS_SCHEDULED)
            ->where('scheduled_publish_at', '<=', now())
            ->get();

        foreach ($due as $product) {
            $product->applyStatus(Product::STATUS_PUBLISHED);
        }

        $this->info("{$due->count()} product(s) published.");

        return self::SUCCESS;
    }
}
