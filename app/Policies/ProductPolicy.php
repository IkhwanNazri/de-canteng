<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Sellers may only manage products that belong to their own shop.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->shop?->id === $product->shop_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
