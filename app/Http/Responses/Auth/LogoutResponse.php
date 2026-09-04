<?php

namespace App\Http\Responses\Auth;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

/**
 * ログアウト後、それぞれのログイン画面へ遷移する。
 * どのURLにPOSTされたかで、一般ユーザーか管理者かを判断している。
 */
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        return redirect($request->is('admin/logout')
            ? '/admin/login'
            : '/login'
        );
    }
}
