<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(City::query()->orderBy('name')->get());
    }

    public function show(City $city): JsonResponse
    {
        return response()->json($city);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        abort_if(City::where('slug', $data['slug'])->exists(), 422, 'Já existe uma cidade com este slug.');

        return response()->json(City::create($data), 201);
    }

    public function update(Request $request, City $city): JsonResponse
    {
        $data = $this->validated($request, $city);
        $city->update($data);

        return response()->json($city->fresh());
    }

    public function destroy(City $city): JsonResponse
    {
        if ($city->places()->exists()) {
            // Mantém integridade: cidade com locais é apenas desativada.
            $city->update(['status' => 'inactive']);

            return response()->json(['message' => 'Cidade possui locais vinculados e foi desativada.']);
        }

        $city->delete();

        return response()->json(['message' => 'Cidade removida com sucesso.']);
    }

    private function validated(Request $request, ?City $city = null): array
    {
        $required = $city ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cities', 'slug')->ignore($city?->id)],
            'state' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // O front envia is_active; o banco usa status.
        if (array_key_exists('is_active', $data)) {
            $data['status'] ??= $data['is_active'] ? 'active' : 'inactive';
        }
        unset($data['is_active']);

        if (isset($data['state'])) {
            $data['state'] = strtoupper($data['state']);
        }

        return array_filter($data, fn ($v) => $v !== null);
    }
}
