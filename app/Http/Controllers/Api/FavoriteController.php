<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::query()
            ->with(['place.city', 'event.city', 'event.category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($favorites);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'place_id' => ['nullable', 'integer', Rule::exists('places', 'id')],
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
        ]);

        if (empty($data['place_id']) === empty($data['event_id'])) {
            return response()->json([
                'message' => 'Envie exatamente um place_id ou event_id.',
            ], 422);
        }

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'place_id' => $data['place_id'] ?? null,
            'event_id' => $data['event_id'] ?? null,
        ]);

        return response()->json($favorite, 201);
    }

    public function destroy(Request $request, Favorite $favorite)
    {
        abort_unless($favorite->user_id === $request->user()->id, 403);
        
        $favorite->delete();

        return response()->json([
            'message' => 'Favorito removido.',
        ]);
    }
}