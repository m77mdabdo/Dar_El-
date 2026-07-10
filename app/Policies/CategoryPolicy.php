<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasAdminAccess('categories.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAdminAccess('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasAdminAccess('categories.edit');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasAdminAccess('categories.delete');
    }
}
