<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                Rule::unique('chores', 'name')->whereNull('deleted_at'),
                'string',
                'max:255'
            ],
            'desc' => [
                'required',
                'string'
            ],
            'occurMonth' => [
                'required',
                'integer',
                'between:0,12'
            ],
            'occurDay' => [
                'required',
                'integer',
                'between:0,32'
            ],
            'weight' => [
                'required',
                'integer',
                'between:1,5'
            ],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
            ],
            'tags' => [
                'nullable',
                'array',
                'max:10'
            ],
            'tags.*' => [
                'nullable',
                'string',
                'max:50',
                'distinct:ignore_case'
            ]
        ];
    }
}
