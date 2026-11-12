<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\PlaceResource;
use App\Models\Event;
use App\Models\Place;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'places' => [],
                'events' => [],
                'total' => 0,
            ]);
        }

        /*
         * Os locais atuais usam status "active".
         * O sistema também aceita "published" para manter compatibilidade
         * com registros futuros.
         */
        $placeStatuses = ['active', 'published'];

        $placesQuery = Place::query()
            ->with(['city', 'category'])
            ->whereIn('status', $placeStatuses)
            ->where(function ($query) use ($term) {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('neighborhood', 'like', "%{$term}%")
                    ->orWhereHas('city', function ($cityQuery) use ($term) {
                        $cityQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%");
                    })
                    ->orWhereHas('category', function ($categoryQuery) use ($term) {
                        $categoryQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%");
                    });
            });

        if ($request->filled('city')) {
            $city = (string) $request->input('city');

            $placesQuery->whereHas('city', function ($query) use ($city) {
                $query
                    ->where('slug', $city)
                    ->orWhere('name', $city);
            });
        }

        $places = $placesQuery
            ->orderByDesc('featured')
            ->orderByDesc('rating')
            ->limit(30)
            ->get();

        /*
         * Eventos publicados.
         * O status "active" também é aceito para compatibilidade
         * caso os eventos tenham sido cadastrados dessa forma.
         */
        $eventStatuses = ['active', 'published'];

        $eventsQuery = Event::query()
            ->with(['city', 'category', 'place'])
            ->whereIn('status', $eventStatuses)
            ->where(function ($query) use ($term) {
                $query
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('location_name', 'like', "%{$term}%")
                    ->orWhereHas('city', function ($cityQuery) use ($term) {
                        $cityQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%");
                    })
                    ->orWhereHas('category', function ($categoryQuery) use ($term) {
                        $categoryQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('slug', 'like', "%{$term}%");
                    });
            });

        if ($request->filled('city')) {
            $city = (string) $request->input('city');

            $eventsQuery->whereHas('city', function ($query) use ($city) {
                $query
                    ->where('slug', $city)
                    ->orWhere('name', $city);
            });
        }

        $events = $eventsQuery
            ->orderByDesc('featured')
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->limit(30)
            ->get();

        return response()->json([
            'places' => PlaceResource::collection($places),
            'events' => EventResource::collection($events),
            'total' => $places->count() + $events->count(),
        ]);
    }
}
