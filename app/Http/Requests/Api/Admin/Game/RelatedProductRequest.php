<?php

namespace App\Http\Requests\Api\Admin\Game;

use App\Enums\ProductDefaultImage;
use App\Enums\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RelatedProductRequest extends FormRequest
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
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('POST');
        $reqStr = $isPost ? 'required' : 'sometimes|required';
        $ratingReq = $isPost
            ? ['required', new Enum(Rating::class)]
            : ['sometimes', 'required', new Enum(Rating::class)];
        $imgReq = $isPost
            ? ['required', new Enum(ProductDefaultImage::class)]
            : ['sometimes', 'required', new Enum(ProductDefaultImage::class)];

        return [
            'name' => $reqStr . '|max:200',
            'node_name' => $reqStr . '|max:200',
            'rating' => $ratingReq,
            'default_img_type' => $imgReq,
            'description' => 'nullable',
            'description_source' => 'nullable',
            'sort_order' => 'nullable|integer',
        ];
    }
}
