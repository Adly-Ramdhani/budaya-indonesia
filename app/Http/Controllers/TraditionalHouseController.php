<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\TraditionalHouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TraditionalHouseController extends Controller
{
    private const WITH = ['province:id,name,island_id', 'province.island:id,name'];

    public function index(Request $request)
    {
        try {
            $query = TraditionalHouse::with(self::WITH)->orderBy('name');

            if ($request->filled('province_id')) {
                $query->where('province_id', $request->province_id);
            }

            if ($request->filled('island_id') && Str::isUuid($request->island_id)) {
                $query->whereHas('province', fn ($q) => $q->where('island_id', $request->island_id));
            }

            if ($request->filled('search')) {
                $query->where('name', 'ILIKE', '%' . $request->search . '%');
            }

            return ApiFormatter::sendResponse(200, 'Success', $query->get());
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve traditional houses', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // POST /api/traditional-houses (form-data)
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'province_id' => ['required', 'exists:provinces,id'],
                'name'        => ['required', 'string', 'max:255',],
                'description' => ['nullable', 'string'],
                'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('traditional-houses', 'public');
            }

            $house = TraditionalHouse::create($data);

            return ApiFormatter::sendResponse(201, 'Traditional house created', $house->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to create traditional house', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // GET /api/traditional-houses/{id}
    public function show($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Traditional house not found', null);
            }

            $house = TraditionalHouse::with(self::WITH)->find($id);

            if (!$house) {
                return ApiFormatter::sendResponse(404, 'Traditional house not found', null);
            }

            return ApiFormatter::sendResponse(200, 'Success', $house);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve traditional house', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // PUT /api/traditional-houses/{id}  (kalau ada file: POST + field _method=PUT)
    public function update(Request $request, $id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Traditional house not found', null);
            }

            $house = TraditionalHouse::find($id);

            if (!$house) {
                return ApiFormatter::sendResponse(404, 'Traditional house not found', null);
            }

            $provinceId = $request->input('province_id', $house->province_id);

            $validator = Validator::make($request->all(), [
                'province_id' => ['sometimes', 'required', 'exists:provinces,id'],
                'name'        => ['sometimes', 'required', 'string', 'max:255' ],
                'description' => ['nullable', 'string'],
                'history'     => ['nullable', 'string'],
                'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                if ($house->image_path) {
                    Storage::disk('public')->delete($house->image_path);
                }

                $data['image_path'] = $request->file('image')->store('traditional-houses', 'public');
            }

            $house->update($data);

            return ApiFormatter::sendResponse(200, 'Traditional house updated', $house->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to update traditional house', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // DELETE /api/traditional-houses/{id}
    public function destroy($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Traditional house not found', null);
            }

            $house = TraditionalHouse::find($id);

            if (!$house) {
                return ApiFormatter::sendResponse(404, 'Traditional house not found', null);
            }

            if ($house->image_path) {
                Storage::disk('public')->delete($house->image_path);
            }

            $house->delete();

            return ApiFormatter::sendResponse(200, 'Traditional house deleted', null);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to delete traditional house', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }
}