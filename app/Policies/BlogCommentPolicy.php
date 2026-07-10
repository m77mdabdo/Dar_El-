<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\User;

class BlogCommentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('comments.view');
    }

    public function view(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id || $user->hasAdminAccess('comments.view');
    }

    public function update(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id && $comment->status === 'pending';
    }

    public function delete(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id || $user->hasAdminAccess('comments.delete');
    }

    public function approve(User $user, BlogComment $comment): bool
    {
        return $user->hasAdminAccess('comments.approve');
    }

    public function reject(User $user, BlogComment $comment): bool
    {
        return $user->hasAdminAccess('comments.reject');
    }
}
