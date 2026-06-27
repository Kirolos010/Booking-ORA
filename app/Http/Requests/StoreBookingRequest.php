<?php

namespace App\Http\Requests;

use App\Enums\ResourceTypes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreBookingRequest extends FormRequest
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
            'resource_id' => ['required','exists:resources,id'],
            // 'resource_type' => ['required', new Enum(ResourceTypes::class)],
            'starts_at' => ['required','date','after:now'],
            'ends_at' => ['required','date','after:starts_at'],
            'amount' => ['required','integer','min:1'],
            'currency' => ['nullable','string','size:3'],
            'payment_method' => ['nullable','string'],
            'metadata' => ['nullable','array'],
        ];
    }
}
