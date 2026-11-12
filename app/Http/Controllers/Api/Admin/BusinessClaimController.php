<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BusinessClaim::query()->with(['user', 'place'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json(
            $query->get()->map(fn (BusinessClaim $claim) => $claim->toApiArray())
        );
    }

    public function update(Request $request, BusinessClaim $claim): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'approved', 'rejected'])],
            'reject_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($claim, $data, $request) {
            $claim->update([
                'status' => $data['status'],
                'reject_reason' => $data['status'] === 'rejected' ? ($data['reject_reason'] ?? null) : null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($data['status'] === 'approved') {
                $claim->place()->update(['owner_id' => $claim->user_id, 'verified' => true]);

                $user = $claim->user;
                if ($user && $user->role === 'user') {
                    $user->update(['role' => 'business']);
                }
            }
        });

        return response()->json($claim->fresh(['user', 'place'])->toApiArray());
    }
}
