<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\TraditionalWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TraditionalWeaponController extends Controller
{
    private const WITH = ['province'];

    public function index()
    {
        $weapons = TraditionalWeapon::with(self::WITH)->latest()->get();

        return ApiFormatter::sendResponse(200, 'Traditional weapons retrieved', $weapons);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'province_id' => ['required', 'exists:provinces,id'],
                'name'        => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image_path'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('traditional-weapons', 'public');
            }

            $weapon = TraditionalWeapon::create($data);

            return ApiFormatter::sendResponse(201, 'Traditional weapon created', $weapon->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(
                500,
                'Failed to create traditional weapon',
                config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server, silakan coba lagi nanti.'
            );
        }
    }

    public function show(string $id)
    {
        $weapon = TraditionalWeapon::with(self::WITH)->find($id);

        if (! $weapon) {
            return ApiFormatter::sendResponse(404, 'Traditional weapon not found', []);
        }

        return ApiFormatter::sendResponse(200, 'Traditional weapon retrieved', $weapon);
    }

    public function update(Request $request, string $id)
    {
        try {
            $weapon = TraditionalWeapon::find($id);

            if (! $weapon) {
                return ApiFormatter::sendResponse(404, 'Traditional weapon not found', []);
            }

            $validator = Validator::make($request->all(), [
                'province_id' => ['sometimes', 'required', 'exists:provinces,id'],
                'name'        => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                if ($weapon->image_path) {
                    Storage::disk('public')->delete($weapon->image_path);
                }

                $data['image_path'] = $request->file('image')->store('traditional-weapons', 'public');
            }

            $weapon->update($data);

            return ApiFormatter::sendResponse(200, 'Traditional weapon updated', $weapon->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(
                500,
                'Failed to update traditional weapon',
                config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server, silakan coba lagi nanti.'
            );
        }
    }

    public function destroy(string $id)
    {
        $weapon = TraditionalWeapon::find($id);

        if (! $weapon) {
            return ApiFormatter::sendResponse(404, 'Traditional weapon not found', []);
        }

        if ($weapon->image_path) {
            Storage::disk('public')->delete($weapon->image_path);
        }

        $weapon->delete();

        return ApiFormatter::sendResponse(200, 'Traditional weapon deleted', []);
    }
}