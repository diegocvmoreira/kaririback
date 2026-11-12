<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class MunicipalityController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $municipalities = Cache::remember('ibge:municipalities:v1', now()->addDay(), function (): array {
            $url = rtrim((string) config('services.ibge.base_url'), '/') . '/localidades/municipios';
            $response = Http::connectTimeout(5)->timeout(15)->get($url);
            if (!$response->successful()) {
                return [];
            }

            return collect($response->json())->map(function (array $municipality): array {
                $state = $municipality['microrregiao']['mesorregiao']['UF'] ?? [];
                $uf = strtoupper((string) ($state['sigla'] ?? ''));
                $name = (string) ($municipality['nome'] ?? '');
                $id = (int) ($municipality['id'] ?? 0);
                return [
                    'id' => null,
                    'ibge_code' => $id,
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . strtolower($uf),
                    'state' => $uf,
                    'country' => 'Brasil',
                    'latitude' => null,
                    'longitude' => null,
                    'status' => 'active',
                    'is_active' => true,
                    'is_cariri_region' => in_array($uf . ':' . Str::slug($name), [
                        'ce:crato', 'ce:juazeiro-do-norte', 'ce:barbalha',
                    ], true),
                ];
            })->filter(fn (array $item): bool => $item['ibge_code'] > 0 && $item['name'] !== '')->values()->all();
        });

        $needle = $this->normalize($query);
        $result = collect($municipalities)
            ->filter(function (array $municipality) use ($needle): bool {
                return str_contains($this->normalize($municipality['name']), $needle)
                    || str_contains($this->normalize($municipality['state']), $needle)
                    || str_contains($this->normalize($municipality['name'] . ' ' . $municipality['state']), $needle);
            })
            ->sortBy(fn (array $municipality): string => $this->normalize($municipality['name']))
            ->take(30)
            ->values();

        return response()->json($result);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->toString();
    }
}
