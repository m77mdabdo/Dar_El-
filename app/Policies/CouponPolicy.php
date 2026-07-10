<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('coupons.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->hasAdminAccess('coupons.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAdminAccess('coupons.create');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->hasAdminAccess('coupons.edit');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->hasAdminAccess('coupons.delete');
    }
}
