<?php

namespace Dashed\DashedCore\Requests\Frontend;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules()
    {
        return [
            'password' => [
                Password::defaults(),
                'max:255',
                'confirmed',
            ],
        ];
    }
}
