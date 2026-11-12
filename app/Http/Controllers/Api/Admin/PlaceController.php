<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlaceRequest;
use App\Http\Requests\UpdatePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $places = Place::query()
            ->with(['city', 'category'])
            ->latest()
            ->paginate(20);

        return PlaceResource::collection($places);
    }

    public function store(StorePlaceRequest $request): PlaceResource
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (empty($data['owner_id'])) {
            $data['owner_id'] = null;
        }

        $place = Place::create($data);

        return new PlaceResource(
            $place->load(['city', 'category'])
        );
    }

    public function show(Place $place): PlaceResource
    {
        return new PlaceResource(
            $place->load(['city', 'category'])
        );
    }

    public function update(
        UpdatePlaceRequest $request,
        Place $place
    ): PlaceResource {
        $data = $request->validated();

        if (
            array_key_exists('name', $data) &&
            empty($data['slug'])
        ) {
            $data['slug'] = Str::slug($data['name']);
        }

        $place->update($data);

        return new PlaceResource(
            $place->fresh()->load(['city', 'category'])
        );
    }

    public function destroy(Place $place): JsonResponse
    {
        $place->delete();

        return response()->json([
            'message' => 'Local excluído com sucesso.',
        ]);
    }
}
