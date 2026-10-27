
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/debug-app-key', function () {
    return response()->json([
        'environment' => app()->environment(),
        'env_key_exists' => !empty(env('APP_KEY')),
        'key_exists' => !empty(config('app.key')),
        'key_length' => strlen((string) config('app.key')),
        'env_file' => app()->environmentFilePath(),
        'config_cached' => app()->configurationIsCached(),
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
