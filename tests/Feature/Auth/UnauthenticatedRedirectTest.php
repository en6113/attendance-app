<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthenticatedRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインで保護ページにアクセスするとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/attendance');

        $response->assertRedirect('/login');
    }
}
