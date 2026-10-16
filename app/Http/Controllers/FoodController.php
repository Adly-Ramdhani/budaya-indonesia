<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FoodController extends Controller
{
    private const WITH = ['province:id,name,island_id', 'province.island:id,name'];

    // GET /api/foods
    // GET /api/foods?province_id=11
    // GET /api/foods?island_id=<uuid>
    // GET /api/foods?search=rendang
    public function index(Request $request)
    {
        try {
            $query = Food::with(self::WITH)->orderBy('name');

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

            return ApiFormatter::sendResponse(500, 'Failed to retrieve foods', []);
        }
    }

    // POST /api/foods (form-data)
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'province_id'     => ['required', 'exists:provinces,id'],
                'name'            => ['required', 'string', 'max:255'],
                'description'     => ['nullable', 'string'],
                'ingredients'     => ['nullable', 'string'],
                'video_url'       => ['nullable', 'url', 'max:500'],
                'video_copyright' => ['nullable', 'string', 'max:255'],
                'image'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('foods', 'public');
            }

            $food = Food::create($data);

            return ApiFormatter::sendResponse(201, 'Food created', $food->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(
                500,
                'Failed to create food',
                config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server, silakan coba lagi nanti.'
            );
        }
    }

    // GET /api/foods/{id}
    public function show($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Food not found', null);
            }

            $food = Food::with(self::WITH)->find($id);

            if (!$food) {
                return ApiFormatter::sendResponse(404, 'Food not found', null);
            }

            return ApiFormatter::sendResponse(200, 'Success', $food);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve food', []);
        }
    }

    // PUT /api/foods/{id}  (kalau ada file: POST + field _method=PUT)
    public function update(Request $request, $id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Food not found', null);
            }

            $food = Food::find($id);

            if (!$food) {
                return ApiFormatter::sendResponse(404, 'Food not found', null);
            }

            $provinceId = $request->input('province_id', $food->province_id);

            $validator = Validator::make($request->all(), [
                'province_id'     => ['sometimes', 'required', 'exists:provinces,id'],
                'name'            => [
                    'sometimes', 'required', 'string', 'max:255',
                    Rule::unique('foods')->where('province_id', $provinceId)->ignore($food->id),
                ],
                'description'     => ['nullable', 'string'],
                'ingredients'     => ['nullable', 'string'],
                'video_url'       => ['nullable', 'url', 'max:500'],
                'video_copyright' => ['nullable', 'string', 'max:255'],
                'image'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if ($request->hasFile('image')) {
                if ($food->image_path) {
                    Storage::disk('public')->delete($food->image_path);
                }

                $data['image_path'] = $request->file('image')->store('foods', 'public');
            }

            $food->update($data);

            return ApiFormatter::sendResponse(200, 'Food updated', $food->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to update food', []);
        }
    }

    // DELETE /api/foods/{id}
    public function destroy($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Food not found', null);
            }

            $food = Food::find($id);

            if (!$food) {
                return ApiFormatter::sendResponse(404, 'Food not found', null);
            }

            if ($food->image_path) {
                Storage::disk('public')->delete($food->image_path);
            }

            $food->delete();

            return ApiFormatter::sendResponse(200, 'Food deleted', null);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to delete food', []);
        }
    }
}