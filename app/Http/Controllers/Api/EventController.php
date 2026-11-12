<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request) 
    {
        $query = Event::with(['city', 'category', 'place'])
            ->where('status', 'published')
            ->orderBy('start_date')
            ->orderBy('start_time');

        if ($request->filled('city')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('slug', $request->city);
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        return EventResource::collection(
            $query->paginate(20)
        );
    }

    public function show(string $slug) 
    {
        $event = Event::with(['city', 'category', 'place'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return new EventResource($event);
    }
}
