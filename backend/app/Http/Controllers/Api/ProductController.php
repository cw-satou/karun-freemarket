<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommend');
        $keyword = $request->query('keyword');

        if ($tab === 'mylist') {
            if (!Auth::check()) {
                return response()->json(['products' => []]);
            }
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $products = $user->favoriteProducts()
                ->where('products.user_id', '!=', $user->id)
                ->with(['order', 'categories', 'condition'])
                ->searchByName($keyword)
                ->latest()
                ->get();
        } else {
            $query = Product::with(['order', 'categories', 'condition']);
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
            $products = $query->searchByName($keyword)->latest()->get();
        }

        return response()->json([
            'products' => $products->map(fn($p) => $this->formatProduct($p)),
        ]);
    }

    public function show(Product $product)
    {
        $product->load([
            'condition',
            'categories',
            'comments.user.profile',
            'favoritedByUsers',
            'seller.profile',
        ]);

        $isFavorited = Auth::check()
            ? $product->favoritedByUsers->contains('id', Auth::id())
            : false;

        return response()->json([
            'product' => array_merge($this->formatProduct($product), [
                'description'    => $product->description,
                'brand'          => $product->brand,
                'condition'      => $product->condition?->only(['id', 'condition']),
                'categories'     => $product->categories->map->only(['id', 'category']),
                'comments'       => $product->comments->map(fn($c) => [
                    'id'         => $c->id,
                    'comment'    => $c->comment,
                    'created_at' => $c->created_at,
                    'user'       => [
                        'id'               => $c->user->id,
                        'name'             => $c->user->name,
                        'profile_image_url' => $c->user->profile?->image_path
                            ? asset('storage/' . $c->user->profile->image_path)
                            : null,
                    ],
                ]),
                'favorites_count' => $product->favoritedByUsers->count(),
                'is_favorited'    => $isFavorited,
                'seller'          => [
                    'id'               => $product->seller->id,
                    'name'             => $product->seller->name,
                    'profile_image_url' => $product->seller->profile?->image_path
                        ? asset('storage/' . $product->seller->profile->image_path)
                        : null,
                ],
            ]),
        ]);
    }

    public function toggleFavorite(Request $request, Product $product)
    {
        $user = $request->user();
        if ($product->favoritedByUsers()->where('user_id', $user->id)->exists()) {
            $product->favoritedByUsers()->detach($user->id);
            $favorited = false;
        } else {
            $product->favoritedByUsers()->attach($user->id);
            $favorited = true;
        }
        return response()->json([
            'favorited'       => $favorited,
            'favorites_count' => $product->favoritedByUsers()->count(),
        ]);
    }

    public function storeComment(Request $request, Product $product)
    {
        $request->validate(['comment' => 'required|string|max:255']);
        $comment = $product->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
        ]);
        $comment->load('user.profile');
        return response()->json([
            'comment' => [
                'id'         => $comment->id,
                'comment'    => $comment->comment,
                'created_at' => $comment->created_at,
                'user'       => [
                    'id'               => $comment->user->id,
                    'name'             => $comment->user->name,
                    'profile_image_url' => $comment->user->profile?->image_path
                        ? asset('storage/' . $comment->user->profile->image_path)
                        : null,
                ],
            ],
        ], 201);
    }

    private function formatProduct(Product $p): array
    {
        return [
            'id'         => $p->id,
            'item_name'  => $p->item_name,
            'price'      => $p->price,
            'image_url'  => $p->image_path ? asset('storage/' . $p->image_path) : null,
            'is_sold'    => $p->is_sold,
        ];
    }
}
