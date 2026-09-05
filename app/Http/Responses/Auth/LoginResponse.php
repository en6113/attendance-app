<?php

namespace App\Http\Responses\Auth;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $redirectTo = $request->is('admin/login')
            ? '/admin/attendance/list'
            : '/attendance';

        return redirect()->intended($redirectTo);
    }
}
