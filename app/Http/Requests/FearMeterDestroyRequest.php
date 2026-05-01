<?php

namespace App\Http\Requests;

class FearMeterDestroyRequest extends BaseWebRequest
{
    /**
     * ユーザーがこのリクエストの権限を持っているか
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title_key' => [
                'required',
                'string',
                'exists:game_titles,key',
            ],
            'from' => [
                'nullable',
                'string',
                'in:title-detail',
            ],
            'also_delete_review' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * バリデーション属性名
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title_key'           => 'タイトル',
            'from'                => '遷移元',
            'also_delete_review'  => 'レビューも削除',
        ];
    }
}
