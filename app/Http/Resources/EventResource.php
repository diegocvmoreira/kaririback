<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = $this->price ?? 0;
        $isFree = (float) $price === 0.0;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_image' => $this->image, // Mapeia o campo image para cover_image
            'start_date' => $this->start_date ? (string) $this->start_date : null,
            'end_date' => $this->end_date ? (string) $this->end_date : null,
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'place_name' => $this->place?->name ?? $this->location_name,
            'city_name' => $this->city?->name ?? null,
            'city_slug' => $this->city?->slug ?? null,
            'location_name' => $this->location_name,
            'price' => (float) $price,
            'price_text' => $isFree ? 'Gratuito' : 'R$ ' . number_format($price, 2, ',', '.'),
            'is_free' => $isFree,
            'ticket_url' => $this->ticket_url,
            'category' => $this->category?->name ?? null,
            'is_featured' => (bool) $this->featured,
            'status' => $this->status,
        ];
    }
}