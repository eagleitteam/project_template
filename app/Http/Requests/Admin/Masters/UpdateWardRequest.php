<?php

namespace App\Http\Requests\Admin\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWardRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $wardId = $this->ward->id ?? null;
        return [
            // 'name' => 'required',
            'name' => ['required', Rule::unique('wards')->ignore($wardId)->whereNull('deleted_at')],
            'initial' => 'required',
        ];
    }
}