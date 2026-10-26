<?php

namespace App\Http\Controllers;

use App\Enums\PublishStatus;
use App\Helpers\ApiFormatter;
use App\Models\Dance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Dance::with('province:id,name,island_id')->orderBy('name');

            if ($request->filled('province_id')) {
                $query->where('province_id', $request->province_id);
            }

            if ($request->filled('island_id')) {
                $query->whereHas('province', fn ($q) => $q->where('island_id', $request->island_id));
            }

            if ($request->filled('search')) {
                $query->where('name', 'ILIKE', '%' . $request->search . '%');
            }

            // filter status hanya jika nilainya valid (draft / published)
            if ($request->filled('status') && $status = PublishStatus::tryFrom($request->status)) {
                $query->where('status', $status->value);
            }

            return ApiFormatter::sendResponse(200, 'Success', $query->get());
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve dances', []);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'province_id'  => ['required', 'exists:provinces,id'],
                'name'         => [ 'required', 'string', 'max:255'],
                'description'  => ['nullable', 'string'],
                'history'      => ['nullable', 'string'],
                'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // maks 2 MB
                'status'       => ['nullable', Rule::enum(PublishStatus::class)],
                'published_at' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            // status default draft; published_at hanya terisi jika published
            $status = PublishStatus::tryFrom($data['status'] ?? '') ?? PublishStatus::Draft;

            $data['status'] = $status->value;
            $data['published_at'] = $status === PublishStatus::Published
                ? ($data['published_at'] ?? now())
                : null;

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('dances', 'public');
            }

            $dance = Dance::create($data);

            return ApiFormatter::sendResponse(
                201,
                'Dance created',
                $dance->load('province:id,name,island_id')
            );
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to create dance', []);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Dance not found', null);
            }

            $dance = Dance::with('province:id,name,island_id')->find($id);

            if (!$dance) {
                return ApiFormatter::sendResponse(404, 'Dance not found', null);
            }

            return ApiFormatter::sendResponse(200, 'Success', $dance);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve dance', []);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Dance not found', null);
            }

            $dance = Dance::find($id);

            if (!$dance) {
                return ApiFormatter::sendResponse(404, 'Dance not found', null);
            }

            $provinceId = $request->input('province_id', $dance->province_id);

            $validator = Validator::make($request->all(), [
                'province_id'  => ['sometimes', 'required', 'exists:provinces,id'],
                'name'         => ['sometimes', 'required', 'string', 'max:255',],
                'description'  => ['nullable', 'string'],
                'history'      => ['nullable', 'string'],
                'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'status'       => ['sometimes', 'required', Rule::enum(PublishStatus::class)],
                'published_at' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            if (isset($data['status'])) {
                $status = PublishStatus::from($data['status']);

                $data['status'] = $status->value;

                if ($status === PublishStatus::Published) {
                    // tanggal dari request, atau tanggal terbit lama, atau sekarang
                    $data['published_at'] = $data['published_at'] ?? $dance->published_at ?? now();
                } else {
                    $data['published_at'] = null; // kembali ke draft
                }
            }

            if ($request->hasFile('image')) {
                if ($dance->image_path) {
                    Storage::disk('public')->delete($dance->image_path); // hapus gambar lama
                }

                $data['image_path'] = $request->file('image')->store('dances', 'public');
            }

            $dance->update($data);

            return ApiFormatter::sendResponse(
                200,
                'Dance updated',
                $dance->load(['province:id,name,island_id', 'province.island:id,name'])
            );
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to update dance', []);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Dance not found', null);
            }

            $dance = Dance::find($id);

            if (!$dance) {
                return ApiFormatter::sendResponse(404, 'Dance not found', null);
            }

            // hapus file gambar dari storage (kalau ada)
            if ($dance->image_path) {
                Storage::disk('public')->delete($dance->image_path);
            }

            $dance->delete();

            return ApiFormatter::sendResponse(200, 'Dance deleted', null);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to delete dance', []);
        }
    }
}