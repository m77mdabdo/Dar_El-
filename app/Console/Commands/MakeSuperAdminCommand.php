<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeSuperAdminCommand extends Command
{
    protected $signature = 'app:make-super-admin {email} {--name=}';

    protected $description = 'Create a new Super Admin, or promote an existing account to Super Admin. The sanctioned way to bootstrap the first Super Admin in any environment, including production — safe to re-run.';

    public function handle(): int
    {
        $email = $this->argument('email');

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->syncRoles(['super_admin']);
            $this->info("Promoted existing user \"{$user->name}\" ({$email}) to Super Admin.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: $this->ask('Name for the new Super Admin');
        $password = $this->secret('Password for the new Super Admin');
        $confirmPassword = $this->secret('Confirm password');

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $passwordValidator = Validator::make(['password' => $password], ['password' => ['required', 'string', 'min:8']]);

        if ($passwordValidator->fails()) {
            $this->error($passwordValidator->errors()->first('password'));

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // email_verified_at isn't in User::$fillable — create() silently
        // drops it instead of erroring, so it must be set via forceFill().
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->syncRoles(['super_admin']);

        $this->info("Super Admin \"{$name}\" ({$email}) created successfully.");

        return self::SUCCESS;
    }
}
