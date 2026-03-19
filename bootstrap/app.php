<?php

use App\Http\Middleware\CrossOriginHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // テスト用ルートを読み込み（開発環境のみ）
            if (app()->environment('local')) {
                require __DIR__.'/../routes/test-debugbar.php';
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request) {

            if ($request->routeIs('Admin.*')) {
                return route('Admin.Login');
            }

            if ($request->routeIs('User.MyNode.*')) {
                return route('Account.Login');
            }

            return route('Root');
        });

        $middleware->appendToGroup('admin', [
            \App\Http\Middleware\Admin::class,
            \App\Http\Middleware\AdminSearchBreadcrumb::class,
        ]);

        $middleware->alias([
            'gpts.api_key' => \App\Http\Middleware\GptsApiKeyMiddleware::class,
            'game_master.api' => \App\Http\Middleware\EnsureGameMasterApiTokenAbility::class,
        ]);

        // $middleware->append(CrossOriginHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // グローバル例外処理を呼び出し
            return \App\Http\Controllers\Controller::handleGlobalException($e, $request);
        });
    })->create();
