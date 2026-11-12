<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => 'kariri-api',
    'status' => 'ok',
]));
