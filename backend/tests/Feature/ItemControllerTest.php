<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Condition;
use App\Models\Category;
use App\Models\Order;
use App\Models\Comment;
use App\Models\Profile;




class ItemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_全商品を取得できる()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '良好']);
        $category = Category::factory()->create(['category' => 'アクセサリー']);

        $products = Product::factory()
            ->count(3)
            ->create([
                'user_id' => $user->id,
                'condition_id' => $condition->id,
            ])
            ->each(fn($product) => $product->categories()->attach($category->id));

        $response = $this->get('/');

        $response->assertStatus(200);

        foreach ($products as $product) {
            $response->assertSee($product->item_name);
            $response->assertSee('storage/' . $product->image_path);
        }
    }


    public function test_購入済み商品は_sold_と表示される()
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '良好']);
        $category = Category::factory()->create(['category' => 'アクセサリー']);

        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'item_name' => '腕時計',
        ]);
        $product->categories()->attach($category->id);

        Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sold', false);
    }


    public function test_自分が出品した商品は表示されない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '良好']);
        $category = Category::factory()->create(['category' => '家電']);

        $myProduct = Product::factory()->create([
            'user_id' => $user->id,
            'condition_id' => $condition->id,
            'item_name' => '自分のノートPC',
        ]);
        $myProduct->categories()->attach($category->id);

        $otherProduct = Product::factory()->create([
            'user_id' => $otherUser->id,
            'condition_id' => $condition->id,
            'item_name' => '他人のスマホ',
        ]);
        $otherProduct->categories()->attach($category->id);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertDontSee($myProduct->item_name);
        $response->assertSee($otherProduct->item_name);
    }


    public function test_いいねした商品だけが表示される()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '良好']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()->create([
            'user_id' => $otherUser->id,
            'condition_id' => $condition->id,
            'item_name' => 'いいね対象商品',
        ]);
        $product->categories()->attach($category->id);

        $user->favoriteProducts()->attach($product->id);

        $response = $this->actingAs($user)->get('/?tab=mylist');

        $response->assertStatus(200);
        $response->assertSee($product->item_name);
    }


    public function test_購入済み商品はマイリストにもSoldと表示される()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $condition = Condition::factory()->create();
        $category = Category::factory()->create();

        $product = Product::factory()
            ->for($seller, 'seller')
            ->for($condition)
            ->create();
        $product->categories()->attach($category->id);

        Order::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        $buyer->favoriteProducts()->attach($product->id);

        $response = $this->actingAs($buyer)->get('/?tab=mylist');

        $response->assertStatus(200);
        $response->assertSee('Sold', false);
    }


    public function test_未認証の場合は何も表示されない()
    {
        $response = $this->get('/?tab=mylist');
        $response->assertStatus(200);
        $response->assertSeeText('');
    }


    public function test_商品名で部分一致検索ができる()
    {
    $user = User::factory()->create();
    $condition = Condition::factory()->create(['name' => '新品']);
    $category = Category::factory()->create(['category' => '家電']);

    $product = Product::factory()
        ->for($user, 'seller')
        ->for($condition)
        ->create([
            'item_name' => 'MacBook Pro',
            'image_path' => 'products/macbook.jpg',
            'price' => 200000,
            'brand' => 'Apple',
            'description' => 'テスト用商品',
        ]);

    $product->categories()->attach($category->id);

    $response = $this->get('/?keyword=Mac');

    $response->assertStatus(200);
    $response->assertSee('MacBook Pro');
    }


    public function test_検索状態がマイリストでも保持されている()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/?keyword=Mac');
        $response->assertStatus(200);

        $response = $this->get('/?tab=mylist&keyword=Mac');
        $response->assertStatus(200);

        $response->assertSee('Mac');
    }


    public function test_商品詳細ページに必要な情報が表示される()
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create([
            'image_path' => 'profiles/test.png',
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
            'building' => 'テストビル101',
        ]);

        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'これはテスト用の商品です。',
            ]);

        $product->categories()->attach($category->id);

        $commentUser = User::factory()->create(['name' => 'コメントユーザー']);
        Profile::factory()->for($commentUser)->create([
            'image_path' => 'profiles/commenter.png',
            'postal_code' => '000-0000',
            'address' => 'テスト市テスト町',
            'building' => 'テストビル',
        ]);

        Comment::factory()
            ->for($product)
            ->for($commentUser)
            ->create([
                'comment' => 'これはテストコメントです。',
            ]);

        $product->favoritedByUsers()->attach($user->id);

        $response = $this->get(route('item.show', $product->id));

        $response->assertStatus(200);

        $response->assertSee('storage/' . $product->image_path, false);
        $response->assertSee('alt="商品画像"', false);
        $response->assertSee('テスト商品');
        $response->assertSee('テストブランド');
        $response->assertSee('これはテスト用の商品です。');
        $response->assertSee('家電');
        $response->assertSee('新品');
        $response->assertSee(number_format($product->price));
        $response->assertSee('コメントユーザー');
        $response->assertSee('これはテストコメントです。');
        $response->assertSee('<div class="product__user-icon--default"></div>', false);

        $response->assertSeeInOrder([
            '<span class="product__favorite-count">1</span>',
            '<span class="product__comment-count">1</span>',
        ], false);
    }


    public function test_複数カテゴリが商品詳細ページに表示される()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);

        $category1 = Category::factory()->create(['category' => '家電']);
        $category2 = Category::factory()->create(['category' => 'パソコン']);
        $category3 = Category::factory()->create(['category' => '周辺機器']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品複数カテゴリ',
                'image_path' => 'products/test_multi.png',
                'price' => 50000,
                'brand' => 'テストブランド',
                'description' => '複数カテゴリの商品です。',
            ]);

        $product->categories()->attach([$category1->id, $category2->id, $category3->id]);

        $response = $this->get(route('item.show', $product->id));
        $response->assertStatus(200);
        $response->assertSee('<span class="product__category">家電</span>', false);
        $response->assertSee('<span class="product__category">パソコン</span>', false);
        $response->assertSee('<span class="product__category">周辺機器</span>', false);
    }


    public function test_いいねアイコンを押下することによって、いいねした商品として登録することができる。()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'これはテスト用の商品です。',
            ]);
        $product->categories()->attach($category->id);

        $response = $this->actingAs($user)
            ->post(route('item.store', $product->id), [
                'action' => 'toggle_favorite',
            ]);

        $response->assertRedirect(route('item.show', $product->id));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->get(route('item.show', $product->id))
            ->assertSee('1');
    }


    public function test_追加済みのアイコンは色が変化する()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'これはテスト用の商品です。',
            ]);
        $product->categories()->attach($category->id);

        $product->favoritedByUsers()->attach($user->id);

        $response = $this->actingAs($user)
            ->get(route('item.show', $product->id));

        $response->assertStatus(200);
        $response->assertSee('class="star-icon active"', false);
        $response->assertSee('1');
    }


    public function test_再度いいねアイコンを押下することによって、いいねを解除することができる()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'これはテスト用の商品です。',
            ]);
        $product->categories()->attach($category->id);
        $product->favoritedByUsers()->attach($user->id);

        $response = $this->actingAs($user)
            ->post(route('item.store', $product->id), [
                'action' => 'toggle_favorite',
            ]);
        $response->assertRedirect(route('item.show', $product->id));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        $this->actingAs($user)
            ->get(route('item.show', $product->id))
            ->assertSee((string)0);
    }


    public function test_ログイン済みのユーザーはコメントを送信できる()
    {
        $user = User::factory()->create();

        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'テスト用商品です。',
            ]);
        $product->categories()->attach($category->id);


        $this->actingAs($user)
            ->post(route('item.store', $product->id), [
                'action' => 'comment',
                'comment' => 'これはテストコメントです。',
            ])
            ->assertRedirect(route('item.show', $product->id));

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'comment' => 'これはテストコメントです。',
        ]);

        $response = $this->actingAs($user)->get(route('item.show', $product->id));
        $response->assertSeeText('これはテストコメントです。');
    }


    public function test_ログイン前のユーザーはコメントを送信できない()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'テスト用商品です。',
            ]);

        $product->categories()->attach($category->id);

        $response = $this->post(route('item.store', $product->id), [
            'action' => 'comment',
            'comment' => 'ログイン前コメント',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('comments', [
            'product_id' => $product->id,
            'comment' => 'ログイン前コメント',
        ]);
    }


    public function test_コメントが入力されていない場合、バリデーションメッセージが表示される()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'テスト用商品です。',
            ]);

        $product->categories()->attach($category->id);

        $response = $this->actingAs($user)->post(route('item.store', $product->id), [
            'action' => 'comment',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment']);

        $this->assertDatabaseMissing('comments', [
            'product_id' => $product->id,
            'comment' => '',
        ]);
    }


    public function test_コメントが255字以上の場合、バリデーションメッセージが表示される()
    {
        $user = User::factory()->create();
        $condition = Condition::factory()->create(['name' => '新品']);
        $category = Category::factory()->create(['category' => '家電']);

        $product = Product::factory()
            ->for($user, 'seller')
            ->for($condition)
            ->create([
                'item_name' => 'テスト商品',
                'image_path' => 'products/test.png',
                'price' => 10000,
                'brand' => 'テストブランド',
                'description' => 'テスト用商品です。',
            ]);

        $product->categories()->attach($category->id);
        $longComment = str_repeat('あ', 256);

        $response = $this->actingAs($user)->post(route('item.store', $product->id), [
            'action' => 'comment',
            'comment' => $longComment,
        ]);

        $response->assertSessionHasErrors(['comment']);

        $this->assertDatabaseMissing('comments', [
            'product_id' => $product->id,
            'comment' => $longComment,
        ]);
    }

}
