<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'provider', 'provider_id', 'avatar', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function blogComments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function emailVerificationOtps(): HasMany
    {
        return $this->hasMany(EmailVerificationOtp::class);
    }

    /**
     * Overrides the MustVerifyEmail trait's default (sends Laravel's
     * native link-based VerifyEmail notification). This app verifies
     * exclusively through the custom OTP system (OtpService /
     * OtpVerificationNotification) — but Laravel's framework
     * unconditionally wires `Registered` to this method on every
     * registration (see Illuminate\Foundation\Support\Providers\
     * EventServiceProvider::configureEmailVerification(), which isn't
     * app code and can't be disabled via config). Left as the native
     * send, this was a second, redundant, blocking synchronous email on
     * every registration — sent immediately before the real OTP, so any
     * SMTP slowness delayed the OTP customers actually need by roughly
     * double. No-op instead: the account's un-verified state and the
     * OTP flow are completely unaffected, only this unused duplicate
     * email is skipped.
     */
    public function sendEmailVerificationNotification(): void
    {
        //
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function customerNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function isDisabled(): bool
    {
        return ! is_null($this->disabled_at);
    }

    /**
     * Identifies the one account (configured in
     * config/primary_super_admin.php) that must always exist as an
     * unrestricted Super Admin. UserController/UpdateUserRequest use this
     * to refuse role changes, permission removal, deletion, and disabling
     * of this specific account, regardless of who's attempting it.
     */
    public function isPrimarySuperAdmin(): bool
    {
        $email = config('primary_super_admin.email');

        return $email && strcasecmp($this->email, $email) === 0;
    }

    /**
     * Where a user lands after login/registration/OTP verification when
     * there's no intended URL to return to (e.g. a guest checkout redirect).
     */
    public function postLoginRedirectRoute(): string
    {
        return $this->hasAnyRole(['admin', 'super_admin', 'employee']) ? route('admin.dashboard') : route('home');
    }

    /**
     * The single source of truth for "can this user do X in the admin
     * panel". `super_admin` always passes, regardless of whether
     * permissions happen to be seeded in a given environment — this
     * mirrors the `before() { return $user->hasRole('admin') ? true : null; }`
     * shortcut already used by every Policy in this app, just extended to
     * also cover routes/sidebar and the `super_admin` role.
     *
     * `admin` gets the same blanket bypass for every *operational*
     * permission, but deliberately NOT for `users.*`/`roles.*`/
     * `permissions.*` — those three groups are Super-Admin-exclusive by
     * explicit requirement ("Admin should not see the Roles & Permissions
     * page or Admin Users management page"), so admin falls through to the
     * real permission check for those slugs specifically (and
     * PermissionSeeder never grants them to the admin role, so that check
     * correctly returns false).
     *
     * Everyone else (in practice, `employee`) needs the specific
     * permission actually granted. Deliberately uses getAllPermissions()
     * rather than hasPermissionTo(), which throws PermissionDoesNotExist
     * on an unseeded/typo'd slug — that would turn a permission typo into
     * a 500 for every employee. This degrades safely to "denied" instead.
     */
    public function hasAdminAccess(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $isSuperAdminOnlyPermission = Str::startsWith($permission, ['users.', 'roles.', 'permissions.']);

        if ($this->hasRole('admin') && ! $isSuperAdminOnlyPermission) {
            return true;
        }

        return $this->getAllPermissions()->contains('name', $permission);
    }

    /**
     * The redirect response for any successful authentication event
     * (login, registration, OTP/email verification, password
     * confirmation). Honors Laravel's stashed "intended URL"
     * (`session('url.intended')`) so a customer who was mid-checkout, on
     * their wishlist, or in their cart before being asked to log in or
     * register lands right back there — except when that stashed URL
     * points into the admin panel and this user isn't an admin.
     *
     * Without this guard: a guest who merely *visited* an admin-only URL
     * while logged out gets it stashed as their "intended" destination by
     * Laravel's auth middleware before being bounced to /login. If they
     * then register or log in as a normal customer instead, every
     * subsequent redirect()->intended(...) call would honor that stale
     * admin URL and send the brand-new customer straight into /admin,
     * where AdminMiddleware rejects them.
     *
     * $fallbackQuery is appended only to the default (non-intended)
     * destination, e.g. VerifyEmailController's '?verified=1'.
     */
    public function redirectResponseAfterAuth(string $fallbackQuery = ''): RedirectResponse
    {
        $intended = session('url.intended');

        if ($intended && ! $this->hasAnyRole(['admin', 'super_admin', 'employee'])) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';

            if ($path === '/admin' || Str::startsWith($path, '/admin/')) {
                session()->forget('url.intended');
            }
        }

        return redirect()->intended($this->postLoginRedirectRoute().$fallbackQuery);
    }

    public function isSocialAccount(): bool
    {
        return ! is_null($this->provider);
    }

    /**
     * Human-readable "Registration Method" label (admin customer view, and
     * "Connected with X" on the profile page). Provider names are brand
     * names, kept untranslated in both locales — adding a new provider
     * later only needs a case here, no other code changes.
     */
    public function registrationMethodLabel(): string
    {
        return match ($this->provider) {
            'google' => 'Google',
            'apple' => 'Apple',
            'facebook' => 'Facebook',
            'microsoft' => 'Microsoft',
            'github' => 'GitHub',
            'linkedin' => 'LinkedIn',
            default => __('Email'),
        };
    }

    /**
     * The avatar to display: a locally-uploaded custom avatar takes
     * precedence over the one pulled from the OAuth provider at signup,
     * which itself takes precedence over no avatar at all (initials shown
     * in the UI instead).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return Str::startsWith($this->avatar_path, ['http://', 'https://'])
                ? $this->avatar_path
                : asset('storage/'.$this->avatar_path);
        }

        return $this->avatar;
    }

    /**
     * All admin/super-admin users, used as the recipient list for
     * admin-facing notifications (new order, low stock, new customer,
     * etc). Deliberately excludes `employee` — their narrower, per-account
     * scope means blanket operational notifications aren't a safe
     * default. Unlike Spatie's role() scope, this never throws if the
     * roles haven't been created yet (e.g. a fresh install/test) — it
     * just returns none.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public static function admins(): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->get();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
