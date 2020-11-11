<?php

namespace App\Core\Security\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        $rules = [
            'UserName' => 'required',
            'Password' => 'required|min:8',
        ];
        if (config('core.security.register.recaptcha')) {
            $rules['g-recaptcha-response'] = 'required|recaptcha';
        }
        return $rules;
    }
}
