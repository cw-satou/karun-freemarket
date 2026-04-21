<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CommentRequest;


class ItemController extends Controller
{
    public function index(Request $request)
        {
        $tab = $request->query('tab', 'recommend');
        $keyword = $request->query('keyword');
        $products = collect();

        if ($tab === 'mylist') {
            if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $query = $user->favoriteProducts()
                    ->where('products.user_id', '!=', $user->id)
                    ->with('order')
                    ->searchByName($keyword);

            $products = $query->latest()
                    ->get();}

        } else {
            $query = Product::query();
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
            $products = $query->searchByName($keyword)
                    ->latest()
                    ->get();
        }
            session()->forget(['sending_postcode', 'sending_address', 'sending_building']);

            foreach ($products as $product) {
                session()->forget('payment_method.' . $product->id);
            }
        return view('index', compact('products', 'tab'));
        }

    public function show(Product $item)
    {
        $item->load([
            'condition',
            'categories',
            'comments' => function($query) {
            $query->with('user.profile');
            },
            'favoritedByUsers',
        ]);
        return view('product', ['product' => $item]);
    }

    public function store(Request $request, Product $item)
    {
        $user = $request->user();
        $action = $request->input('action');

        if ($action === 'toggle_favorite') {

            if ($item->favoritedByUsers()->where('user_id', $user->id)->exists()) {
                $item->favoritedByUsers()->detach($user->id);
            } else {
                $item->favoritedByUsers()->attach($user->id);
            }

        } elseif ($action === 'comment') {
            $validated = app(CommentRequest::class)->validated(); 
            $item->comments()->create([
                'user_id' => $user->id,
                'comment' => $validated['comment'],
            ]);
        }
        return redirect()->route('item.show', $item->id);
    }
}


