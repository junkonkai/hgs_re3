<?php

namespace App\Http\Requests\Api\Admin\Game;

use App\Enums\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RelatedProductShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('POST');
        $shopReq = $isPost
            ? ['required', new Enum(Shop::class)]
            : ['sometimes', 'required', new Enum(Shop::class)];
        $reqStr = $isPost ? 'required' : 'sometimes|required';

        return [
            'shop_id' => $shopReq,
            'subtitle' => 'nullable',
            'url' => $reqStr,
            'img_tag' => 'nullable',
            'param1' => 'nullable',
            'param2' => 'nullable',
            'param3' => 'nullable',
            'use_img_tag' => 'nullable',
        ];
    }
}
