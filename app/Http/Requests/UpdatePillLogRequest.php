<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePillLogRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'administered_at' => [
                'nullable',
                'date'
            ],
            'scheduled_time' => [
                'required',
                'date_format:H:i'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }
}
