<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('event')?->id
            ?? $this->route('event');

        return [
            'city_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('cities', 'id'),
            ],

            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
            ],

            'place_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('places', 'id'),
            ],

            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('events', 'slug')->ignore($eventId),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'image' => [
                'sometimes',
                'nullable',
                'string',
                'max:2048',
            ],

            'start_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'end_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'start_time' => [
                'sometimes',
                'nullable',
                'date_format:H:i',
            ],

            'end_time' => [
                'sometimes',
                'nullable',
                'date_format:H:i',
                'after_or_equal:start_time',
            ],

            'location_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'ticket_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],

            'price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'featured' => [
                'sometimes',
                'boolean',
            ],

            'status' => [
                'sometimes',
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
        if ($this->has('featured')) {
            $this->merge([
                'featured' => $this->boolean('featured'),
            ]);
        }
    }
}
