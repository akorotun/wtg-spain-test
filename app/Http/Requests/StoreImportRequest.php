<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\CarbonImmutable;

class StoreImportRequest extends FormRequest
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
            'supplier' => ['required', 'string', 'exists:suppliers,code'],
            'external_import_id' => ['required', 'string'],
            //'sent_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            //'sent_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z'],
            'sent_at' => ['required', 'date'],
            'offers' => ['required', 'array', 'min:1', 'max:50'],

            'offers.*.external_id' => ['required', 'string'],
            'offers.*.check_in' => ['required', 'date_format:Y-m-d'],
            'offers.*.check_out' => ['required', 'date_format:Y-m-d', 'after:offers.*.check_in'],
            'offers.*.max_guests' => ['required', 'integer', 'min:1'],
            'offers.*.price' => ['required', 'integer', 'min:1'],
            'offers.*.currency' => ['required', 'string', 'max:3'],
            'offers.*.available_units' => ['required', 'integer', 'min:1'],
            //'offers.*.expires_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            //'offers.*.expires_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z'],
            'offers.*.expires_at' => ['required', 'date'],

            'offers.*.property' => ['required', 'array:code,name,city'],
            'offers.*.property.code' => ['required', 'string'],
            'offers.*.property.name' => ['required', 'string'],
            'offers.*.property.city' => ['required', 'string'],
        ];
    }

    public function sentAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('sent_at'))->utc();
    }
}
