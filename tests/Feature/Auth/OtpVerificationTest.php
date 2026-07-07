<?php

namespace Tests\Feature\Auth;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
