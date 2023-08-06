<?php

namespace Dashed\DashedCore\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules()
    {
        return [
            'password' => [
                'min:6',
                'max:255',
                'confirmed',
            ],
        ];
    }
}
