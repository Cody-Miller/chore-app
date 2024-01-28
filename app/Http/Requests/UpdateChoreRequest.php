<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChoreRequest extends FormRequest
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
                // 'unique:chores,name',
                'unique:chores,name,'.$this->name.',name',
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
                'between:0,10'
            ]
        ];
    }
}
