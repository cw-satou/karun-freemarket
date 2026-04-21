<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Condition;
use App\Http\Requests\ExhibitionRequest;

class SellController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        $conditions = Condition::all();
        return view('exhibition', compact('categories', 'conditions'));
    }

    public function store(ExhibitionRequest $request)
    {
        $user = auth()->user();

        $imagePath = null;
        if ($request->hasFile('image_path')) {
            $imagePath = $request->file('image_path')->store('product_images', 'public');
        }
        $product = Product::create([
            'user_id'      => $user->id,
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
        return redirect()->route('items.index')->with('success', '商品を出品しました');
    }
}
