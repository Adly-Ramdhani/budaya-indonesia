<?php

namespace App\Http\Controllers;

use App\Enums\PublishStatus;
use App\Helpers\ApiFormatter;
use App\Models\MusicalInstrument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MusicalInstrumentController extends Controller
{
    private const WITH = ['province', 'island'];

    /* ===================== PUBLIK (hanya published) ===================== */

    public function publicIndex(Request $request)
    {
        $instruments = MusicalInstrument::published()
            ->with(self::WITH)
            ->when($request->query('province_id'), fn ($q, $v) => $q->where('province_id', $v))
            ->when($request->query('island_id'), fn ($q, $v) => $q->where('island_id', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest('published_at')
            ->get();

        return ApiFormatter::sendResponse(200, 'Musical instruments retrieved', $instruments);
    }

    public function publicShow(string $id)
    {
        $instrument = MusicalInstrument::published()->with(self::WITH)->find($id);

        if (! $instrument) {
            return ApiFormatter::sendResponse(404, 'Musical instrument not found', []);
        }

        return ApiFormatter::sendResponse(200, 'Musical instrument retrieved', $instrument);
    }

    /* ===================== ADMIN (semua data) ===================== */

    public function index(Request $request)
    {
        $instruments = MusicalInstrument::with(self::WITH)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('province_id'), fn ($q, $v) => $q->where('province_id', $v))
            ->when($request->query('island_id'), fn ($q, $v) => $q->where('island_id', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->get();

        return ApiFormatter::sendResponse(200, 'Musical instruments retrieved', $instruments);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'island_id'   => ['required', 'uuid', 'exists:islands,id'],
                'province_id' => ['required', 'exists:provinces,id'],
                'name'        => ['required', 'string', 'max:255'],
                'description' => [
                    Rule::requiredIf(fn () => $request->input('status') === PublishStatus::Published->value),
                    'nullable',
                    'string',
                ],
                'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'video_url'   => ['nullable', 'url', 'max:255'],
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
                $data['image_path'] = $request->file('image')->store('musical-instruments', 'public');
            }

            $instrument = MusicalInstrument::create($data);

            return ApiFormatter::sendResponse(
                201,
                $data['status'] === PublishStatus::Published->value
                    ? 'Musical instrument published'
                    : 'Musical instrument saved as draft',
                $instrument->load(self::WITH)
            );
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(
                500,
                'Failed to create musical instrument',
                config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server, silakan coba lagi nanti.'
            );
        }
    }

    public function show(string $id)
    {
        $instrument = MusicalInstrument::with(self::WITH)->find($id);

        if (! $instrument) {
            return ApiFormatter::sendResponse(404, 'Musical instrument not found', []);
        }

        return ApiFormatter::sendResponse(200, 'Musical instrument retrieved', $instrument);
    }

    public function update(Request $request, string $id)
    {
        try {
            $instrument = MusicalInstrument::find($id);

            if (! $instrument) {
                return ApiFormatter::sendResponse(404, 'Musical instrument not found', []);
            }

            $validator = Validator::make($request->all(), [
                'island_id'   => ['sometimes', 'required', 'uuid', 'exists:islands,id'],
                'province_id' => ['sometimes', 'required', 'exists:provinces,id'],
                'name'        => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'video_url'   => ['nullable', 'url', 'max:255'],
                'status'      => ['sometimes', Rule::enum(PublishStatus::class)],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('image');

            // Status akhir: pakai yang dikirim, kalau tidak ada pakai yang lama
            $finalStatus = $data['status'] ?? $instrument->status->value;
            $finalDescription = array_key_exists('description', $data)
                ? $data['description']
                : $instrument->description;

            if ($finalStatus === PublishStatus::Published->value && blank($finalDescription)) {
                return ApiFormatter::sendResponse(422, 'Validation failed', [
                    'description' => ['Deskripsi wajib diisi sebelum dipublikasikan.'],
                ]);
            }

            if (isset($data['status'])) {
                $data['published_at'] = $finalStatus === PublishStatus::Published->value
                    ? ($instrument->published_at ?? now()) // pertahankan tanggal publish pertama
                    : null;
            }

            if ($request->hasFile('image')) {
                if ($instrument->image_path) {
                    Storage::disk('public')->delete($instrument->image_path);
                }

                $data['image_path'] = $request->file('image')->store('musical-instruments', 'public');
            }

            $instrument->update($data);

            return ApiFormatter::sendResponse(200, 'Musical instrument updated', $instrument->load(self::WITH));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(
                500,
                'Failed to update musical instrument',
                config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server, silakan coba lagi nanti.'
            );
        }
    }

    public function publish(string $id)
    {
        $instrument = MusicalInstrument::find($id);

        if (! $instrument) {
            return ApiFormatter::sendResponse(404, 'Musical instrument not found', []);
        }

        if (blank($instrument->description)) {
            return ApiFormatter::sendResponse(422, 'Validation failed', [
                'description' => ['Deskripsi wajib diisi sebelum dipublikasikan.'],
            ]);
        }

        $instrument->update([
            'status'       => PublishStatus::Published,
            'published_at' => $instrument->published_at ?? now(),
        ]);

        return ApiFormatter::sendResponse(200, 'Musical instrument published', $instrument->load(self::WITH));
    }

    public function unpublish(string $id)
    {
        $instrument = MusicalInstrument::find($id);

        if (! $instrument) {
            return ApiFormatter::sendResponse(404, 'Musical instrument not found', []);
        }

        $instrument->update([
            'status'       => PublishStatus::Draft,
            'published_at' => null,
        ]);

        return ApiFormatter::sendResponse(200, 'Musical instrument set to draft', $instrument->load(self::WITH));
    }

    public function destroy(string $id)
    {
        $instrument = MusicalInstrument::find($id);

        if (! $instrument) {
            return ApiFormatter::sendResponse(404, 'Musical instrument not found', []);
        }

        if ($instrument->image_path) {
            Storage::disk('public')->delete($instrument->image_path);
        }

        $instrument->delete();

        return ApiFormatter::sendResponse(200, 'Musical instrument deleted', []);
    }
}