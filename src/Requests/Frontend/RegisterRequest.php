<?php

namespace Qubiqx\QcommerceCore\Requests\Frontend;

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
                'min:6',
                'max:255',
                'confirmed',
            ],
        ];
    }
}
