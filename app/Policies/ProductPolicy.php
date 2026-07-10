<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasAdminAccess('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAdminAccess('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasAdminAccess('products.edit');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasAdminAccess('products.delete');
    }

    /**
     * Gates the gallery-image endpoints (destroyImage/updateImage/
     * reorderImages/setCoverImage) — separate from the general `update`
     * ability so an Employee can be granted "manage images" without full
     * product-edit rights (or vice versa), per the exact granularity the
     * Employee permission checkboxes are meant to offer.
     */
    public function manageImages(User $user, Product $product): bool
    {
        return $user->hasAdminAccess('products.edit') || $user->hasAdminAccess('products.manage_images');
    }

    /**
     * Gates variant/option management (ProductVariantController,
     * ProductOptionController, ProductOptionValueController,
     * ProductVariantBulkActionController) — same rationale as
     * manageImages() above.
     */
    public function manageVariants(User $user, Product $product): bool
    {
        return $user->hasAdminAccess('products.edit') || $user->hasAdminAccess('products.manage_variants');
    }
}
