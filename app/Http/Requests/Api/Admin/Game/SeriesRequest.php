<?php

namespace App\Http\Requests\Api\Admin\Game;

use Illuminate\Foundation\Http\FormRequest;

class SeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_franchise_id' => 'required|exists:game_franchises,id',
            'name' => 'required|max:200',
            'phonetic' => 'required|max:200|regex:/^[あ-ん][ぁ-んー0-9]*/u',
            'node_name' => 'required|max:200',
            'description' => 'nullable',
            'description_source' => 'nullable',
        ];
    }
}
