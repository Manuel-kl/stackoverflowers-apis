<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function store(): JsonResponse
    {
        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
            ], 422);
        }

        $order = DB::transaction(function () use ($user, $cart) {
            $order = $user->orders()->create([
                'status' => OrderStatusEnum::PENDING_PAYMENT->value,
                'total_amount' => $cart->items->sum('price'),
                'currency' => 'KES',
            ]);

            foreach ($cart->items as $cartItem) {
                $order->items()->create([
                    'domain_name' => $cartItem->domain_name,
                    'number_of_years' => $cartItem->number_of_years,
                    'price' => $cartItem->price,
                    'currency' => 'KES',
                    'status' => OrderItemStatusEnum::PENDING->value,
                ]);
            }

            $cart->delete();

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
        ], 201);
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $orders = $user->orders()->with('items')->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders,
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('can-manage-resource', $order->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order->load('items'),
        ]);
    }
}
