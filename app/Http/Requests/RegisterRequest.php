<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $login
 */
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'login' => ['required', 'email', 'unique:users,login'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'login.required' => 'Необходимо заполнить Емайл',
            'login.exists' => 'Email не найден в системе, попробуйте другой',
            'login.email' => 'Значение поля Емайл должно быть действительным электронным адресом.',
        ];
    }
}
