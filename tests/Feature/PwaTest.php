<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * sw.js and site.webmanifest are plain static files under public/, served
 * directly by the webserver in production (never routed through Laravel) —
 * so they're asserted on via direct filesystem reads here, not $this->get(),
 * which would 404 against them since no Laravel route serves those paths.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;


    protected function manifest(): array
    {
        return json_decode(File::get(public_path('site.webmanifest')), true);
    }

    protected function serviceWorkerSource(): string
    {
        return File::get(public_path('sw.js'));
    }

    // ---------------------------------------------------------------
    // Manifest
    // ---------------------------------------------------------------

    public function test_manifest_file_exists_and_is_valid_json(): void
    {
        $this->assertFileExists(public_path('site.webmanifest'));
        $this->assertIsArray($this->manifest());
    }

    public function test_manifest_has_required_installability_fields(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('Dar El Jamila', $manifest['name']);
        $this->assertSame('Dar El Jamila', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertArrayHasKey('theme_color', $manifest);
        $this->assertArrayHasKey('background_color', $manifest);
    }

    public function test_manifest_theme_color_matches_dj_maroon_dark_palette(): void
    {
        $this->assertSame('#3C0B17', $this->manifest()['theme_color']);
    }

    public function test_manifest_declares_both_192_and_512_icons(): void
    {
        $sizes = collect($this->manifest()['icons'])->pluck('sizes')->all();

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        foreach ($this->manifest()['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_every_page_links_the_manifest_and_theme_color(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('rel="manifest" href="'.asset('site.webmanifest').'"', false);
        $response->assertSee('name="theme-color" content="#3C0B17"', false);
    }

    // ---------------------------------------------------------------
    // Service worker: file + route-scoping logic
    // ---------------------------------------------------------------

    public function test_service_worker_file_exists_at_the_site_root(): void
    {
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_service_worker_registration_is_wired_in_app_js(): void
    {
        $source = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString("navigator.serviceWorker.register('/sw.js')", $source);
    }

    #[DataProvider('neverCachePathProvider')]
    public function test_sensitive_prefixes_are_excluded_from_all_caching(string $prefix): void
    {
        $this->assertStringContainsString("'".$prefix."'", $this->serviceWorkerSource());
    }

    public static function neverCachePathProvider(): array
    {
        return [
            'admin' => ['/admin'],
            'checkout' => ['/checkout'],
            'cart' => ['/cart'],
            'account' => ['/account'],
            'login' => ['/login'],
            'invoice' => ['/invoice/'],
        ];
    }

    public function test_non_get_requests_are_never_intercepted(): void
    {
        $this->assertStringContainsString("request.method !== 'GET'", $this->serviceWorkerSource());
    }

    public function test_static_build_and_asset_paths_are_cache_first(): void
    {
        $source = $this->serviceWorkerSource();

        $this->assertStringContainsString("pathname.startsWith('/build/')", $source);
        $this->assertStringContainsString("pathname.startsWith('/assets/')", $source);
        $this->assertStringContainsString('cacheFirst(request, STATIC_CACHE)', $source);
    }

    public function test_page_navigations_use_network_first_with_offline_fallback(): void
    {
        $source = $this->serviceWorkerSource();

        $this->assertStringContainsString("request.mode === 'navigate'", $source);
        $this->assertStringContainsString('networkFirstNavigation', $source);
        $this->assertStringContainsString('OFFLINE_URL', $source);
    }

    public function test_never_cache_paths_are_checked_before_static_or_navigation_handling(): void
    {
        $source = $this->serviceWorkerSource();

        $neverCachePos = strpos($source, 'isNeverCachePath(url.pathname)');
        $staticPos = strpos($source, 'isStaticAsset(url.pathname)');
        $navigatePos = strpos($source, "request.mode === 'navigate'");

        $this->assertNotFalse($neverCachePos);
        $this->assertLessThan($staticPos, $neverCachePos);
        $this->assertLessThan($navigatePos, $staticPos);
    }

    // ---------------------------------------------------------------
    // Offline fallback page
    // ---------------------------------------------------------------

    public function test_offline_page_renders_with_branded_content(): void
    {
        $response = $this->get(route('offline'));

        $response->assertOk();
        $response->assertSee(__('general.errors.offline_title'));
        $response->assertSee(__('general.errors.offline_retry'));
    }

    public function test_offline_page_is_precached_by_the_service_worker(): void
    {
        $this->assertStringContainsString("'/offline'", $this->serviceWorkerSource());
    }

    // ---------------------------------------------------------------
    // Install prompt
    // ---------------------------------------------------------------

    public function test_install_banner_markup_is_present_on_storefront_pages(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="dj-install-banner"', false);
        $response->assertSee('djInstallApp()', false);
        $response->assertSee('djDismissInstallBanner()', false);
    }

    public function test_install_dismissal_uses_a_timestamped_localstorage_key(): void
    {
        $source = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString("localStorage.setItem(DJ_INSTALL_DISMISS_KEY, Date.now().toString())", $source);
    }

    // ---------------------------------------------------------------
    // iOS Safari fallback (beforeinstallprompt never fires there)
    // ---------------------------------------------------------------

    public function test_ios_install_banner_markup_is_present_on_storefront_pages(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="dj-ios-install-banner"', false);
        $response->assertSee(__('Tap the Share button ⬆️ below, then choose "Add to Home Screen".'));
    }

    public function test_ios_banner_does_not_render_a_functional_install_button(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();

        $iosBannerStart = strpos($html, 'id="dj-ios-install-banner"');
        $this->assertNotFalse($iosBannerStart);

        // Isolate just the iOS banner's own markup block (up to the next
        // closing </div> pair after it) and confirm djInstallApp() —
        // the Chrome-only programmatic trigger — never appears inside it.
        $iosBannerHtml = substr($html, $iosBannerStart, 600);
        $this->assertStringNotContainsString('djInstallApp()', $iosBannerHtml);
        $this->assertStringContainsString('djDismissInstallBanner()', $iosBannerHtml);
    }

    public function test_ios_detection_checks_user_agent_excludes_other_ios_browsers_and_checks_standalone_mode(): void
    {
        $source = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString('djIsIosSafari', $source);
        // Other iOS browsers (Chrome/Firefox/Edge/Opera for iOS) also
        // report "Safari" in their UA — must be excluded, since only
        // Safari itself has the Share-sheet flow these instructions describe.
        $this->assertStringContainsString('CriOS', $source);
        $this->assertStringContainsString('FxiOS', $source);
        $this->assertStringContainsString('navigator.standalone', $source);
        $this->assertStringContainsString('djIsRunningStandalone', $source);
    }

    public function test_ios_fallback_reuses_the_same_dismissal_cooldown_as_the_android_banner(): void
    {
        $source = File::get(resource_path('js/app.js'));

        $iosCheckPos = strpos($source, 'djIsIosSafari() && !djIsRunningStandalone()');
        $this->assertNotFalse($iosCheckPos);
        $this->assertStringContainsString('djInstallPromptDismissedRecently()', substr($source, $iosCheckPos, 100));
    }

    public function test_shared_dismiss_function_clears_both_android_and_ios_banners(): void
    {
        $source = File::get(resource_path('js/app.js'));

        $dismissFnStart = strpos($source, 'window.djDismissInstallBanner = function');
        $dismissFnBody = substr($source, $dismissFnStart, 300);

        $this->assertStringContainsString("getElementById('dj-install-banner')", $dismissFnBody);
        $this->assertStringContainsString("getElementById('dj-ios-install-banner')", $dismissFnBody);
    }
}
