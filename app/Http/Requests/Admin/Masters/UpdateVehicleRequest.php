<?php

namespace App\Http\Requests\Admin\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
        $vehicleID = $this->vehicle->id ?? null;
        return [
            // 'name' => 'required',
            'type_name' => ['required', Rule::unique('vehicle_type_masters')->ignore($vehicleID)->whereNull('deleted_at')],
            'size_in_feet' => 'required',
        ];
    }
}