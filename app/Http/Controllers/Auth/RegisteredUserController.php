<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewCustomerRegistered;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

class RegisteredUserController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $redirect = $request->string('redirect');
        if ($redirect->isNotEmpty() && Str::startsWith($redirect, '/') && ! Str::startsWith($redirect, '//')) {
            $request->session()->put('url.intended', url($redirect->value()));
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole(Role::findOrCreate('customer', 'web'));

        Notification::send(User::admins(), new NewCustomerRegistered($user));

        event(new Registered($user));

        Auth::login($user);

        // The account already exists and the customer is already logged in
        // at this point — a transient mail-transport failure here must not
        // 500 the whole registration. OtpService already logged the
        // specific cause; the customer lands on the OTP screen either way
        // and can use "resend" once the transport issue clears.
        try {
            $this->otp->generate($user);
        } catch (Throwable $e) {
            Log::warning('Registration OTP send failed; account created, customer can resend', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('otp.notice')->with('status', __('Your account was created, but we could not send your verification code. Please use "Resend code" below.'));
        }

        return redirect()->route('otp.notice');
    }
}
