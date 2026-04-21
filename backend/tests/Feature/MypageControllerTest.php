<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use App\Models\Order;


class MypageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザー情報を取得できる()
    {
        $user = User::factory()->create(['name' => 'テスト太郎']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'image_path' => 'profile_dummy.png',
        ]);

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'item_name' => '出品テスト商品',
            'image_path' => 'product_dummy_sell.png',
        ]);

        $seller = User::factory()->create();
        $purchasedProduct = Product::factory()->create([
            'user_id' => $seller->id,
            'item_name' => '購入テスト商品',
            'image_path' => 'product_dummy_buy.png',
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $purchasedProduct->id,
        ]);

        $this->actingAs($user);

        $response = $this->get('/mypage?page=sell');
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('<div class="mypage__profile-image--default"></div>', false);
        $response->assertSee($product->item_name);
        $response->assertSee('storage/' . $product->image_path, false);


        $response = $this->get('/mypage?page=buy');
        $response->assertStatus(200);
        $response->assertSee($purchasedProduct->item_name);
        $response->assertSee('storage/' . $purchasedProduct->image_path, false);
    }


    public function test_プロフィール編集画面に初期値が表示される()
    {

        $user = User::factory()->create(['name' => 'テスト太郎']);

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'image_path' => 'profile_images/test.png',
        ]);
        Storage::shouldReceive('exists')
            ->with('public/' . $profile->image_path)
            ->andReturn(true);

        $this->actingAs($user);

        $response = $this->get('/mypage/profile');
        $response->assertStatus(200);

        $response->assertSee('テスト太郎');
        $response->assertSee('123-4567');
        $response->assertSee('東京都渋谷区');
        $response->assertSee('テストビル101');
        $response->assertSee('<img src="' . asset('storage/' . $profile->image_path) . '"', false);
    }
}
