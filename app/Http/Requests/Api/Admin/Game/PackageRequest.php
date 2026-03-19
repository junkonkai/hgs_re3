<?php

namespace App\Http\Requests\Api\Admin\Game;

use App\Enums\ProductDefaultImage;
use App\Enums\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->node_name === null) {
            $this->merge(['node_name' => '']);
        }
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('POST');
        $reqStr = $isPost ? 'required' : 'sometimes|required';
        $enumReq = $isPost
            ? ['required', new Enum(ProductDefaultImage::class)]
            : ['sometimes', 'required', new Enum(ProductDefaultImage::class)];
        $ratingReq = $isPost
            ? ['required', new Enum(Rating::class)]
            : ['sometimes', 'required', new Enum(Rating::class)];

        return [
            'name' => $reqStr . '|max:200',
            'acronym' => 'nullable|max:30',
            'node_name' => 'nullable|max:200',
            'game_platform_id' => $reqStr . '|exists:game_platforms,id',
            'game_maker_ids' => 'nullable|array',
            'game_maker_ids.*' => 'integer|exists:game_makers,id',
            'release_at' => $reqStr . '|max:100',
            'sort_order' => $reqStr . '|integer',
            'default_img_type' => $enumReq,
            'rating' => $ratingReq,
        ];
    }
}
