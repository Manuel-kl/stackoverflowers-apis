<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CartController extends Controller
{
    public function store(AddCartRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $cart = $user->cart;

        if (!$cart) {
            $cart = $user->cart()->create();
        }

        $cartItem = $cart->items()->updateOrCreate(
            ['domain_name' => $data['domain_name']],
            [
                'number_of_years' => $data['number_of_years'],
                'price' => $data['price'],
            ]
        );

        $message = $cartItem->wasRecentlyCreated ? 'Item added to cart successfully' : 'Cart item updated successfully';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $cart,
        ], $cartItem->wasRecentlyCreated ? 201 : 200);
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $cart = $user->load('cart.items');

        if (!$cart) {
            return response()->json([
                'success' => true,
                'message' => 'Cart is empty',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully',
            'data' => $cart,
        ]);
    }

    public function removeFromCart(CartItem $cartItem): JsonResponse
    {
        Gate::authorize('can-manage-resource', $cartItem->cart->user_id);

        $cart = $cartItem->cart;
        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart successfully',
            'data' => $cart->fresh(['items']),
        ]);
    }

    public function destroy(Cart $cart): JsonResponse
    {
        Gate::authorize('can-manage-resource', $cart->user_id);

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
        ]);
    }
}
