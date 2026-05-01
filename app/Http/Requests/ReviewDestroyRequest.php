<?php

namespace App\Http\Requests;

class ReviewDestroyRequest extends BaseWebRequest
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
            'also_delete_fear_meter' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title_key'             => 'タイトル',
            'also_delete_fear_meter' => '怖さメーターも削除',
        ];
    }
}
