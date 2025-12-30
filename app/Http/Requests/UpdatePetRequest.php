<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $pet = $this->route('pet');
        $petId = $pet?->id;

        return [
            'name' => [
                'required',
                Rule::unique('pets', 'name')->ignore($petId)->whereNull('deleted_at'),
                'string',
                'max:255'
            ],
            'species' => [
                'nullable',
                'string',
                'max:100'
            ],
            'breed' => [
                'nullable',
                'string',
                'max:100'
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before:today'
            ],
            'notes' => [
                'nullable',
                'string'
            ],
            'photo' => [
                'nullable',
                'image',
                'max:2048' // 2MB max
            ]
        ];
    }
}
