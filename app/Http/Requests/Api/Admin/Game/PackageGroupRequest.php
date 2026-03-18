<?php

namespace App\Http\Requests\Api\Admin\Game;

use Illuminate\Foundation\Http\FormRequest;

class PackageGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:200',
            'node_name' => 'required|max:200',
            'sort_order' => 'required|integer',
            'description' => 'nullable',
            'description_source' => 'nullable',
            'simple_shop_text' => 'nullable',
        ];
    }
}
