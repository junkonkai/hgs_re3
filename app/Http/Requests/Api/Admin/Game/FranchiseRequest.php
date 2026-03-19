<?php

namespace App\Http\Requests\Api\Admin\Game;

use Illuminate\Foundation\Http\FormRequest;

class FranchiseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:200',
            'key' => 'required|max:50',
            'phonetic' => 'required|max:200|regex:/^[あ-ん][ぁ-んー0-9]*/u',
            'node_name' => 'required|max:200',
            'description' => 'nullable',
            'description_source' => 'nullable',
        ];
    }
}
