<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function initiateCart(): JsonResponse
    {
        $user = Auth::user();

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            $cart = $user->cart()->create();
        }

        $cart->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Cart initiated successfully',
            'data' => $cart,
        ], 200);
    }

    public function addToCart(AddCartRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            $cart = $user->cart()->create();
        }

        $existingItem = $cart->items()->where('domain_name', $data['domain_name'])->first();

        if ($existingItem) {
            $existingItem->update([
                'number_of_years' => $data['number_of_years'],
                'price' => $data['price'],
            ]);

            $message = 'Cart item updated successfully';
        } else {
            $cart->items()->create([
                'domain_name' => $data['domain_name'],
                'number_of_years' => $data['number_of_years'],
                'price' => $data['price'],
            ]);

            $message = 'Item added to cart successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $cart,
        ], 200);
    }

    public function getCart(): JsonResponse
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully',
            'data' => $cart,
        ], 200);
    }
}
