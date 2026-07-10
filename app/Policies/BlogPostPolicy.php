<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('blog.view');
    }

    public function view(User $user, BlogPost $blogPost): bool
    {
        return $user->hasAdminAccess('blog.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAdminAccess('blog.create');
    }

    public function update(User $user, BlogPost $blogPost): bool
    {
        return $user->hasAdminAccess('blog.edit');
    }

    public function delete(User $user, BlogPost $blogPost): bool
    {
        return $user->hasAdminAccess('blog.delete');
    }
}
