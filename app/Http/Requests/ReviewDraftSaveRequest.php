<?php

namespace App\Http\Requests;

use App\Enums\FearMeter;
use App\Enums\HorrorTypeTag;
use App\Enums\PlayStatus;
use App\Enums\PlayTime;
use Illuminate\Validation\Rule;

class ReviewDraftSaveRequest extends BaseWebRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_key' => [
                'required',
                'string',
                'exists:game_titles,key',
            ],
            'play_status' => [
                'nullable',
                'string',
                Rule::enum(PlayStatus::class),
            ],
            'play_time' => [
                'nullable',
                'string',
                Rule::enum(PlayTime::class),
            ],
            'body' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'has_spoiler' => [
                'nullable',
                'boolean',
            ],
            'score_story' => [
                'nullable',
                'integer',
                'min:0',
                'max:4',
            ],
            'score_atmosphere' => [
                'nullable',
                'integer',
                'min:0',
                'max:4',
            ],
            'score_gameplay' => [
                'nullable',
                'integer',
                'min:0',
                'max:4',
            ],
            'user_score_adjustment' => [
                'nullable',
                'integer',
                'min:-20',
                'max:20',
            ],
            'packages' => [
                'nullable',
                'array',
            ],
            'packages.*' => [
                'integer',
                'exists:game_packages,id',
            ],
            'horror_type_tags' => [
                'nullable',
                'array',
            ],
            'horror_type_tags.*' => [
                'string',
                Rule::enum(HorrorTypeTag::class),
            ],
            'fear_meter' => [
                'nullable',
                'integer',
                Rule::enum(FearMeter::class),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title_key'            => 'タイトル',
            'play_status'          => 'プレイ状況',
            'play_time'            => 'プレイ時間',
            'body'                 => 'レビュー本文',
            'has_spoiler'          => 'ネタバレフラグ',
            'score_story'          => 'ストーリー評価',
            'score_atmosphere'     => '雰囲気・演出評価',
            'score_gameplay'       => 'ゲーム性評価',
            'user_score_adjustment' => 'スコア調整',
            'packages'             => 'プレイ環境',
            'horror_type_tags'     => 'ホラー種別タグ',
            'fear_meter'           => '怖さメーター',
        ];
    }
}
