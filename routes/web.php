<?php

use App\Http\Controllers\ProvinceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('api/provinces/islands', [ProvinceController::class, 'islands']);
Route::apiResource('api/provinces', ProvinceController::class);