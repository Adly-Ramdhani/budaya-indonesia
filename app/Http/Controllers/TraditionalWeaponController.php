<?php

namespace App\Http\Controllers;

use App\Enums\PublishStatus;
use App\Helpers\ApiFormatter;
use App\Models\TraditionalWeapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TraditionalWeaponController extends Controller
{
    private const WITH = ['province'];

    /* ===================== PUBLIK (hanya published) ===================== */

    public function publicIndex(Request $request)
    {
        $weapons = TraditionalWeapon::published()
            ->with(self::WITH)
            ->when($request->query('province_id'), fn ($q, $v) => $q->where('province_id', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest('published_at')
            ->get();

        return ApiFormatter::sendResponse(200, 'Traditional weapons retrieved', $weapons);
    }

    public function publicShow(string $id)
    {
        $weapon = TraditionalWeapon::published()->with(self::WITH)->find($id);

        if (! $weapon) {
            return ApiFormatter::sendResponse(404, 'Traditional weapon not found', []);
        }

        return ApiFormatter::sendResponse(200, 'Traditional weapon retrieved', $weapon);
    }

    /* ===================== ADMIN (semua data) ===================== */

    public function index(Request $request)
    {
        $weapons = TraditionalWeapon::with(self::WITH)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('province_id'), fn ($q, $v) => $q->where('province_id', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->get();

        return ApiFormatter::sendResponse(200, 'Traditional weapons retrieved', $weapons);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'province_id' => ['required', 'exists:provinces,id'],
                'name'        => ['required', 'string', 'max:255'],
                'description' => [
                    Rule::requiredIf(fn () => $request->input('status') === PublishStatus::Published->value),
                    'nullable',
                    'string',
                ],
                'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'status'      => ['sometimes', Rule::enum(PublishStatus::class)],
            ], [
                'description.required' => 'Deskripsi wajib diisi sebelum dipublikasikan.',
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');
            $data['status'] = $data['status'] ?? PublishStatus::Draft->value;
            $data['published_at'] = $data['status'] === PublishStatus::Published->value ? now() : null;

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('traditional-weapons', 'public');
            }

            $weapon = TraditionalWeapon::create($data);

            return ApiFormatter::sendResponse(
                201,
                $data['status'] === PublishStatus::Published->value
                    ? 'Traditional weapon published'
                    : 'Traditional weapon saved as draft',
                $weapon->load(self::WITH)
            );
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
                'status'      => ['sometimes', Rule::enum(PublishStatus::class)],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            // Status akhir: pakai yang dikirim, kalau tidak ada pakai yang lama
            $finalStatus = $data['status'] ?? $weapon->status->value;
            $finalDescription = array_key_exists('description', $data)
                ? $data['description']
                : $weapon->description;

            if ($finalStatus === PublishStatus::Published->value && blank($finalDescription)) {
                return ApiFormatter::sendResponse(422, 'Validation failed', [
                    'description' => ['Deskripsi wajib diisi sebelum dipublikasikan.'],
                ]);
            }

            if (isset($data['status'])) {
                $data['published_at'] = $finalStatus === PublishStatus::Published->value
                    ? ($weapon->published_at ?? now()) // pertahankan tanggal publish pertama
                    : null;
            }

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

    public function publish(string $id)
    {
        $weapon = TraditionalWeapon::find($id);

        if (! $weapon) {
            return ApiFormatter::sendResponse(404, 'Traditional weapon not found', []);
        }

        if (blank($weapon->description)) {
            return ApiFormatter::sendResponse(422, 'Validation failed', [
                'description' => ['Deskripsi wajib diisi sebelum dipublikasikan.'],
            ]);
        }

        $weapon->update([
            'status'       => PublishStatus::Published,
            'published_at' => $weapon->published_at ?? now(),
        ]);

        return ApiFormatter::sendResponse(200, 'Traditional weapon published', $weapon->load(self::WITH));
    }

    public function unpublish(string $id)
    {
        $weapon = TraditionalWeapon::find($id);

        if (! $weapon) {
            return ApiFormatter::sendResponse(404, 'Traditional weapon not found', []);
        }

        $weapon->update([
            'status'       => PublishStatus::Draft,
            'published_at' => null,
        ]);

        return ApiFormatter::sendResponse(200, 'Traditional weapon set to draft', $weapon->load(self::WITH));
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