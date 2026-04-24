<?php

namespace App\Http\Requests;

use App\Enums\FearMeter;
use Illuminate\Validation\Rule;

class FearMeterDraftSaveRequest extends BaseWebRequest
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
            'fear_meter' => [
                'nullable',
                'integer',
                Rule::enum(FearMeter::class),
            ],
            'comment' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title_key'  => 'タイトル',
            'fear_meter' => '怖さメーター',
            'comment'    => '一言コメント',
        ];
    }
}
