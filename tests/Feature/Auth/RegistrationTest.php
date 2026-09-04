<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validParams(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_名前が未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validParams(['name' => '']));

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validParams(['email' => '']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワードが8文字未満の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validParams([
            'password' => 'pass1',
            'password_confirmation' => 'pass1',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_パスワードが一致しない場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validParams([
            'password' => 'password',
            'password_confirmation' => 'different',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    public function test_パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        $response = $this->post('/register', $this->validParams([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_フォームに内容が入力されていた場合データが正常に保存される(): void
    {
        $this->post('/register', $this->validParams());

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    public function test_登録画面を表示できる(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('user.register');
    }
}
