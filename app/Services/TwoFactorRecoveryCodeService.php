<?php

namespace App\Services;

use App\Models\TwoFactorRecoveryCode;
use App\Models\User;

class TwoFactorRecoveryCodeService
{
    private const CODE_COUNT = 8;

    /**
     * リカバリーコードを新規生成して保存し、平文コードの配列を返す
     *
     * @return string[]
     */
    public function generate(User $user): array
    {
        TwoFactorRecoveryCode::where('user_id', $user->id)->delete();

        $plainCodes = [];
        $records = [];

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $plain = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $plainCodes[] = $plain;
            $records[] = [
                'user_id'    => $user->id,
                'code'       => hash('sha256', $plain),
                'used_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TwoFactorRecoveryCode::insert($records);

        return $plainCodes;
    }

    /**
     * リカバリーコードを照合し、一致したコードを使用済みにする
     */
    public function verify(User $user, string $plainCode): bool
    {
        $hashed = hash('sha256', $plainCode);

        $record = TwoFactorRecoveryCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('code', $hashed)
            ->first();

        if (!$record) {
            return false;
        }

        $record->used_at = now();
        $record->save();

        return true;
    }

    /**
     * 未使用コードの残数を返す
     */
    public function remainingCount(User $user): int
    {
        return TwoFactorRecoveryCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->count();
    }
}
