<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PlaceResource;
use App\Models\Place;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Place::query()
            ->with(['city', 'category'])
            ->where('status', 'published');

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($city) => $city->where('slug', $request->query('city')));
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $request->query('category')));
        }

        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', (float) $request->query('min_rating'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));

            if (mb_strlen($term) >= 2) {
                $query->where(function ($search) use ($term): void {
                    $search
                        ->where('places.name', 'like', "%{$term}%")
                        ->orWhere('places.slug', 'like', "%{$term}%")
                        ->orWhere('places.short_description', 'like', "%{$term}%")
                        ->orWhere('places.description', 'like', "%{$term}%")
                        ->orWhere('places.address', 'like', "%{$term}%")
                        ->orWhere('places.neighborhood', 'like', "%{$term}%")
                        ->orWhereHas('city', fn ($city) => $city
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%"))
                        ->orWhereHas('category', fn ($category) => $category
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%"));
                });
            }
        }

        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $hasCoordinates = is_numeric($lat)
            && is_numeric($lng)
            && (float) $lat >= -90
            && (float) $lat <= 90
            && (float) $lng >= -180
            && (float) $lng <= 180;

        if ($hasCoordinates) {
            $latValue = (float) $lat;
            $lngValue = (float) $lng;
            $haversineSql = '(6371 * ACOS(
                COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                COS(RADIANS(longitude) - RADIANS(?)) +
                SIN(RADIANS(?)) * SIN(RADIANS(latitude))
            ))';

            $query->select('places.*')->selectRaw(
                "CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL
                 THEN {$haversineSql} ELSE NULL END AS distance_km",
                [$latValue, $lngValue, $latValue]
            );

            if ($request->filled('radius') && is_numeric($request->query('radius'))) {
                $radius = max((float) $request->query('radius'), 0);
                $query->havingRaw('(distance_km <= ? OR distance_km IS NULL)', [$radius]);
            }
        }

        $sortBy = (string) $request->query('sort_by', 'featured');

        if ($sortBy === 'distance' && $hasCoordinates) {
            $query->orderByRaw('CASE WHEN distance_km IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('distance_km');
        } elseif ($sortBy === 'rating') {
            $query->orderByDesc('rating');
        } elseif ($sortBy === 'reviews') {
            $query->orderByDesc('reviews_count');
        } else {
            $query->orderByDesc('featured')->orderByDesc('rating');
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);

        if ($request->boolean('open_now')) {
            $all = $query->get()->filter(fn (Place $place) => $place->isOpenNow())->values();
            $page = max((int) $request->query('page', 1), 1);
            $items = $all->forPage($page, $perPage)->values();
            $paginator = new LengthAwarePaginator(
                $items,
                $all->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return PlaceResource::collection($paginator);
        }

        return PlaceResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function nearby(Request $request): AnonymousResourceCollection
    {
        $request->merge([
            'sort_by' => 'distance',
            'per_page' => min((int) $request->query('limit', 6), 50),
        ]);

        return $this->index($request);
    }

    public function show(string $slug): JsonResponse
    {
        $place = Place::query()
            ->with(['city', 'category'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json(['data' => new PlaceResource($place)]);
    }
}
