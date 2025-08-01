<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'role' => 'required',
            'name' => 'required',
            'email' => 'required|unique:users,email|email',
            'mobile' => 'required|unique:users,mobile|digits:10',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ];

        if ($this->input('role') == 3) {
            $rules['ward_id'] = 'required';
            $rules['department_id'] = 'nullable';
        } elseif ($this->input('role') == 4) {
            $rules['ward_id'] = 'required';
            $rules['department_id'] = 'required';
        } else {
            $rules['ward_id'] = 'nullable';
            $rules['department_id'] = 'nullable';
        }


        return $rules;
    }
}