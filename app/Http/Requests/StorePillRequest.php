<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePillRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'pet_id' => [
                'required',
                'integer',
                'exists:pets,id'
            ],
            'name' => [
                'required',
                Rule::unique('pills', 'name')->whereNull('deleted_at'),
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string'
            ],
            'dosage' => [
                'required',
                'string',
                'max:100'
            ],
            'scheduled_times' => [
                'required',
                'array',
                'min:1'
            ],
            'scheduled_times.*' => [
                'required',
                'date_format:H:i'
            ]
        ];
    }
}
