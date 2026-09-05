<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン画面を表示できる(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('user.user-login');
    }

    public function test_正しい認証情報でログインでき、勤怠打刻画面に遷移する(): void
    {
        $user = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => 'password',
            'admin_status' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertAuthenticatedAs($user);
    }

    public function test_メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create(['email' => 'user1@example.com', 'password' => 'password']);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create(['email' => 'user1@example.com', 'password' => 'password']);

        $response = $this->post('/login', [
            'email' => 'user1@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_登録内容と一致しない場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create(['email' => 'user1@example.com', 'password' => 'password']);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function test_メールアドレスは合っているがパスワードが間違っている場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'user1@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_ログイン済みの一般ユーザーが再度ログイン画面にアクセスすると勤怠打刻画面にリダイレクトされる(): void
    {
        $user = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => 'password',
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/attendance');
    }

    public function test_一般ユーザーがログアウトするとログイン画面にリダイレクトされる(): void
    {
        $user = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => 'password',
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
