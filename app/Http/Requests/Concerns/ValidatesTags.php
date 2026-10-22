<?php

namespace App\Http\Requests\Concerns;

trait ValidatesTags
{
    /**
     * Rules for the tags[] field sent by the tag input component.
     *
     * @return array<string, array>
     */
    protected function tagRules(): array
    {
        return [
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
