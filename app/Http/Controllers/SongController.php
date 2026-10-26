<?php

namespace App\Http\Controllers;

use App\Enums\PublishStatus;
use App\Helpers\ApiFormatter;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SongController extends Controller
{
    private const STATUSES = ['draft', 'published'];

    public function index(Request $request)
    {
        try {
            $query = Song::with('province:id,name,island_id')->orderBy('title');

            if ($request->filled('province_id')) {
                $query->where('province_id', $request->province_id);
            }

            if ($request->filled('island_id')) {
                $query->whereHas('province', fn ($q) => $q->where('island_id', $request->island_id));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                // escape karakter wildcard agar input "%" atau "_" tidak dianggap pola
                $search = addcslashes($request->search, '%_\\');
                $query->where('title', 'ILIKE', "%{$search}%");
            }

            return ApiFormatter::sendResponse(200, 'Success', $query->get());
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve songs', []);
        }
    }

    public function store(Request $request)
    {
        $audioPath = null;

        try {
            $validator = Validator::make($request->all(), [
                'province_id' => ['required', 'exists:provinces,id'],
                'title'       => ['required', 'string', 'max:255'],
                'composer'    => ['nullable', 'string', 'max:255'],
                'language'    => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'lyrics'      => ['nullable', 'string'],
                'status'      => ['sometimes', Rule::enum(PublishStatus::class)],
                'audio'       => ['nullable', 'file', 'mimes:mp3,mpga,wav,ogg,m4a', 'max:10240'],
            ]);

                if ($validator->fails()) {
                    return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
                }

                $data = $validator->safe()->except('audio');

                $status = PublishStatus::from($data['status']);

                $data['status']       = $status->value;
                $data['published_at'] = $status === PublishStatus::Published ? now() : null;

                if ($request->hasFile('audio')) {
                    $audioPath = $request->file('audio')->store('songs/audio', 'public');
                    $data['audio_path'] = $audioPath;
                }

            $song = Song::create($data);

            return ApiFormatter::sendResponse(201, 'Song created', $song->load('province:id,name,island_id'));
        } catch (\Exception $e) {
            report($e);

            // hapus file yang sudah terlanjur diupload jika gagal menyimpan ke DB
            if ($audioPath) {
                Storage::disk('public')->delete($audioPath);
            }

            return ApiFormatter::sendResponse(500, 'Failed to create song', []);
        }
    }

    public function show($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Song not found', null);
            }

            $song = Song::with('province:id,name,island_id')->find($id);

            if (!$song) {
                return ApiFormatter::sendResponse(404, 'Song not found', null);
            }

            return ApiFormatter::sendResponse(200, 'Success', $song);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve song', []);
        }
    }

    public function update(Request $request, $id)
    {
        $newAudioPath = null;

        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Song not found', null);
            }

            $song = Song::find($id);

            if (!$song) {
                return ApiFormatter::sendResponse(404, 'Song not found', null);
            }

            $provinceId = $request->input('province_id', $song->province_id);

            $validator = Validator::make($request->all(), [
                'province_id' => ['sometimes', 'required', 'exists:provinces,id'],
                'title'       => [
                    'sometimes', 'required', 'string', 'max:255',
                    Rule::unique('songs')->where('province_id', $provinceId)->ignore($song->id),
                ],
                'composer'    => ['nullable', 'string', 'max:255'],
                'language'    => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'lyrics'      => ['nullable', 'string'],
                'status'      => ['sometimes', 'required', Rule::in(self::STATUSES)],
                'audio'       => ['nullable', 'file', 'mimes:mp3,mpga,wav,ogg,m4a', 'max:10240'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('audio');

            // published_at diatur otomatis mengikuti perubahan status
            if (isset($data['status'])) {
                if ($data['status'] === 'published' && $song->status !== 'published') {
                    $data['published_at'] = now();
                } elseif ($data['status'] !== 'published') {
                    $data['published_at'] = null;
                }
            }

            $oldAudioPath = $song->audio_path;

            if ($request->hasFile('audio')) {
                $newAudioPath = $request->file('audio')->store('songs/audio', 'public');
                $data['audio_path'] = $newAudioPath;
            }

            $song->update($data);

            // hapus audio lama hanya setelah update ke DB berhasil
            if ($newAudioPath && $oldAudioPath) {
                Storage::disk('public')->delete($oldAudioPath);
            }

            return ApiFormatter::sendResponse(
                200,
                'Song updated',
                $song->load(['province:id,name,island_id', 'province.island:id,name'])
            );
        } catch (\Exception $e) {
            report($e);

            if ($newAudioPath) {
                Storage::disk('public')->delete($newAudioPath);
            }

            return ApiFormatter::sendResponse(500, 'Failed to update song', []);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Str::isUuid($id)) {
                return ApiFormatter::sendResponse(404, 'Song not found', null);
            }

            $song = Song::find($id);

            if (!$song) {
                return ApiFormatter::sendResponse(404, 'Song not found', null);
            }

            $audioPath = $song->audio_path;

            $song->delete();

            // hapus file setelah data di DB berhasil dihapus
            if ($audioPath) {
                Storage::disk('public')->delete($audioPath);
            }

            return ApiFormatter::sendResponse(200, 'Song deleted', null);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to delete song', []);
        }
    }
}