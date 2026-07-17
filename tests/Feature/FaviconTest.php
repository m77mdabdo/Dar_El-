<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function assertFaviconTagsPresent($response): void
    {
        $response->assertOk();
        $response->assertSee('<link rel="icon" href="'.asset('favicon.ico').'" sizes="any">', false);
        $response->assertSee('<link rel="icon" type="image/png" sizes="16x16" href="'.asset('assets/branding/favicon-16x16.png').'">', false);
        $response->assertSee('<link rel="icon" type="image/png" sizes="32x32" href="'.asset('assets/branding/favicon-32.png').'">', false);
        $response->assertSee('<link rel="icon" type="image/png" sizes="192x192" href="'.asset('assets/branding/favicon-192.png').'">', false);
        $response->assertSee('<link rel="apple-touch-icon" href="'.asset('assets/branding/apple-touch-icon.png').'">', false);
        $response->assertSee('<link rel="manifest" href="'.asset('site.webmanifest').'">', false);
    }

    public function test_favicon_tags_render_on_the_storefront_layout(): void
    {
        $this->assertFaviconTagsPresent($this->get(route('home')));
    }

    public function test_favicon_tags_render_on_the_guest_auth_layout(): void
    {
        $this->assertFaviconTagsPresent($this->get(route('login')));
    }

    public function test_favicon_tags_render_on_the_admin_layout(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertFaviconTagsPresent($this->actingAs($admin)->get(route('admin.dashboard')));
    }

    public function test_generated_favicon_files_exist_with_correct_dimensions(): void
    {
        $files = [
            'favicon.ico' => null,
            'assets/branding/favicon-16x16.png' => [16, 16],
            'assets/branding/favicon-32.png' => [32, 32],
            'assets/branding/favicon-192.png' => [192, 192],
            'assets/branding/favicon-512.png' => [512, 512],
            'assets/branding/apple-touch-icon.png' => [180, 180],
        ];

        foreach ($files as $relative => $dimensions) {
            $path = public_path($relative);
            $this->assertFileExists($path, "Missing favicon file: {$relative}");

            if ($dimensions !== null) {
                [$width, $height] = getimagesize($path);
                $this->assertSame($dimensions, [$width, $height], "Unexpected dimensions for {$relative}");
            }
        }
    }

    public function test_favicon_ico_is_a_valid_multi_resolution_icon(): void
    {
        $bytes = file_get_contents(public_path('favicon.ico'));

        // ICONDIR header: reserved(0), type(1 = icon), image count.
        $header = unpack('vreserved/vtype/vcount', substr($bytes, 0, 6));

        $this->assertSame(0, $header['reserved']);
        $this->assertSame(1, $header['type']);
        $this->assertGreaterThanOrEqual(1, $header['count']);
    }
}
