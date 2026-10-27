<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DanceController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\IslandController;
use App\Http\Controllers\MusicalInstrumentController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\TraditionalAttireController;
use App\Http\Controllers\TraditionalHouseController;
use App\Http\Controllers\TraditionalWeaponController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return response()->json([
        'status'  => 'ok',
        'message' => 'Budaya Indonesia API',
    ]);
});

Route::prefix('v1')->group(function () {

    // ---------- Auth (publik) ----------
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // ---------- Harus login (user atau admin) ----------
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // ---------- Publik: hanya baca ----------
    Route::get('provinces', [ProvinceController::class, 'index']);
    Route::get('provinces/categories/{id}', [ProvinceController::class, 'categories']);
    Route::get('provinces/{id}', [ProvinceController::class, 'show']);

    Route::get('islands', [IslandController::class, 'index']);

    Route::get('dances', [DanceController::class, 'index']);
    Route::get('dances/{id}', [DanceController::class, 'show']);

    Route::get('songs', [SongController::class, 'index']);
    Route::get('songs/{id}', [SongController::class, 'show']);

    Route::get('foods', [FoodController::class, 'index']);
    Route::get('foods/{id}', [FoodController::class, 'show']);

    Route::get('traditional-houses', [TraditionalHouseController::class, 'index']);
    Route::get('traditional-houses/{id}', [TraditionalHouseController::class, 'show']);

    Route::get('traditional-attires', [TraditionalAttireController::class, 'index']);
    Route::get('traditional-attires/{id}', [TraditionalAttireController::class, 'show']);

    Route::get('traditional-weapons', [TraditionalWeaponController::class, 'index']);
    Route::get('traditional-weapons/{id}', [TraditionalWeaponController::class, 'show']);

    Route::get('musical-instruments', [MusicalInstrumentController::class, 'index']);
    Route::get('musical-instruments/{id}', [MusicalInstrumentController::class, 'show']);

    // ---------- Khusus admin: tambah, ubah, hapus ----------
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {

        // Provinces & islands
        Route::post('provinces', [ProvinceController::class, 'store']);
        Route::put('provinces/{id}', [ProvinceController::class, 'update']);
        Route::delete('provinces/{id}', [ProvinceController::class, 'destroy']);
        Route::post('islands', [IslandController::class, 'store']);

        // Dances
        Route::post('dances', [DanceController::class, 'store']);
        Route::put('dances/{id}', [DanceController::class, 'update']);
        Route::delete('dances/{id}', [DanceController::class, 'destroy']);

        // Songs
        Route::post('songs', [SongController::class, 'store']);
        Route::put('songs/{id}', [SongController::class, 'update']);
        Route::delete('songs/{id}', [SongController::class, 'destroy']);

        // Foods
        Route::post('foods', [FoodController::class, 'store']);
        Route::put('foods/{id}', [FoodController::class, 'update']);
        Route::delete('foods/{id}', [FoodController::class, 'destroy']);

        // Traditional houses
        Route::post('traditional-houses', [TraditionalHouseController::class, 'store']);
        Route::put('traditional-houses/{id}', [TraditionalHouseController::class, 'update']);
        Route::delete('traditional-houses/{id}', [TraditionalHouseController::class, 'destroy']);

        // Traditional attires
        Route::post('traditional-attires', [TraditionalAttireController::class, 'store']);
        Route::put('traditional-attires/{id}', [TraditionalAttireController::class, 'update']);
        Route::delete('traditional-attires/{id}', [TraditionalAttireController::class, 'destroy']);

        // Traditional weapons
        Route::post('traditional-weapons', [TraditionalWeaponController::class, 'store']);
        Route::put('traditional-weapons/{id}', [TraditionalWeaponController::class, 'update']);
        Route::delete('traditional-weapons/{id}', [TraditionalWeaponController::class, 'destroy']);
        Route::patch('traditional-weapons/{id}/publish', [TraditionalWeaponController::class, 'publish']);
        Route::patch('traditional-weapons/{id}/unpublish', [TraditionalWeaponController::class, 'unpublish']);

        Route::post('musical-instruments', [MusicalInstrumentController::class, 'store']);
        Route::put('musical-instruments/{id}', [MusicalInstrumentController::class, 'update']);
        Route::delete('musical-instruments/{id}', [MusicalInstrumentController::class, 'destroy']);
        Route::patch('musical-instruments/{id}/publish', [MusicalInstrumentController::class, 'publish']);
        Route::patch('musical-instruments/{id}/unpublish', [MusicalInstrumentController::class, 'unpublish']);

    });
});

Route::get('/media/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);
    return Storage::disk('public')->response($path);
})->where('path', '.*');