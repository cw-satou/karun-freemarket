<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use App\Models\User;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録時に名前が入力されていない場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/register')->post('/register', [
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertSessionHasErrors([
                'name' => 'お名前を入力してください',
            ]);

            $response->assertRedirect('/register');
        }


    public function test_会員登録時にメールアドレスが入力されていない場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/register')->post('/register', [
                'name' => 'テストユーザー',
                'email' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください',
            ]);

            $response->assertRedirect('/register');
        }


    public function test_会員登録時にパスワードが入力されていない場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/register')->post('/register', [
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => '',
                'password_confirmation' => '',
            ]);

            $response->assertSessionHasErrors([
                'password' => 'パスワードを入力してください',
            ]);

            $response->assertRedirect('/register');
        }


    public function test_会員登録時にパスワードが7文字以下の場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/register')->post('/register', [
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => 'pass123',
                'password_confirmation' => 'pass123',
            ]);

            $response->assertSessionHasErrors([
                'password' => 'パスワードは8文字以上で入力してください',
            ]);

            $response->assertRedirect('/register');
        }


    public function test_会員登録時にパスワード確認と一致しない場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/register')->post('/register', [
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different123',
            ]);

            $response->assertSessionHasErrors([
                'password_confirmation' => 'パスワードと一致しません',
            ]);

            $response->assertRedirect('/register');
        }


    public function test_全ての項目が入力されている場合、会員情報が登録され、プロフィール設定画面に遷移される()
        {
            $userData = [
                'name' => 'テストユーザー',
                'email' => 'testuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = $this->post('/register', $userData);

            $this->assertDatabaseHas('users', [
                'email' => 'testuser@example.com',
                'name' => 'テストユーザー',
            ]);

            $response->assertRedirect(route('verification.notice'));
        }


    public function test_会員登録後に認証メールが送信される()
        {
            Notification::fake();

            $response = $this->post('/register', [
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);


            $user = User::where('email', 'test@example.com')->first();
            Notification::assertSentTo($user, VerifyEmail::class);

            $response->assertRedirect(route('verification.notice'));
        }


    public function test_メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する()
        {
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);

            $this->actingAs($user);

            $response = $this->get(route('verification.notice'));
            $response->assertStatus(200);

            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $response = $this->get($verifyUrl);

            $response->assertRedirect(route('profile.edit'));
        }


    public function test_メール認証サイトのメール認証を完了すると、プロフィール編集画面に遷移する()
        {

            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $response = $this->get($verificationUrl);

            $this->assertTrue($user->fresh()->hasVerifiedEmail());

            $response->assertRedirect('/mypage/profile');
        }


    public function test_ログイン時にメールアドレスが入力されていない場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
            ]);

            $response->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください',
            ]);

            $response->assertRedirect('/login');
        }


    public function test_ログイン時にパスワードが入力されていない場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
            ]);

            $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
            ]);

            $response->assertRedirect('/login');
        }


    public function test_ログイン時入力情報が間違っている場合、バリデーションメッセージが表示される()
        {
            $response = $this->from('/login')->post('/login', [
                'email' => 'notfound@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
            ]);

            $response->assertRedirect('/login');
        }


    public function test_正しい情報が入力された場合、ログイン処理が実行される()
        {
            $user = User::factory()->create([
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]);

            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);

            $this->assertAuthenticatedAs($user);

            $response->assertRedirect('/');
        }


    public function test_ログアウトができる()
        {
            $user = User::factory()->create();

            $this->actingAs($user);

            $response = $this->post('/logout');

            $this->assertGuest();

            $response->assertRedirect('/');
        }


}
