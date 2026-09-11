<?php

namespace App\Http\Responses\Auth;

use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;

class EmailVerificationNotificationSentResponse implements EmailVerificationNotificationSentResponseContract
{
    public function toResponse($request)
    {
        return back()->with('message', '認証メールを再送しました');
    }
}
