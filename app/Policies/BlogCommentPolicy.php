<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\User;

class BlogCommentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function update(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id && $comment->status === 'pending';
    }

    public function delete(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
