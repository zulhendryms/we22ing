<?php

namespace App\Core\Security\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'UserName' => 'required|exists:user,UserName',
            'Password' => 'required',
            'Company' => 'required|exists:company,Oid'
        ];
    }
}
