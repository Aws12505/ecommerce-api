<?php
// FILE: app/Services/V1/CartService.php

namespace App\Services\V1;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        $product = Product::findOrFail($data['product_id']);
        $quantity = $data['quantity'] ?? 1;

        if (!$product->canPurchase($quantity)) {
            throw ValidationException::withMessages([
                'product' => ['Product is not available in requested quantity.']
            ]);
        }

        $cart = $this->getCurrentCart();
        $cartItem = $cart->addItem($product, $quantity, $data['options'] ?? []);

        return [
            'data' => [
                'cart' => $cart->load(['items.product']),
                'added_item' => $cartItem->load('product')
            ],
            'message' => 'Item added to cart successfully'
        ];
    }

    public function updateCartItem(int $cartItemId, array $data): array
    {
        $cart = $this->getCurrentCart();
        $quantity = $data['quantity'];

        if (!$cart->updateItemQuantity($cartItemId, $quantity)) {
            throw ValidationException::withMessages([
                'item' => ['Cart item not found.']
            ]);
        }

        return [
            'data' => $cart->load(['items.product']),
            'message' => 'Cart item updated successfully'
        ];
    }

    public function removeFromCart(int $cartItemId): array
    {
        $cart = $this->getCurrentCart();

        if (!$cart->removeItem($cartItemId)) {
            throw ValidationException::withMessages([
                'item' => ['Cart item not found.']
            ]);
        }

        return [
            'data' => $cart->load(['items.product']),
            'message' => 'Item removed from cart successfully'
        ];
    }

    public function clearCart(): array
    {
        $cart = $this->getCurrentCart();
        $cart->clear();

        return [
            'data' => $cart,
            'message' => 'Cart cleared successfully'
        ];
    }

    private function getCurrentCart(): Cart
    {
        $user = Auth::user();
        
        return Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['subtotal' => 0, 'tax_amount' => 0, 'total' => 0]
        );
    }
}
