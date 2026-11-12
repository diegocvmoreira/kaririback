<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $placeId = $this->route('place')?->id ?? $this->route('place');

        return [
            'owner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'city_id' => ['sometimes', 'required', 'integer', Rule::exists('cities', 'id')],
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('places', 'slug')->ignore($placeId)],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'neighborhood' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zipcode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:40'],
            'website' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opening_hours' => ['sometimes', 'nullable'],
            'price_level' => ['sometimes', 'nullable', 'integer', 'between:1,4'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'between:0,5'],
            'reviews_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'featured' => ['sometimes', 'boolean'],
            'verified' => ['sometimes', 'boolean'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'images' => ['sometimes', 'nullable', 'array', 'max:30'],
            'images.*' => ['string', 'max:2048'],
            'status' => [
                'sometimes',
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

        if ($this->has('featured')) {
            $payload['featured'] = $this->boolean('featured');
        }

        if ($this->has('verified')) {
            $payload['verified'] = $this->boolean('verified');
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
