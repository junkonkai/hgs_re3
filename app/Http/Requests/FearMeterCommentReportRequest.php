<?php

namespace App\Http\Requests;

class FearMeterCommentReportRequest extends BaseWebRequest
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
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
