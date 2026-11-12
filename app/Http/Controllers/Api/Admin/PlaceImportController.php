<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlaceImportController extends Controller
{
    private const VALID_STATUSES = ['draft', 'pending', 'published', 'rejected', 'archived'];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'research_metadata' => ['nullable', 'array'],
            'places' => ['required', 'array', 'min:1', 'max:500'],
            'places.*' => ['required', 'array'],
        ]);

        $created = 0;
        $updated = 0;
        $ignored = 0;
        $withCoordinates = 0;
        $withoutCoordinates = 0;
        $errors = [];
        $saved = [];

        DB::transaction(function () use (
            $data,
            &$created,
            &$updated,
            &$ignored,
            &$withCoordinates,
            &$withoutCoordinates,
            &$errors,
            &$saved
        ): void {
            foreach ($data['places'] as $index => $item) {
                $name = trim((string) ($item['name'] ?? ''));
                $slug = trim((string) ($item['slug'] ?? '')) ?: Str::slug($name);
                $citySlug = trim((string) ($item['city_slug'] ?? ''));
                $categorySlug = trim((string) ($item['category_slug'] ?? ''));

                if ($name === '' || $slug === '') {
                    $ignored++;
                    $errors[] = $this->error($index, $name, $slug, 'Nome e slug são obrigatórios.');
                    continue;
                }

                $city = !empty($item['city_id'])
                    ? City::find((int) $item['city_id'])
                    : City::where('slug', $citySlug)->first();
                $category = !empty($item['category_id'])
                    ? Category::find((int) $item['category_id'])
                    : Category::where('slug', $categorySlug)->first();

                if (!$city || !$category) {
                    $ignored++;
                    $errors[] = $this->error(
                        $index,
                        $name,
                        $slug,
                        'Cidade ou categoria não encontrada pelo ID/slug informado.'
                    );
                    continue;
                }

                $lat = $this->coordinate($item['latitude'] ?? null, -90, 90);
                $lng = $this->coordinate($item['longitude'] ?? null, -180, 180);
                $hasCoordinates = $lat !== null && $lng !== null;
                $hasCoordinates ? $withCoordinates++ : $withoutCoordinates++;

                $status = (string) ($item['status'] ?? 'pending');
                if (!in_array($status, self::VALID_STATUSES, true)) {
                    $status = 'pending';
                }

                $attributes = [
                    'owner_id' => null,
                    'city_id' => $city->id,
                    'category_id' => $category->id,
                    'name' => $name,
                    'short_description' => $this->stringValue($item['short_description'] ?? null, 500),
                    'description' => (string) ($item['description'] ?? $item['short_description'] ?? ''),
                    'address' => $this->stringValue($item['address'] ?? null, 500),
                    'neighborhood' => $this->stringValue($item['neighborhood'] ?? null, 255),
                    'zipcode' => $this->stringValue($item['zipcode'] ?? null, 20),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'phone' => $this->stringValue($item['phone'] ?? null, 40),
                    'whatsapp' => $this->stringValue($item['whatsapp'] ?? null, 40),
                    'website' => $this->stringValue($item['website'] ?? null, 2048),
                    'instagram' => $this->stringValue($item['instagram'] ?? null, 255),
                    'facebook' => $this->stringValue($item['facebook'] ?? null, 255),
                    'opening_hours' => $item['opening_hours'] ?? null,
                    'price_level' => min(max((int) ($item['price_level'] ?? 1), 1), 4),
                    'featured' => (bool) ($item['featured'] ?? $item['is_featured'] ?? false),
                    'verified' => (bool) ($item['verified'] ?? false),
                    'status' => $status,
                    'cover_image' => $this->stringValue($item['cover_image'] ?? $item['image'] ?? null, 2048),
                    'images' => is_array($item['images'] ?? null) ? $item['images'] : [],
                ];

                $place = Place::query()->where('slug', $slug)->first();
                if ($place) {
                    $place->update($attributes);
                    $updated++;
                } else {
                    $place = Place::create(['slug' => $slug, ...$attributes]);
                    $created++;
                }

                $saved[] = new PlaceResource($place->load(['city', 'category']));
            }
        });

        return response()->json([
            'success' => true,
            'total' => count($data['places']),
            'created' => $created,
            'updated' => $updated,
            'ignored' => $ignored,
            'with_coordinates' => $withCoordinates,
            'without_coordinates' => $withoutCoordinates,
            'errors' => $errors,
            'places' => array_map(static fn (PlaceResource $resource) => $resource->resolve($request), $saved),
        ]);
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        return $number >= $min && $number <= $max ? $number : null;
    }

    private function stringValue(mixed $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr(trim((string) $value), 0, $max);
    }

    private function error(int $index, string $name, string $slug, string $message): array
    {
        return [
            'index' => $index,
            'name' => $name,
            'slug' => $slug,
            'error' => $message,
        ];
    }
}
