<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sanctum Personal Access Token の ability
    |--------------------------------------------------------------------------
    |
    | /api/v1/admin/game/* で利用するトークンに付与する権限名です。
    | php artisan game-master:issue-token で発行するトークンにこの ability が付きます。
    |
    */
    'token_ability' => env('GAME_MASTER_API_TOKEN_ABILITY', 'game-master:access'),

    /*
    |--------------------------------------------------------------------------
    | トークン表示名（personal_access_tokens.name）
    |--------------------------------------------------------------------------
    */
    'token_name_default' => env('GAME_MASTER_API_TOKEN_NAME', 'game-master-api'),

];
