<?php

namespace App\Console\Commands;

use App\Models\Banner;
use Illuminate\Console\Command;

/**
 * Safety-valve for the "active hero banner overrides the homepage
 * default" scenario — see the Hero Banners admin page's opt-in design
 * (HomeController::index() / home.blade.php): the homepage only ever
 * shows the hardcoded default hero when zero Banner::TYPE_HERO rows
 * are active. This command exists for exactly one situation: a hero
 * banner is live in production and needs pulling without shell/tinker
 * access to the database.
 *
 * Deactivates rather than deletes — reversible if the banner turns out
 * to have been wanted, and matches the same is_active toggle the admin
 * screen itself uses (HeroBannerController::toggleActive()), so this
 * is just that same action, scriptable for a one-off SSH run instead
 * of a database credential.
 */
class DeactivateActiveHeroBanners extends Command
{
    protected $signature = 'banners:deactivate-hero';

    protected $description = 'Deactivate any active hero banner(s), reverting the homepage to its default hardcoded hero';

    public function handle(): int
    {
        $activeHeroBanners = Banner::where('type', Banner::TYPE_HERO)->where('is_active', true)->get();

        if ($activeHeroBanners->isEmpty()) {
            $this->info('No active hero banners found — the homepage is already showing its default hero.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($activeHeroBanners as $banner) {
            // ->update() (not a raw query) so Banner::booted()'s saved()
            // hook fires and busts storefront.home.data itself — no
            // separate cache-clear step needed here.
            $banner->update(['is_active' => false]);

            $rows[] = [$banner->id, $banner->title_en, $banner->image, $banner->created_at->toDateTimeString()];
        }

        $this->info($activeHeroBanners->count().' active hero banner(s) deactivated:');
        $this->table(['ID', 'Title', 'Image', 'Created At'], $rows);

        return self::SUCCESS;
    }
}
