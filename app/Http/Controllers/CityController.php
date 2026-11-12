<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        return response()->json(
            City::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
        );
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $cities = City::query()
            ->where('status', 'active')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('state', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json($cities);
    }

    public function show(City $city)
    {
        if ($city->status !== 'active') {
            return response()->json(['message' => 'Cidade não encontrada.'], 404);
        }

        return response()->json($city);
    }
}
