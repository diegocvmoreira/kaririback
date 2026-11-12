<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessClaimController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'place_id' => ['required', 'integer', Rule::exists('places', 'id')],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'proof' => ['nullable', 'string', 'max:500'],
        ]);

        $userId = $request->user()->id;

        $alreadyPending = BusinessClaim::query()
            ->where('user_id', $userId)
            ->where('place_id', $data['place_id'])
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'message' => 'Você já possui uma solicitação pendente para este local.',
            ], 409);
        }

        $claim = BusinessClaim::create([
            'user_id' => $userId,
            'place_id' => $data['place_id'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'proof' => $data['proof'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($claim->load(['user', 'place'])->toApiArray(), 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $claims = BusinessClaim::query()
            ->with(['user', 'place'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (BusinessClaim $claim) => $claim->toApiArray());

        return response()->json($claims);
    }
}
