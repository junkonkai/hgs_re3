<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGameMasterApiTokenAbility
{
    /**
     * Sanctum で認証済みかつ、管理者＋ゲームマスターAPI用 ability であることを検証する。
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ability = config('game_master_api.token_ability');

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== UserRole::ADMIN) {
            return response()->json([
                'message' => 'ゲームマスターAPIは管理者のトークンのみ利用できます。',
            ], 403);
        }

        if (! $user->tokenCan($ability)) {
            return response()->json([
                'message' => 'この操作には有効なゲームマスターAPIトークンが必要です。',
            ], 403);
        }

        return $next($request);
    }
}
