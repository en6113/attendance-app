<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * 管理者ルート用のmiddleware
     *
     * 使用例: Route::middleware(['auth:web', 'admin'])->group(...)
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->admin_status, 403);

        return $next($request);
    }
}
