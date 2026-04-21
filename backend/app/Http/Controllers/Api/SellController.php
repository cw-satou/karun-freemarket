<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Condition;
use App\Models\Product;
use Illuminate\Http\Request;

class SellController extends Controller
{
    public function formData()
    {
        return response()->json([
            'categories' => Category::all(['id', 'category']),
            'conditions' => Condition::all(['id', 'condition']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name'    => 'required|string|max:255',
            'description'  => 'required|string',
            'price'        => 'required|integer|min:0',
            'condition_id' => 'required|exists:conditions,id',
            'brand'        => 'nullable|string|max:255',
            'categories'   => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'image'        => 'nullable|image|max:4096',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('product_images', 'public');
        }

        $product = Product::create([
            'user_id'      => $request->user()->id,
            'condition_id' => $request->condition_id,
            'item_name'    => $request->item_name,
            'brand'        => $request->brand,
            'description'  => $request->description,
            'price'        => $request->price,
            'image_path'   => $imagePath,
        ]);

        if ($request->categories) {
            $product->categories()->attach($request->categories);
        }

        return response()->json([
            'product' => [
                'id'        => $product->id,
                'item_name' => $product->item_name,
                'price'     => $product->price,
                'image_url' => $imagePath ? asset('storage/' . $imagePath) : null,
            ],
        ], 201);
    }
}
