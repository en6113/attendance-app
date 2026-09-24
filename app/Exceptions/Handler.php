<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 例外をHTTPレスポンスに変換する。api/* パスのModelNotFoundExceptionは統一形式のJSONにする。
     *
     * @param  Request  $request
     */
    public function render($request, Throwable $e): JsonResponse|Response
    {
        if ($request->is('api/*') && $e instanceof ModelNotFoundException) {
            return response()->json(['error' => '勤怠情報が見つかりませんでした。'], 404);
        }

        if ($request->is('api/*') && $e instanceof AuthorizationException) {
            return response()->json(['error' => 'この操作を実行する権限がありません。'], 403);
        }

        return parent::render($request, $e);
    }
}
