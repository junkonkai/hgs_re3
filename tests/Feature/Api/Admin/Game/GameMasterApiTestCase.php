<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

abstract class GameMasterApiTestCase extends TestCase
{
    use DatabaseTransactions;

    protected User $gameMasterApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gameMasterApiUser = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $ability = config('game_master_api.token_ability');
        $token = $this->gameMasterApiUser->createToken(
            config('game_master_api.token_name_default'),
            [$ability]
        );

        $this->withToken($token->plainTextToken);
    }
}
