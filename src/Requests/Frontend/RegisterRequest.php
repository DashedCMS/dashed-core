<?php

namespace Dashed\DashedCore\Requests\Frontend;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => [
                'unique:users',
                'required',
                'email:rfc',
                'max:255',
            ],
            'password' => [
                Password::defaults(),
                'max:255',
                'confirmed',
            ],
        ];
    }
}
