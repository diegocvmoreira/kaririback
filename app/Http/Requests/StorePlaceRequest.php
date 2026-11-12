<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:places,slug'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:500'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'zipcode' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:2048'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable'],
            'price_level' => ['nullable', 'integer', 'between:1,4'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'reviews_count' => ['nullable', 'integer', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'verified' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'string', 'max:2048'],
            'images' => ['nullable', 'array', 'max:30'],
            'images.*' => ['string', 'max:2048'],
            'status' => [
                'nullable',
                Rule::in(['draft', 'pending', 'published', 'rejected', 'archived']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if (!$this->filled('cover_image') && $this->filled('image')) {
            $payload['cover_image'] = $this->input('image');
        }

        $payload['featured'] = $this->boolean('featured');
        $payload['verified'] = $this->boolean('verified');
        $payload['status'] = $this->input('status', 'draft');

        $this->merge($payload);
    }
}
