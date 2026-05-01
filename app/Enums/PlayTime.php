<?php
/**
 * プレイ時間
 */

namespace App\Enums;

enum PlayTime: string
{
    case Under1Hour  = 'under_1h';
    case Hour1To3    = '1h_to_3h';
    case Hour3To5    = '3h_to_5h';
    case Hour5To10   = '5h_to_10h';
    case Hour10To20  = '10h_to_20h';
    case Over20Hours = 'over_20h';

    /**
     * テキストを取得
     *
     * @return string
     */
    public function text(): string
    {
        return match($this) {
            PlayTime::Under1Hour  => '1時間未満',
            PlayTime::Hour1To3    => '1〜3時間',
            PlayTime::Hour3To5    => '3〜5時間',
            PlayTime::Hour5To10   => '5〜10時間',
            PlayTime::Hour10To20  => '10〜20時間',
            PlayTime::Over20Hours => '20時間以上',
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
