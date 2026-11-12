<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Place;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Place $place)
    {
        return response()->json(
            Review::query()
                ->where('place_id', $place->id)
                ->where('status', 'published')
                ->with('user:id,name')
                ->latest()
                ->paginate(15)
        );
    }

    public function store(StoreReviewRequest $request, Place $place): JsonResponse
    {
        $alreadyReviewed = Review::query()
            ->where('place_id', $place->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'message' => 'Você já avaliou este local.',
            ], 409);
        }

        $review = DB::transaction(function () use ($request, $place) {
            $review = Review::create([
                'place_id' => $place->id,
                'user_id' => $request->user()->id,
                'rating' => (int) $request->validated('rating'),
                'comment' => trim((string) $request->validated('comment')),
                'status' => 'published',
                'ip_hash' => hash('sha256', (string) $request->ip()),
            ]);

            $stats = Review::query()
                ->where('place_id', $place->id)
                ->where('status', 'published')
                ->selectRaw('AVG(rating) AS average_rating, COUNT(*) AS review_count')
                ->first();

            $place->update([
                'rating' => round((float) ($stats->average_rating ?? 0), 2),
                'reviews_count' => (int) ($stats->review_count ?? 0),
            ]);

            return $review->load('user:id,name');
        });

        return response()->json([
            'id' => $review->id,
            'place_id' => $review->place_id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
            'user_name' => $review->user?->name,
            'created_at' => $review->created_at?->toIso8601String(),
        ], 201);
    }
}
