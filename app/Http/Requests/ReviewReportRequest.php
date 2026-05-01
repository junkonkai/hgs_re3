<?php

namespace App\Http\Requests;

class ReviewReportRequest extends BaseWebRequest
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
            'reason_types'   => ['nullable', 'array'],
            'reason_types.*' => ['string', 'max:50'],
            'reason_note'    => ['nullable', 'string', 'max:255'],
        ];
    }
}
