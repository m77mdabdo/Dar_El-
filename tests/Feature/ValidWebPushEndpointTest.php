<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Before ValidWebPushEndpoint existed, PushSubscriptionController validated
 * `endpoint` with the plain 'url' rule — which accepts ANY scheme/host,
 * including http://127.0.0.1/..., https://169.254.169.254/latest/meta-data,
 * or any other internal address. PushNotificationService later makes a real
 * server-side HTTP POST to exactly this stored value (minishlink/web-push's
 * sendOneNotification()), so that was a genuine SSRF: an attacker registers
 * an internal URL as their "subscription", then triggers any event that
 * pushes a notification to them (an order-status change on their own order,
 * a back-in-stock fulfillment) and the server makes the request on their
 * behalf. Every rejection test below documents a URL the old 'url' rule
 * would have accepted and the new allowlist now blocks.
 */
class ValidWebPushEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.uniqid(),
            'keys' => [
                'p256dh' => 'BNZt1sr089T8_QclkT-OqDVevOhFACXtStn5mqb2AP6VGhj1YnLwbceJ6PrP-H5xoKzaLr4_DIgud1fiDgSkT'.substr(uniqid(), 0, 2),
                'auth' => 'TXzvF9_PZts9JMuIpVC1'.substr(uniqid(), 0, 2),
            ],
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // SSRF: host allowlist (proves the vulnerability existed, Findings #2/#6)
    // ---------------------------------------------------------------

    public function test_subscribe_rejects_a_loopback_endpoint(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'https://127.0.0.1/steal',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_rejects_a_private_ip_literal_endpoint(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'https://192.168.1.5/internal-api',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_rejects_the_cloud_metadata_endpoint(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'https://169.254.169.254/latest/meta-data/iam/security-credentials/',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_rejects_an_ipv6_loopback_literal_endpoint(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'https://[::1]/steal',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_rejects_a_non_https_scheme(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'http://fcm.googleapis.com/fcm/send/abc',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_rejects_an_arbitrary_non_allowlisted_https_host(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'https://evil.example.com/collect',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_rejects_a_lookalike_host_that_is_not_a_true_subdomain(): void
    {
        // Ends in ".attacker.net", not ".push.apple.com" — a naive
        // str_contains()/substring check would be fooled by this; the fix
        // uses str_ends_with() against the allowed suffix specifically to
        // rule this shape out.
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'endpoint' => 'https://evil-push.apple.com.attacker.net/fake',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_accepts_every_allowlisted_push_service_host(): void
    {
        $realHosts = [
            'https://fcm.googleapis.com/fcm/send/'.uniqid(),
            'https://android.googleapis.com/gcm/send/'.uniqid(),
            'https://updates.push.services.mozilla.com/wpush/v2/'.uniqid(),
            'https://sn1p.notify.windows.com/w/?token='.uniqid(),
            'https://web.push.apple.com/'.uniqid(),
        ];

        foreach ($realHosts as $endpoint) {
            $response = $this->postJson(route('push.subscribe'), $this->payload(['endpoint' => $endpoint]));

            $response->assertOk();
        }
    }

    // ---------------------------------------------------------------
    // Key-material format validation (proves Finding #6)
    // ---------------------------------------------------------------

    public function test_subscribe_rejects_obviously_garbage_p256dh_key(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'keys' => ['p256dh' => 'not-a-real-key', 'auth' => 'TXzvF9_PZts9JMuIpVC1XY'],
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('keys.p256dh');
    }

    public function test_subscribe_rejects_obviously_garbage_auth_key(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'keys' => [
                'p256dh' => 'BNZt1sr089T8_QclkT-OqDVevOhFACXtStn5mqb2AP6VGhj1YnLwbceJ6PrP-H5xoKzaLr4_DIgud1fiDgSkTXY',
                'auth' => 'short',
            ],
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('keys.auth');
    }

    public function test_subscribe_rejects_key_material_with_invalid_characters(): void
    {
        $response = $this->postJson(route('push.subscribe'), $this->payload([
            'keys' => [
                'p256dh' => str_repeat('!', 87),
                'auth' => str_repeat('!', 22),
            ],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keys.p256dh', 'keys.auth']);
    }
}
