<?php

namespace App\Http\Responses\Auth;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        return redirect()->intended('/attendance');
    }
}
