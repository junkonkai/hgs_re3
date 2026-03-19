<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GameMasterApiAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_without_token_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/admin/game/platforms');

        $response->assertUnauthorized();
    }

    public function test_token_without_ability_returns_forbidden(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createToken('no-ability', [])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/admin/game/platforms');

        $response->assertForbidden();
        $response->assertJsonFragment(['message' => 'この操作には有効なゲームマスターAPIトークンが必要です。']);
    }

    public function test_non_admin_token_returns_forbidden_even_with_ability(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $ability = config('game_master_api.token_ability');
        $token = $user->createToken('test', [$ability])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/admin/game/platforms?per_page=1');

        $response->assertForbidden();
        $response->assertJsonFragment(['message' => 'ゲームマスターAPIは管理者のトークンのみ利用できます。']);
    }
}
