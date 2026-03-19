<?php

namespace App\Http\Requests\Api\Admin\Game;

use App\Enums\MediaMixType;
use App\Enums\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MediaMixRequest extends FormRequest
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
        $isPost = $this->isMethod('POST');
        $reqStr = $isPost ? 'required' : 'sometimes|required';
        $typeReq = $isPost
            ? ['required', new Enum(MediaMixType::class)]
            : ['sometimes', 'required', new Enum(MediaMixType::class)];
        $ratingReq = $isPost
            ? ['required', new Enum(Rating::class)]
            : ['sometimes', 'required', new Enum(Rating::class)];

        return [
            'type' => $typeReq,
            'name' => $reqStr . '|max:200',
            'key' => $reqStr . '|max:50',
            'node_name' => $reqStr . '|max:200',
            'game_franchise_id' => 'nullable|exists:game_franchises,id',
            'game_media_mix_group_id' => 'nullable|exists:game_media_mix_groups,id',
            'rating' => $ratingReq,
            'sort_order' => $reqStr . '|numeric',
            'description' => 'nullable',
            'description_source' => 'nullable',
            'use_ogp_description' => $reqStr . '|boolean',
        ];
    }
}
