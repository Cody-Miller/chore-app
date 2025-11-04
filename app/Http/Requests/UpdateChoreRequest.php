<?php

namespace App\Http\Requests;

use App\Models\Chore;
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
        // Get the chore from route model binding (already resolved by Laravel)
        $chore = $this->route('chore');
        $choreId = $chore?->id;

        return [
            'name' => [
                'required',
                Rule::unique('chores', 'name')->ignore($choreId)->whereNull('deleted_at'),
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
            ]
        ];
    }
}
