<?php

namespace App\Http\Requests\Api\Admin\Game;

use App\Enums\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MakerRequest extends FormRequest
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
            'name' => 'required|max:100',
            'key' => 'required|max:50',
            'node_name' => 'required|max:200',
            'rating' => ['required', new Enum(Rating::class)],
            'type' => 'required|integer',
            'related_game_maker_id' => 'nullable|exists:game_makers,id',
            'synonymsStr' => 'nullable|string',
            'description' => 'nullable',
            'description_source' => 'nullable',
        ];
    }
}

