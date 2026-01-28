<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:1',

            'category_id' => 'required|exists:categories,id',

            'shortDesc' => [
                'nullable',
                'string',
                'max:255',
            ],

            'what_youll_learn' => [
                'required',
                'string',
            ],

            'lesson_type' => ['required', Rule::in(['online', 'inperson'])],

        ];
    }
}
