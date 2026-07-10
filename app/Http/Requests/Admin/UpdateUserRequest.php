<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * Already gated by SuperAdminMiddleware at the route level — no
     * additional per-request authorization needed here.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'employee'])],
            'is_active' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * No self-promotion/demotion: a Super Admin editing their own account
     * can update every other field, but the role field itself is rejected
     * if changed. Enforced here (server-side) rather than only hiding the
     * field in the UI.
     *
     * The configured primary Super Admin (config/primary_super_admin.php)
     * gets the same role-change rejection PLUS an email-change rejection —
     * changing its email would let it drift away from the account
     * PrimarySuperAdminSeeder heals on every deploy, effectively losing its
     * "primary" status. This applies no matter who is editing it, including
     * another Super Admin.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->route('user');

            if ($user->id === auth()->id() && $this->input('role') !== $user->getRoleNames()->first()) {
                $validator->errors()->add('role', __('users.cannot_change_own_role'));
            }

            if ($user->isPrimarySuperAdmin()) {
                if ($this->input('role') !== 'super_admin') {
                    $validator->errors()->add('role', __('users.cannot_change_primary_super_admin_role'));
                }

                if (strcasecmp((string) $this->input('email'), $user->email) !== 0) {
                    $validator->errors()->add('email', __('users.cannot_change_primary_super_admin_email'));
                }
            }
        });
    }
}
