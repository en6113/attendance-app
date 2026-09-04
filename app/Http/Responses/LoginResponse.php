<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $redirectTo = Auth::user()->admin_status
            ? '/admin/attendance/list'
            : '/attendance';

        return redirect()->intended($redirectTo);
    }
}
