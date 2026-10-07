<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Province::query()->orderBy('id');

            if ($request->filled('island')) {
                $island = collect(Province::ISLANDS)
                    ->first(fn ($i) => strcasecmp($i, $request->island) === 0);

                if (!$island) {
                    return ApiFormatter::sendResponse(404, 'Pulau tidak ditemukan', []);
                }

                $query->where('island', $island);
            }

            return ApiFormatter::sendResponse(200, 'Success', $query->get(['id', 'name', 'island']));
        } catch (\Exception $e) {
            report($e); // tulis error ke storage/logs/laravel.log

            return ApiFormatter::sendResponse(500, 'Failed to retrieve provinces', []);
        }
    }

    public function islands()
    {
        try {
            $available = Province::query()->distinct()->pluck('island')->all();

            // Urutkan sesuai urutan di UI, hanya pulau yang punya provinsi
            $islands = array_values(array_filter(
                Province::ISLANDS,
                fn ($i) => in_array($i, $available, true)
            ));

            return ApiFormatter::sendResponse(200, 'Success', $islands);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve islands', []);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Province $province)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Province $province)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Province $province)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Province $province)
    {
        //
    }
}
