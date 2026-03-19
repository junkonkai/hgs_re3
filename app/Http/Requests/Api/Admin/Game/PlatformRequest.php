<?php

namespace App\Http\Requests\Api\Admin\Game;

use Illuminate\Foundation\Http\FormRequest;

class PlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->description === null) {
            $this->merge(['description' => '']);
        }
        if ($this->synonymsStr === null) {
            $this->merge(['synonymsStr' => '']);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:200',
            'key' => 'required|max:50',
            'acronym' => 'required|max:30',
            'node_name' => 'required|max:200',
            'type' => 'required|integer',
            'sort_order' => 'required|integer|min:0|max:99999999',
            'game_maker_id' => 'nullable|exists:game_makers,id',
            'description' => 'nullable|string',
            'description_source' => 'nullable',
            'synonymsStr' => 'nullable|string',
        ];
    }
}
