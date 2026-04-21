<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;


class PurchaseController extends Controller
{
    public function show($item_id)
    {
        $product = Product::findOrFail($item_id);
        $profile = Auth::user()->profile;
        $paymentMethod = old('payment_method', session('payment_method.' . $item_id, ''));
        return view('purchase', compact('product', 'profile', 'paymentMethod'));
    }

    public function store(PurchaseRequest $request, $item_id)
    {
        $validated = $request->validated();
        session(['payment_method.' . $item_id => $request->payment_method]);
        $product = Product::findOrFail($item_id);

        if (Order::where('product_id', $product->id)->exists()) {
            return redirect()->route('items.index')->with('error', 'この商品はすでに購入済みです。');;
        }
        $order = Order::create(array_merge($validated, [
            'user_id'    => Auth::id(),
            'product_id' => $product->id,
        ]));

        Transaction::create([
            'order_id'  => $order->id,
            'seller_id' => $product->user_id,
            'status'    => 'trading',
        ]);
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $paymentType = $request->payment_method === 'card' ? ['card'] : ['konbini'];

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => $paymentType,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $product->item_name,
                    ],
                    'unit_amount' => $product->price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('items.index') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('items.index'),
        ]);
        return redirect($session->url);
    }

    public function editAddress(Product $item, Request $request)
    {
        if ($request->filled('payment_method')) {
            session(['payment_method.' . $item->id => $request->payment_method]);
        }

        return view('address_edit', compact('item'));
    }

    public function updateAddress(AddressRequest $request, Product $item)
    {
        session([
            'sending_postcode' => $request->sending_postcode,
            'sending_address'  => $request->sending_address,
            'sending_building' => $request->sending_building,
        ]);

        return redirect()->route('purchase.show', $item->id);
    }
}




