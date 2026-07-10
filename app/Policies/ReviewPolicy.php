<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('reviews.view');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->id === $review->user_id || $user->hasAdminAccess('reviews.view');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id && $review->status === 'pending';
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id || $user->hasAdminAccess('reviews.delete');
    }

    public function approve(User $user, Review $review): bool
    {
        return $user->hasAdminAccess('reviews.approve');
    }

    public function reject(User $user, Review $review): bool
    {
        return $user->hasAdminAccess('reviews.reject');
    }

    public function feature(User $user, Review $review): bool
    {
        return $user->hasAdminAccess('reviews.approve');
    }

    public function unfeature(User $user, Review $review): bool
    {
        return $user->hasAdminAccess('reviews.approve');
    }
}
