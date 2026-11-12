<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class ImageUploadController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $file = $data['image'];

        $manager = ImageManager::usingDriver(Driver::class);

        $image = $manager->decode($file->getRealPath());

        $image->scaleDown(
            width: 1920,
            height: 1920
        );

        $webp = $image->encodeUsingFileExtension(
            'webp',
            quality: 82
        );

        $path = sprintf(
            'uploads/users/%d/%s.webp',
            (int) $request->user()->id,
            Str::uuid()
        );

        Storage::disk('public')->put($path, (string) $webp);

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'mime' => 'image/webp',
            'size' => Storage::disk('public')->size($path),
        ], 201);
    }
}
