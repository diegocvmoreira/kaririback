<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventContributionRequest;
use App\Http\Requests\StorePlaceContributionRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\PlaceResource;
use App\Models\Event;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ContributionController extends Controller
{
    public function storePlace(StorePlaceContributionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['owner_id'] = (int) $request->user()->id;
        $data['status'] = 'pending';
        $data['featured'] = false;
        $data['verified'] = false;
        $data['rating'] = 0;
        $data['reviews_count'] = 0;
        $data['slug'] = $this->uniqueSlug(Place::class, $data['slug'] ?? null, $data['name']);

        $place = Place::create($data);

        return (new PlaceResource($place->load(['city', 'category'])))
            ->response()
            ->setStatusCode(201);
    }

    public function storeEvent(StoreEventContributionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = (int) $request->user()->id;
        $data['status'] = 'pending';
        $data['featured'] = false;
        $data['slug'] = $this->uniqueSlug(Event::class, $data['slug'] ?? null, $data['title']);
        $data['image'] = $data['image'] ?? ($data['cover_image'] ?? null);
        $data['location_name'] = $data['location_name'] ?? ($data['place_name'] ?? null);

        unset($data['cover_image'], $data['place_name'], $data['is_free'], $data['organizer']);

        $event = Event::create($data);

        return (new EventResource($event->load(['city', 'category', 'place'])))
            ->response()
            ->setStatusCode(201);
    }

    public function myPlaces(Request $request): AnonymousResourceCollection
    {
        $places = Place::query()
            ->where('owner_id', $request->user()->id)
            ->with(['city', 'category'])
            ->latest()
            ->paginate(50);

        return PlaceResource::collection($places);
    }

    public function myEvents(Request $request): AnonymousResourceCollection
    {
        $events = Event::query()
            ->where('created_by', $request->user()->id)
            ->with(['city', 'category', 'place'])
            ->latest()
            ->paginate(50);

        return EventResource::collection($events);
    }

    public function mine(Request $request): JsonResponse
    {
        $places = Place::query()
            ->where('owner_id', $request->user()->id)
            ->with(['city', 'category'])
            ->latest()
            ->get();

        $events = Event::query()
            ->where('created_by', $request->user()->id)
            ->with(['city', 'category', 'place'])
            ->latest()
            ->get();

        return response()->json([
            'places' => PlaceResource::collection($places),
            'events' => EventResource::collection($events),
        ]);
    }

    private function uniqueSlug(string $modelClass, ?string $requestedSlug, string $fallback): string
    {
        $base = Str::slug($requestedSlug ?: $fallback);
        $base = $base !== '' ? $base : 'contribuicao';
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
