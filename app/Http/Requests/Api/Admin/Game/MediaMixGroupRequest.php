<?php

namespace App\Http\Requests\Api\Admin\Game;

use Illuminate\Foundation\Http\FormRequest;

class MediaMixGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('POST');
        $reqStr = $isPost ? 'required' : 'sometimes|required';

        return [
            'game_franchise_id' => $reqStr . '|exists:game_franchises,id',
            'name' => $reqStr . '|max:200',
            'node_name' => $reqStr . '|max:200',
            'description' => 'nullable',
            'sort_order' => $reqStr . '|integer',
        ];
    }
}
