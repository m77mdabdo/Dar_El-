<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id && $review->status === 'pending';
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
