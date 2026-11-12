<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'status',
                'email_verified_at',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));

            $query->where(function ($userQuery) use ($term) {
                $userQuery
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        return response()->json(
            $query->paginate($perPage)
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(UserRole::values())],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        // Evita que um admin remova o próprio acesso por engano.
        if ($user->id === $request->user()->id) {
            abort_if(isset($data['role']) && $data['role'] !== 'admin', 422, 'Você não pode alterar seu próprio perfil de acesso.');
            abort_if(isset($data['status']) && $data['status'] !== 'active', 422, 'Você não pode desativar a própria conta.');
        }

        $user->update($data);

        if (isset($data['status']) && $data['status'] !== 'active') {
            $user->tokens()->delete();
        }

        return response()->json($user->only(['id', 'name', 'email', 'role', 'status', 'email_verified_at', 'created_at', 'updated_at']));
    }
}
