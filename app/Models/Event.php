<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'place_id',
        'city_id',
        'category_id',
        'created_by',
        'title',
        'slug',
        'description',
        'image',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location_name',
        'ticket_url',
        'price',
        'featured',
        'status',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}