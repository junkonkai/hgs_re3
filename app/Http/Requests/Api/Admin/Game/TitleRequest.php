<?php

namespace App\Http\Requests\Api\Admin\Game;

use App\Enums\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TitleRequest extends FormRequest
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
        if ($this->use_ogp_description === null) {
            $this->merge(['use_ogp_description' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            'game_franchise_id' => 'required_without:game_series_id|nullable|exists:game_franchises,id',
            'game_series_id' => 'required_without:game_franchise_id|nullable|exists:game_series,id',
            'name' => 'required|max:200',
            'key' => 'required|max:50',
            'phonetic' => 'required|max:200|regex:/^[あ-ん][ぁ-んー0-9]*/',
            'node_name' => 'required|max:200',
            'original_package_id' => 'nullable|exists:game_packages,id',
            'search_synonyms' => 'nullable|string',
            'description' => 'nullable|string',
            'description_source' => 'nullable',
            'first_release_int' => 'required|numeric|max:99999999',
            'use_ogp_description' => 'required|boolean',
            'rating' => ['required', new Enum(Rating::class)],
            'issue' => 'nullable',
        ];
    }
}
