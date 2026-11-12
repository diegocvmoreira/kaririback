<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = is_array($this->images) ? $this->images : [];
        $coverImage = $this->cover_image ?: ($images[0] ?? null);

        return [
            'id' => (int) $this->id,
            'owner_id' => $this->owner_id ? (int) $this->owner_id : null,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'short_description' => $this->short_description ?? '',
            'description' => $this->description ?? $this->short_description ?? '',
            'city_id' => (int) $this->city_id,
            'city_slug' => $this->city?->slug,
            'city_name' => $this->city?->name,
            'category_id' => (int) $this->category_id,
            'category_slug' => $this->category?->slug,
            'category_name' => $this->category?->name,
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'zipcode' => $this->zipcode,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'website' => $this->website,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'opening_hours' => $this->opening_hours,
            'price_level' => (int) ($this->price_level ?? 1),
            'rating' => (float) ($this->rating ?? 0),
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'featured' => (bool) $this->featured,
            'is_featured' => (bool) $this->featured,
            'verified' => (bool) $this->verified,
            'status' => (string) ($this->status ?? 'published'),
            'cover_image' => $coverImage,
            'images' => $images,
            'is_open_now' => $this->isOpenNow(),
            'distance_km' => $this->distance_km !== null ? round((float) $this->distance_km, 2) : null,
        ];
    }
}
