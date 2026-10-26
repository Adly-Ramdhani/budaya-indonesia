<?php

namespace App\Http\Controllers;

use App\Models\Island;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IslandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->has('search')) {
            $islands = Island::where('name', 'ILIKE', '%' . request('search') . '%')->get();
        } else {
            $islands = Island::all();
        }

        return response()->json([
            'data' => $islands
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'name' => 'required|string|unique:islands,name',
            ])->validate();

            $island = Island::create($validated);

            return response()->json([
                'message' => 'Island created successfully',
                'data'    => $island,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Island $island)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Island $island)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Island $island)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Island $island)
    {
        //
    }
}
