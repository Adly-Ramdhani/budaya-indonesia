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
                $query = Province::with('island:id,name')->orderBy('id');

                if ($request->filled('island_id')) {
                    $query->where('island_id', $request->island_id);
                }

                if ($request->filled('island')) {
                    $query->whereHas('island', fn ($q) => $q->whereRaw('LOWER(name) = ?', [strtolower($request->island)]));
                }

                return ApiFormatter::sendResponse(200, 'Success', $query->get());
            } catch (\Exception $e) {
                report($e);

                return ApiFormatter::sendResponse(500, 'Failed to retrieve provinces', []);
            }
    }

    public function categories(string $id)
    {
        try {
            $province = Province::with([
                'island:id,name',
                'foods',
                'traditionalHouses',
                'traditionalAttires',
                'traditionalWeapons',
            ])->find($id);

            if (! $province) {
                return ApiFormatter::sendResponse(404, 'Province not found', []);
            }

            $categories = collect([
                ['key' => 'foods',               'label' => 'Makanan Daerah',      'items' => $province->foods],
                ['key' => 'traditional-houses',  'label' => 'Rumah Adat',          'items' => $province->traditionalHouses],
                ['key' => 'traditional-attires', 'label' => 'Pakaian Adat',        'items' => $province->traditionalAttires],
                ['key' => 'traditional-weapons', 'label' => 'Senjata Tradisional', 'items' => $province->traditionalWeapons],
            ])
                ->map(fn ($c) => $c + ['count' => $c['items']->count()])
                ->where('count', '>', 0)
                ->values();

            return ApiFormatter::sendResponse(200, 'Success', [
                'province' => [
                    'id'     => $province->id,
                    'name'   => $province->name,
                    'island' => $province->island,
                ],
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(
                500,
                'Failed to retrieve categories',
                config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server, silakan coba lagi nanti.'
            );
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
