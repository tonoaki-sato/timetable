<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentLogisticsPlanRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'exists:equipments,id'],
            'target_date' => ['nullable', 'date'],
            'day_phase' => ['required', 'string', 'in:pre_day,day1_morning,day1_evening,day2_morning,day2_evening,post_day,other'],
            'action_type' => ['required', 'string', 'in:storage_out,vendor_receive,staging_move,setup,night_storage,return_storage,collection_staging,vendor_setup,vendor_pickup,other'],
            'title' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'from_location' => ['nullable', 'string', 'max:255'],
            'to_location' => ['nullable', 'string', 'max:255'],
            'from_location_id' => ['nullable', 'exists:logistics_locations,id'],
            'to_location_id' => ['nullable', 'exists:logistics_locations,id'],
            'operator_type' => ['required', 'string', 'in:committee,event_company'],
            'witness_required' => ['boolean'],
            'witness_staff_id' => ['nullable', 'exists:staff,id'],
            'contact_info' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:planned,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'witness_required' => $this->boolean('witness_required'),
            'status' => $this->input('status', 'planned'),
            'operator_type' => $this->input('operator_type', 'committee'),
        ]);
    }
}
