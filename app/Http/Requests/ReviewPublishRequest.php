<?php

namespace App\Http\Requests;

use App\Enums\FearMeter;
use App\Enums\HorrorTypeTag;
use App\Enums\PlayStatus;
use Illuminate\Validation\Rule;

class ReviewPublishRequest extends BaseWebRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => $this->input('body') ?? '',
        ]);
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
                'required',
                'string',
                Rule::enum(PlayStatus::class),
            ],
            'body' => [
                'string',
                'max:2000',
            ],
            'has_spoiler' => [
                'nullable',
                'boolean',
            ],
            'score_story' => [
                'required',
                'integer',
                'in:0,5,10,15,20',
            ],
            'score_atmosphere' => [
                'required',
                'integer',
                'in:0,5,10,15,20',
            ],
            'score_gameplay' => [
                'required',
                'integer',
                'in:0,5,10,15,20',
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
                'required',
                'integer',
                Rule::enum(FearMeter::class),
            ],
            'fear_meter_comment' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title_key'            => 'タイトル',
            'play_status'          => 'プレイ状況',
            'body'                 => 'レビュー本文',
            'has_spoiler'          => 'ネタバレフラグ',
            'score_story'          => 'ストーリー評価',
            'score_atmosphere'     => '雰囲気・演出評価',
            'score_gameplay'       => 'ゲーム性評価',
            'user_score_adjustment' => 'スコア調整',
            'packages'             => 'プレイ環境',
            'horror_type_tags'     => 'ホラー種別タグ',
            'fear_meter'           => '怖さメーター',
            'fear_meter_comment'   => '怖さメーターコメント',
        ];
    }
}
