<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChoreLogRequest extends FormRequest
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
                'required',
                'integer',
                'exists:users,id'
            ],
            'chore_id' => [
                'required',
                'integer',
                'exists:chores,id'
            ],
            'completed_time' => [
                'required',
                'date_format:Y-m-d\TH:i:s'
            ]
        ];
    }
}
