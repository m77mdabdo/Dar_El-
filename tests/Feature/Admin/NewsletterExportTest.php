<?php

namespace Tests\Feature\Admin;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewsletterExportTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_export_contains_subscriber_emails(): void
    {
        $admin = $this->makeAdmin();
        NewsletterSubscriber::create(['email' => 'layla@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.newsletter.export'));

        $response->assertOk();
        $this->assertStringContainsString('layla@example.com', $response->streamedContent());
    }

    /**
     * A malicious local-part (RFC 5322 technically allows '=', '+', '-',
     * '@'-adjacent characters in the local part) must not produce a
     * formula-interpretable CSV cell — same guard as the Sales report
     * export, applied here since this export shares the exact
     * unsanitized fputcsv() pattern that was audited.
     */
    public function test_a_formula_like_email_is_neutralized_in_the_export(): void
    {
        $admin = $this->makeAdmin();
        NewsletterSubscriber::create(['email' => "=cmd|'/c calc'!A0@example.com"]);

        $response = $this->actingAs($admin)->get(route('admin.newsletter.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringNotContainsString("\n=cmd", $content);
        $this->assertStringNotContainsString(",=cmd", $content);
        $this->assertStringContainsString("'=cmd|'/c calc'!A0@example.com", $content);
    }
}
