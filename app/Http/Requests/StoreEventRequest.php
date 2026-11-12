<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id'),
            ],

            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
            ],

            'place_id' => [
                'nullable',
                'integer',
                Rule::exists('places', 'id'),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:events,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'string',
                'max:2048',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'end_time' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:start_time',
            ],

            'location_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ticket_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'featured' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'pending',
                    'published',
                    'rejected',
                    'archived',
                    'active',
                ]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'featured' => $this->boolean('featured'),
            'status' => $this->input('status', 'draft'),
        ]);
    }
}
