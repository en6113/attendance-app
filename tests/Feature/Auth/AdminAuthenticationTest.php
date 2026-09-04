<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ログイン画面を表示できる(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertViewIs('admin.admin-login');
    }

    public function test_正しい認証情報でログインでき勤怠一覧画面に遷移する(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/attendance/list');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create([
            'email' => 'user3@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create([
            'email' => 'user3@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'user3@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_登録内容と一致しない場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create([
            'email' => 'user3@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function test_メールアドレスは合っているがパスワードが間違っている場合バリデーションメッセージが表示される(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }

    public function test_ログイン済みの管理者が再度ログイン画面にアクセスすると勤怠一覧画面にリダイレクトされる(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/login');

        $response->assertRedirect('/admin/attendance/list');
    }

    public function test_管理者がログアウトすると管理者ログイン画面にリダイレクトされる(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_一般ユーザーが管理者ログインを利用すると弾かれる(): void
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'password' => 'password',
            'admin_status' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest();
    }
}
