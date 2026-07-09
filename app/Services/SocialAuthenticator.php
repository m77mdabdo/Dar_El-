<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewCustomerRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Spatie\Permission\Models\Role;

/**
 * Provider-agnostic find-or-create-or-link logic shared by every OAuth
 * provider — SocialAuthController never touches User rows directly, so
 * adding a new provider never means duplicating this logic.
 */
class SocialAuthenticator
{
    /**
     * @return array{0: User, 1: bool} the user, and whether it was just created
     */
    public function findOrCreate(string $provider, SocialiteUser $socialUser): array
    {
        $existingByProvider = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($existingByProvider) {
            return [$existingByProvider, false];
        }

        $existingByEmail = User::where('email', $socialUser->getEmail())->first();

        if ($existingByEmail) {
            $existingByEmail->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);

            // Google (and any other provider we'd add) has already verified
            // this email — trust it for an existing account that hadn't
            // verified yet rather than leaving it stuck. email_verified_at
            // isn't mass-assignable (see MustVerifyEmail), hence the
            // separate markEmailAsVerified() call rather than update([...]).
            if (! $existingByEmail->hasVerifiedEmail()) {
                $existingByEmail->markEmailAsVerified();
            }

            return [$existingByEmail, false];
        }

        $user = User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: __('New Customer'),
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(40)),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        // Not mass-assignable — see the note above.
        $user->markEmailAsVerified();

        $user->assignRole(Role::findOrCreate('customer', 'web'));

        Notification::send(User::admins(), new NewCustomerRegistered($user));

        event(new Registered($user));

        return [$user, true];
    }
}
