<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class IssueGameMasterApiToken extends Command
{
    /**
     * @var string
     */
    protected $signature = 'game-master:issue-token
                            {email : トークンを紐づける管理者ユーザーのメールアドレス}
                            {--name= : トークン表示名（省略時は GAME_MASTER_API_TOKEN_NAME 相当）}';

    /**
     * @var string
     */
    protected $description = 'ゲームマスターAPI用 Sanctum Personal Access Token を発行する（平文はこの一度だけ表示）';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("ユーザーが見つかりません: {$email}");

            return self::FAILURE;
        }

        if ($user->role !== UserRole::ADMIN) {
            $this->error('role が管理者（ADMIN）のユーザーのみトークンを発行できます。');

            return self::FAILURE;
        }

        $ability = config('game_master_api.token_ability');
        $tokenName = $this->option('name') ?: config('game_master_api.token_name_default');
        $token = $user->createToken($tokenName, [$ability]);

        $this->info('Authorization: Bearer に付与するトークンです。再表示できないため安全に保管してください。');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
