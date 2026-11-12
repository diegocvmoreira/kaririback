<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $fillable = [
        'owner_id',
        'city_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'address',
        'neighborhood',
        'zipcode',
        'latitude',
        'longitude',
        'phone',
        'whatsapp',
        'website',
        'instagram',
        'facebook',
        'opening_hours',
        'price_level',
        'rating',
        'reviews_count',
        'featured',
        'verified',
        'status',
        'cover_image',
        'images',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'rating' => 'decimal:2',
            'price_level' => 'integer',
            'reviews_count' => 'integer',
            'featured' => 'boolean',
            'verified' => 'boolean',
            'images' => 'array',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeWithDistance(Builder $query, float $lat, float $lng): Builder
    {
        $haversine = '(6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(latitude))
        ))';

        return $query
            ->select('places.*')
            ->selectRaw(
                "CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL
                 THEN {$haversine} ELSE NULL END AS distance_km",
                [$lat, $lng, $lat]
            );
    }

    public function isOpenNow(?CarbonInterface $now = null): bool
    {
        $currentTime = $now
            ? $now
            : now(config('app.timezone', 'America/Fortaleza'));

        $openingHours = $this->opening_hours;

        if (empty($openingHours)) {
            return false;
        }

        if (is_string($openingHours)) {
            $decoded = json_decode($openingHours, true);
            $openingHours = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        if (!is_array($openingHours)) {
            return false;
        }

        $dayKeys = [
            1 => ['monday', 'segunda', 'segunda-feira', '1'],
            2 => ['tuesday', 'terça', 'terca', 'terça-feira', 'terca-feira', '2'],
            3 => ['wednesday', 'quarta', '3'],
            4 => ['thursday', 'quinta', '4'],
            5 => ['friday', 'sexta', '5'],
            6 => ['saturday', 'sábado', 'sabado', '6'],
            7 => ['sunday', 'domingo', '7'],
        ];

        $today = null;
        foreach ($dayKeys[$currentTime->dayOfWeekIso] as $key) {
            if (array_key_exists($key, $openingHours)) {
                $today = $openingHours[$key];
                break;
            }
        }

        if ($today === null || $today === false) {
            return false;
        }

        if (is_string($today)) {
            if (in_array(mb_strtolower(trim($today)), ['fechado', 'closed', 'não', 'nao'], true)) {
                return false;
            }

            if (preg_match('/(\d{1,2}:\d{2})\s*(?:-|–|às|as|a)\s*(\d{1,2}:\d{2})/iu', $today, $matches) !== 1) {
                return false;
            }

            return $this->isWithinTimeRange($matches[1], $matches[2], $currentTime);
        }

        if (!is_array($today) || !empty($today['closed']) || !empty($today['fechado'])) {
            return false;
        }

        $open = $today['open'] ?? $today['opens_at'] ?? $today['abertura'] ?? null;
        $close = $today['close'] ?? $today['closes_at'] ?? $today['fechamento'] ?? null;

        return $open && $close
            ? $this->isWithinTimeRange((string) $open, (string) $close, $currentTime)
            : false;
    }

    private function isWithinTimeRange(string $open, string $close, CarbonInterface $now): bool
    {
        $pattern = '/^([01]?\d|2[0-3]):[0-5]\d$/';

        if (!preg_match($pattern, $open) || !preg_match($pattern, $close)) {
            return false;
        }

        [$openHour, $openMinute] = array_map('intval', explode(':', $open));
        [$closeHour, $closeMinute] = array_map('intval', explode(':', $close));
        $current = ((int) $now->format('H') * 60) + (int) $now->format('i');
        $start = ($openHour * 60) + $openMinute;
        $end = ($closeHour * 60) + $closeMinute;

        return $start <= $end
            ? $current >= $start && $current <= $end
            : $current >= $start || $current <= $end;
    }
}
