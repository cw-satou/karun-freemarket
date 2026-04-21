<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Condition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SellControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品出品情報が保存できる()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $category = Category::factory()->create();
        $condition = Condition::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('sell.create'));
        $response->assertStatus(200);
        $response->assertSee($category->category);
        $response->assertSee($condition->name);

        $file = UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg');

        $postData = [
            'categories'   => [$category->id],
            'condition_id' => $condition->id,
            'item_name'    => 'テスト商品',
            'brand'        => 'テストブランド',
            'description'  => '商品の説明です',
            'price'        => 5000,
            'image_path'   => $file,
        ];

        $response = $this->post(route('sell.store'), $postData);
        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('products', [
            'user_id'    => $user->id,
            'item_name'  => 'テスト商品',
            'brand'      => 'テストブランド',
            'description'=> '商品の説明です',
            'price'      => 5000,
        ]);

        $product = Product::first();
        $this->assertTrue($product->categories->contains($category->id));
       /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        /** @phpstan-ignore-next-line */
        $disk->assertExists($product->image_path);
    }
}
