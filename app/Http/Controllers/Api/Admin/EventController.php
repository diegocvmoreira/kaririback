<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $events = Event::query()
            ->with(['city', 'category', 'place'])
            ->latest()
            ->paginate(20);

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request): EventResource
    {
        $data = $request->validated();

        $data['created_by'] = $request->user()->id;

        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug(
                $data['title']
            );
        }

        $event = Event::create($data);

        return new EventResource(
            $event->load(['city', 'category', 'place'])
        );
    }

    public function show(Event $event): EventResource
    {
        return new EventResource(
            $event->load(['city', 'category', 'place'])
        );
    }

    public function update(
        UpdateEventRequest $request,
        Event $event
    ): EventResource {
        $data = $request->validated();

        if (
            array_key_exists('title', $data) &&
            empty($data['slug'])
        ) {
            $data['slug'] = \Illuminate\Support\Str::slug(
                $data['title']
            );
        }

        $event->update($data);

        return new EventResource(
            $event->fresh()->load(['city', 'category', 'place'])
        );
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Evento excluído com sucesso.',
        ]);
    }
}
