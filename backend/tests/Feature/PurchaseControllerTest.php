<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Stripe\Checkout\Session as StripeSession;
use Mockery;



class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;


    public function test_購入ボタンを押下すると購入が完了する()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $mock = Mockery::mock('overload:' . StripeSession::class);
        $mock->shouldReceive('create')->andReturn((object)[
            'url' => '/dummy-stripe-url'
        ]);

        $response = $this->actingAs($user)->post(route('purchase.store', $product->id), [
            'payment_method' => 'card',
            'sending_postcode' => '123-4567',
            'sending_address' => '東京都渋谷区1-1-1',
            'sending_building' => 'テストビル101',
        ]);

        $response->assertRedirect('/dummy-stripe-url');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'payment_method' => 'card',
        ]);
    }


    public function test_購入した商品は一覧でsoldと表示される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $mock = Mockery::mock('overload:' . \Stripe\Checkout\Session::class);
        $mock->shouldReceive('create')->andReturn((object)[
            'url' => '/dummy-stripe-url'
        ]);

        $this->actingAs($user)->post(route('purchase.store', $product->id), [
            'payment_method' => 'card',
            'sending_postcode' => '123-4567',
            'sending_address' => '東京都渋谷区1-1-1',
            'sending_building' => 'テストビル101',
        ]);

        $response = $this->get('/');
        $response->assertSee('Sold');
    }


    public function test_購入した商品がプロフィール購入一覧に追加される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $mock = Mockery::mock('overload:' . StripeSession::class);
        $mock->shouldReceive('create')->andReturn((object)[
            'url' => '/dummy-stripe-url'
        ]);

        $this->actingAs($user)->post(route('purchase.store', $product->id), [
            'payment_method' => 'card',
            'sending_postcode' => '123-4567',
            'sending_address' => '東京都渋谷区1-1-1',
            'sending_building' => 'テストビル101',
        ]);

        $response = $this->actingAs($user)->get(route('mypage.index', ['page' => 'buy']));

        $response->assertStatus(200);
        $response->assertSee($product->item_name);
    }


    public function test_支払い方法選択が小計画面に反映される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $mock = Mockery::mock('overload:' . StripeSession::class);
        $mock->shouldReceive('create')->andReturn((object)[
            'url' => '/dummy-stripe-url'
        ]);

        $responseConvenience = $this->actingAs($user)->post(route('purchase.store', $product->id), [
            'payment_method' => 'convenience',
            'sending_postcode' => '123-4567',
            'sending_address' => '東京都渋谷区1-1-1',
            'sending_building' => 'テストビル101',
        ]);
        $responseConvenience->assertRedirect('/dummy-stripe-url');

        $this->assertEquals('convenience', session('payment_method.' . $product->id));

        $responsePage = $this->actingAs($user)->get(route('purchase.show', $product->id));
        $responsePage->assertSee('コンビニ払い');

        $product2 = Product::factory()->create();
        $responseCard = $this->actingAs($user)->post(route('purchase.store', $product2->id), [
            'payment_method' => 'card',
            'sending_postcode' => '987-6543',
            'sending_address' => '東京都新宿区1-1-1',
            'sending_building' => 'テストビル202',
        ]);
        $responseCard->assertRedirect('/dummy-stripe-url');

        $this->assertEquals('card', session('payment_method.' . $product2->id));

        $responsePage2 = $this->actingAs($user)->get(route('purchase.show', $product2->id));
        $responsePage2->assertSee('カード払い');
    }


    public function test_送付先住所変更画面にて登録した住所が商品購入画面に反映されている()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user);

        $this->get(route('purchase.address.edit', $product->id))
            ->assertStatus(200);

        $responseUpdate = $this->post(route('purchase.address.update', $product->id), [
            'sending_postcode' => '123-4567',
            'sending_address'  => '東京都渋谷区1-1-1',
            'sending_building' => 'テストビル101',
        ]);

        $responseUpdate->assertRedirect(route('purchase.show', $product->id));


        $responsePurchase = $this->get(route('purchase.show', $product->id));
        $responsePurchase->assertSee('〒123-4567');
        $responsePurchase->assertSee('東京都渋谷区1-1-1');
        $responsePurchase->assertSee('テストビル101');
    }


    public function test_購入した商品に送付先住所が紐づいて登録される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user);

        $mock = \Mockery::mock('overload:' . \Stripe\Checkout\Session::class);
        $mock->shouldReceive('create')->andReturn((object)[
            'url' => '/dummy-stripe-url'
        ]);

        $this->withSession([
            'sending_postcode' => '987-6543',
            'sending_address'  => '東京都新宿区1-1-1',
            'sending_building' => 'テストビル202',
        ])->post(route('purchase.store', $product->id), [
            'payment_method'   => 'card',
            'sending_postcode' => '987-6543',
            'sending_address'  => '東京都新宿区1-1-1',
            'sending_building' => 'テストビル202',
        ])->assertRedirect('/dummy-stripe-url');

        $order = $product->order;
        $this->assertNotNull($order);
        $this->assertEquals('987-6543', $order->sending_postcode);
        $this->assertEquals('東京都新宿区1-1-1', $order->sending_address);
        $this->assertEquals('テストビル202', $order->sending_building);

    }
}
