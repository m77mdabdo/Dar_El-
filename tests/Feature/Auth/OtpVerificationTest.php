<?php

namespace Tests\Feature\Auth;

use App\Jobs\GenerateAndSendInvoice;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use App\Notifications\LoginAlertNotification;
use App\Notifications\NewOrderPlaced;
use App\Notifications\OtpVerificationNotification;
use App\Services\OtpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_screen_can_be_rendered_for_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-otp');

        $response->assertStatus(200);
    }

    public function test_verified_user_visiting_otp_screen_is_redirected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-otp');

        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_correct_otp_verifies_the_account(): void
    {
        $user = User::factory()->unverified()->create();

        app(OtpService::class)->generate($user);
        $otp = EmailVerificationOtp::where('user_id', $user->id)->latest('id')->first();

        // Re-derive the plaintext code isn't possible (hashed), so exercise
        // the service directly to get a known code for the HTTP assertion.
        $plainOtp = '123456';
        $otp->update(['otp' => Hash::make($plainOtp)]);

        $response = $this->actingAs($user)->post('/verify-otp', ['otp' => $plainOtp]);

        $response->assertRedirect(route('home', absolute: false));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_incorrect_otp_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        app(OtpService::class)->generate($user);

        $response = $this->actingAs($user)->post('/verify-otp', ['otp' => '000000']);

        $response->assertSessionHasErrors('otp');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_blocked_during_cooldown(): void
    {
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->generate($user);

        $response = $this->actingAs($user)->post('/resend-otp');

        $response->assertSessionHasErrors('otp');
        $this->assertSame(1, EmailVerificationOtp::where('user_id', $user->id)->count());
    }

    public function test_otp_notification_is_never_queued(): void
    {
        // The single architectural guarantee the rest of the OTP-speed
        // work depends on: this notification must send synchronously
        // (blocking, transport-confirmed) rather than waiting for a queue
        // worker/cron cycle, since the customer is actively waiting on it.
        $this->assertFalse(
            is_subclass_of(OtpVerificationNotification::class, ShouldQueue::class),
            'OtpVerificationNotification must not implement ShouldQueue — OTP has to send synchronously, not enter any queue (including the invoices queue).'
        );
    }

    public function test_login_alert_notification_is_sent_synchronously(): void
    {
        // Previously ShouldQueue on a 'high' priority queue; now sent
        // synchronously (same reasoning as OTP above) so a security-relevant
        // login alert reaches the inbox immediately instead of waiting for
        // the next cron-driven queue:work tick.
        $this->assertFalse(
            is_subclass_of(LoginAlertNotification::class, ShouldQueue::class),
            'LoginAlertNotification must not implement ShouldQueue — it has to send synchronously so it is not delayed by the cron-driven queue worker.'
        );
    }

    public function test_invoice_job_uses_invoices_queue_not_high_or_default(): void
    {
        $job = new GenerateAndSendInvoice(new \App\Models\Order());

        $this->assertSame('invoices', $job->queue);
    }

    public function test_normal_order_notification_uses_default_queue(): void
    {
        // No explicit queue assignment — implicitly 'default', and must
        // stay that way: 'high' is reserved for auth-adjacent alerts,
        // 'invoices' for PDF-generation work, so a normal notification
        // landing on either would be a queue-priority regression.
        $notification = new NewOrderPlaced(new \App\Models\Order());

        $this->assertNull($notification->queue);
    }

    public function test_registration_otp_failure_does_not_crash_the_request_and_account_still_exists(): void
    {
        // Isolates the assertion to the one genuinely synchronous send
        // (OTP). Without this, phpunit.xml's QUEUE_CONNECTION=sync makes
        // *queued* work (NewCustomerRegistered admin alert, the login-alert
        // notification fired by Auth::login()) execute inline too — a
        // test-environment-only quirk, since in production those are real
        // queued jobs that never touch Mail::mailer() inside the request.
        \Illuminate\Support\Facades\Queue::fake();

        Mail::shouldReceive('mailer')->andReturnUsing(function () {
            $mailer = \Mockery::mock();
            $mailer->shouldReceive('send')->andThrow(new \RuntimeException('SMTP unavailable (simulated)'));

            return $mailer;
        });

        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'otpfailure@example.com',
            'phone' => '01012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // The account is real and the customer is logged in — a transport
        // failure must degrade to "please resend", never a 500.
        $response->assertRedirect(route('otp.notice', absolute: false));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['email' => 'otpfailure@example.com']);
        $this->assertAuthenticated();
    }

    public function test_otp_resend_failure_returns_a_graceful_error_not_a_500(): void
    {
        $user = User::factory()->unverified()->create();

        Mail::shouldReceive('mailer')->andReturnUsing(function () {
            $mailer = \Mockery::mock();
            $mailer->shouldReceive('send')->andThrow(new \RuntimeException('SMTP unavailable (simulated)'));

            return $mailer;
        });

        $response = $this->actingAs($user)->post('/resend-otp');

        $response->assertSessionHasErrors('otp');
        $response->assertSessionMissing('status');
    }
}
