<?php

namespace Dashed\DashedCore\Requests\Frontend;

use Illuminate\Validation\Rules\Password;
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
                Password::defaults(),
                'max:255',
                'confirmed',
            ],
        ];
    }
}
