<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CitizenLoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'mobile' => 'required|max:10',
            'password' => 'required|min:8',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
