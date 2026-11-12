<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CityController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PlaceController as PublicPlaceController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ContributionController;
use App\Http\Controllers\Api\BusinessClaimController as UserBusinessClaimController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\PlaceController as AdminPlaceController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\BusinessClaimController;
use App\Http\Controllers\Api\Admin\CityController as AdminCityController;
use App\Http\Controllers\Api\Admin\PlaceImportController;
use App\Http\Middleware\VerifyTurnstile;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\Api\ImageUploadController;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/uploads/images', [ImageUploadController::class, 'store'])
        ->middleware('throttle:20,1');

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'update']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);
    Route::delete('/me', [AuthController::class, 'destroy'])
        ->middleware('throttle:3,1');

    // demais rotas autenticadas...
});


Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'kariri-api',
    ]);
});

Route::get('/user', function (Request $request) {
    return response()->json($request->user());
})->middleware('auth:sanctum');

Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/search', [CityController::class, 'search']);
Route::get('/municipalities/search', [MunicipalityController::class, 'search'])
    ->middleware('throttle:60,1');
Route::get('/cities/{city:slug}', [CityController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);

Route::get('/places', [PublicPlaceController::class, 'index']);
Route::get('/places/nearby', [PublicPlaceController::class, 'nearby']);
Route::get('/places/{place:slug}', [PublicPlaceController::class, 'show']);
Route::get('/places/{place}/reviews', [ReviewController::class, 'index']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event:slug}', [EventController::class, 'show']);

Route::get('/search', SearchController::class);

//Route::post('/register', [AuthController::class, 'register'])->middleware([VerifyTurnstile::class, 'throttle:5,1']);
//Route::post('/login', [AuthController::class, 'login'])->middleware([VerifyTurnstile::class, 'throttle:10,1']);
//Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware([VerifyTurnstile::class, 'throttle:5,1']);
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware([VerifyTurnstile::class, 'throttle:3,60']);
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'update']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);
    Route::delete('/me', [AuthController::class, 'destroy'])->middleware('throttle:3,1');

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy']);

    Route::post('/places/{place}/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::post('/business-claims', [UserBusinessClaimController::class, 'store'])
        ->middleware('throttle:5,1');
    Route::get('/business-claims/mine', [UserBusinessClaimController::class, 'mine']);

    Route::post('/contributions/places', [ContributionController::class, 'storePlace'])
        ->middleware('throttle:10,1');
    Route::post('/contributions/events', [ContributionController::class, 'storeEvent'])
        ->middleware('throttle:10,1');
    Route::get('/contributions/mine', [ContributionController::class, 'mine']);
    Route::get('/contributions/mine/places', [ContributionController::class, 'myPlaces']);
    Route::get('/contributions/mine/events', [ContributionController::class, 'myEvents']);
});

Route::middleware(['auth:sanctum', 'verified', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('events', AdminEventController::class);
        Route::apiResource('places', AdminPlaceController::class);
        Route::apiResource('cities', AdminCityController::class);
        Route::apiResource('users', AdminUserController::class)->only(['index', 'update']);
        Route::post('/places/import', [PlaceImportController::class, 'store'])
            ->middleware('throttle:3,1');

        Route::get('/business-claims', [BusinessClaimController::class, 'index']);
        Route::put('/business-claims/{claim}', [BusinessClaimController::class, 'update']);
    });
