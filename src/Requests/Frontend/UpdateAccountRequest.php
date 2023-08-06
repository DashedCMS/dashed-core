<?php

namespace Dashed\DashedCore\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function rules()
    {
        return [
            'first_name' => [
                'max:255',
            ],
            'last_name' => [
                'max:255',
            ],
            'password' => [
                'nullable',
                'min:6',
                'max:255',
                'confirmed',
            ],
        ];
    }
}
