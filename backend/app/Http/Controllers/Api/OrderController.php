<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'payment_method'  => 'required|in:card,konbini',
            'sending_postcode' => 'required|string|max:8',
            'sending_address'  => 'required|string|max:255',
            'sending_building' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (Order::where('product_id', $product->id)->exists()) {
            return response()->json(['message' => 'この商品はすでに購入済みです。'], 422);
        }

        $order = Order::create([
            'user_id'          => Auth::id(),
            'product_id'       => $product->id,
            'payment_method'   => $request->payment_method,
            'sending_postcode' => $request->sending_postcode,
            'sending_address'  => $request->sending_address,
            'sending_building' => $request->sending_building,
        ]);

        Transaction::create([
            'order_id'  => $order->id,
            'seller_id' => $product->user_id,
            'status'    => 'trading',
        ]);

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $paymentType = $request->payment_method === 'card' ? ['card'] : ['konbini'];

        $successUrl = config('app.frontend_url', 'http://localhost:3000') . '/?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = config('app.frontend_url', 'http://localhost:3000') . '/checkout?product_id=' . $product->id;

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => $paymentType,
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'jpy',
                    'product_data' => ['name' => $product->item_name],
                    'unit_amount'  => $product->price,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        return response()->json([
            'checkout_url' => $session->url,
            'order_id'     => $order->id,
        ]);
    }
}
