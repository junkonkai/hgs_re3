<?php
/**
 * プレイ状況
 */

namespace App\Enums;

enum PlayStatus: string
{
    case Cleared = 'cleared';
    case Playing = 'playing';
    case Watched = 'watched';

    /**
     * テキストを取得
     *
     * @return string
     */
    public function text(): string
    {
        return match($this) {
            PlayStatus::Cleared => 'クリア済み',
            PlayStatus::Playing => '未クリア',
            PlayStatus::Watched => '配信・動画で視聴',
        };
    }

    /**
     * input[type=select]に渡す用のリスト作成
     *
     * @param array $prepend
     * @return string[]
     */
    public static function selectList(array $prepend = []): array
    {
        $result = $prepend;

        foreach (self::cases() as $case) {
            $result[$case->value] = $case->text();
        }

        return $result;
    }
}
