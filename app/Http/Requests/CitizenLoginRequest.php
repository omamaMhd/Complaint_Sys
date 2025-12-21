<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CitizenLoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'mobile' => 'required',
            'password' => 'required|min:8',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
