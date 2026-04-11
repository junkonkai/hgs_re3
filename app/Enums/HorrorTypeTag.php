<?php
/**
 * ホラー種別タグ
 */

namespace App\Enums;

enum HorrorTypeTag: string
{
    case JumpScare     = 'jump_scare';
    case Psychological = 'psychological';
    case Gore          = 'gore';
    case Atmosphere    = 'atmosphere';
    case Supernatural  = 'supernatural';
    case Enclosed      = 'enclosed';
    case Chased        = 'chased';

    /**
     * テキストを取得
     *
     * @return string
     */
    public function text(): string
    {
        return match($this) {
            HorrorTypeTag::JumpScare     => 'ジャンプスケア',
            HorrorTypeTag::Psychological => '心理的恐怖',
            HorrorTypeTag::Gore          => 'グロテスク描写',
            HorrorTypeTag::Atmosphere    => '雰囲気・サスペンス',
            HorrorTypeTag::Supernatural  => '超自然現象',
            HorrorTypeTag::Enclosed      => '閉所・暗所',
            HorrorTypeTag::Chased        => '追いかけられる系',
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
