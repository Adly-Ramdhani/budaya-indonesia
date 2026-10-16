<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\TraditionalAttire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TraditionalAttireController extends Controller
{
    private const WITH = ['province:id,name,island_id', 'province.island:id,name'];

    // GET /api/traditional-attires
    // GET /api/traditional-attires?province_id=13
    // GET /api/traditional-attires?island_id=<uuid>
    // GET /api/traditional-attires?search=kebaya
    public function index(Request $request)
    {
        try {
            $query = TraditionalAttire::with(self::WITH)->orderBy('name');

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

            return ApiFormatter::sendResponse(500, 'Failed to retrieve traditional attires', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // POST /api/traditional-attires (form-data)
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'province_id' => ['required', 'exists:provinces,id'],
                'name'        => [ 'required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image_path'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('traditional-attires', 'public');
            }

            $attire = TraditionalAttire::create($data);

            return ApiFormatter::sendResponse(201, 'Traditional attire created', $attire->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to create traditional attire', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // GET /api/traditional-attires/{id}
    public function show($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Traditional attire not found', null);
            }

            $attire = TraditionalAttire::with(self::WITH)->find($id);

            if (!$attire) {
                return ApiFormatter::sendResponse(404, 'Traditional attire not found', null);
            }

            return ApiFormatter::sendResponse(200, 'Success', $attire);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve traditional attire', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // PUT /api/traditional-attires/{id}  (kalau ada file: POST + field _method=PUT)
    public function update(Request $request, $id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Traditional attire not found', null);
            }

            $attire = TraditionalAttire::find($id);

            if (!$attire) {
                return ApiFormatter::sendResponse(404, 'Traditional attire not found', null);
            }

            $provinceId = $request->input('province_id', $attire->province_id);

            $validator = Validator::make($request->all(), [
                'province_id' => ['sometimes', 'required', 'exists:provinces,id'],
                'name'        => [ 'sometimes', 'required', 'string', 'max:255',
                ],
                'description' => ['nullable', 'string'],
                'history'     => ['nullable', 'string'],
                'image_path'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                if ($attire->image_path) {
                    Storage::disk('public')->delete($attire->image_path);
                }

                $data['image_path'] = $request->file('image')->store('traditional-attires', 'public');
            }

            $attire->update($data);

            return ApiFormatter::sendResponse(200, 'Traditional attire updated', $attire->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to update traditional attire', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }

    // DELETE /api/traditional-attires/{id}
    public function destroy($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Traditional attire not found', null);
            }

            $attire = TraditionalAttire::find($id);

            if (!$attire) {
                return ApiFormatter::sendResponse(404, 'Traditional attire not found', null);
            }

            if ($attire->image_path) {
                Storage::disk('public')->delete($attire->image_path);
            }

            $attire->delete();

            return ApiFormatter::sendResponse(200, 'Traditional attire deleted', null);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to delete traditional attire', $e->getMessage() ?: 'Terjadi kesalahan pada server, silakan coba lagi nanti.');
        }
    }
}