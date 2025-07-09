<?php
// FILE: app/Services/V1/CartService.php

namespace App\Services\V1;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getCart(): array
    {
        $cart = $this->getCurrentCart();

        return [
            'data' => $cart->load(['items.product']),
            'message' => 'Cart retrieved successfully'
        ];
    }

    public function addToCart(array $data): array
    {
        // Lock the product row for update to prevent race conditions
        $product = Product::lockForUpdate()->findOrFail($data['product_id']);
        $quantity = $data['quantity'] ?? 1;

        if (! $product->canPurchase($quantity)) {
            throw ValidationException::withMessages([
                'product' => ['Product is not available in requested quantity.']
            ]);
        }

        $cartItem = null;

        DB::transaction(function () use ($product, $quantity, $data, &$cartItem) {
            // Decrease stock
            $product->decreaseStock($quantity);

            // Add or update cart item
            $cart = $this->getCurrentCart();
            $cartItem = $cart->addItem($product, $quantity, $data['options'] ?? []);
        });

        return [
            'data' => [
                'cart' => $cartItem->cart->load(['items.product']),
                'added_item' => $cartItem->load('product')
            ],
            'message' => 'Item added to cart successfully'
        ];
    }

    public function updateCartItem(int $cartItemId, array $data): array
{
    $cart = $this->getCurrentCart();
    $newQuantity = $data['quantity'];

    DB::transaction(function () use ($cart, $cartItemId, $newQuantity) {
        $item = $cart->items()->findOrFail($cartItemId);
        $oldQuantity = $item->quantity;

        if ($newQuantity <= 0) {
            // Restore stock for entire quantity before removing
            $item->product->increaseStock($oldQuantity);
        } else {
            $difference = $newQuantity - $oldQuantity;

            if ($difference > 0) {
                // Need to reserve more stock
                if (! $item->product->canPurchase($difference)) {
                    throw ValidationException::withMessages([
                        'product' => ['Not enough stock available to increase quantity.']
                    ]);
                }
                $item->product->decreaseStock($difference);
            } elseif ($difference < 0) {
                // Release stock
                $item->product->increaseStock(abs($difference));
            }
        }

        // Use the existing Cart model method to update/remove the item
        if (!$cart->updateItemQuantity($cartItemId, $newQuantity)) {
            throw ValidationException::withMessages([
                'item' => ['Cart item not found.']
            ]);
        }
    });

    return [
        'data' => $cart->load(['items.product']),
        'message' => 'Cart item updated successfully'
    ];
}


   public function removeFromCart(int $cartItemId): array
{
    $cart = $this->getCurrentCart();

    DB::transaction(function () use ($cart, $cartItemId) {
        $item = $cart->items()->findOrFail($cartItemId);
        
        // Restore stock before removing the item
        $item->product->increaseStock($item->quantity);

        // Use the existing Cart model method to remove the item
        if (!$cart->removeItem($cartItemId)) {
            throw ValidationException::withMessages([
                'item' => ['Cart item not found.']
            ]);
        }
    });

    return [
        'data' => $cart->load(['items.product']),
        'message' => 'Item removed from cart successfully'
    ];
}

public function clearCart(): array
{
    $cart = $this->getCurrentCart();

    DB::transaction(function () use ($cart) {
        // Restore stock for all items before clearing
        foreach ($cart->items as $item) {
            $item->product->increaseStock($item->quantity);
        }

        // Use the existing Cart model method to clear the cart
        $cart->clear();
    });

    return [
        'data' => $cart,
        'message' => 'Cart cleared successfully'
    ];
}


    public function getCurrentCart(): Cart
    {
        $user = Auth::user();
        
        return Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['subtotal' => 0, 'tax_amount' => 0, 'total' => 0]
        );
    }
}
