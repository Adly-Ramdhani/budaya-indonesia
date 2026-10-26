<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

            if ($request->filled('search')) {
                $query->where('title', 'ILIKE', '%' . $request->search . '%');
            }

            return ApiFormatter::sendResponse(200, 'Success', $query->get());
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to retrieve songs', []);
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
        try {
            $validator = Validator::make($request->all(), [
                'province_id' => ['required', 'exists:provinces,id'],
                'title'       => ['required', 'string', 'max:255' ],
                'composer'    => ['nullable', 'string', 'max:255'],
                'language'    => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'lyrics'      => ['nullable', 'string'],
                'audio'       => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:10240'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('audio');

            if ($request->hasFile('audio')) {
                $data['audio_path'] = $request->file('audio')->store('songs/audio', 'public');
            }

            $song = Song::create($data);

            return ApiFormatter::sendResponse(201, 'Song created', $song->load('province:id,name,island_id'));
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to create song', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Song $song)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
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
                'audio'       => ['nullable', 'file', 'mimes:mp3,mpga,wav,ogg,m4a', 'max:10240'],
            ]);

            if ($validator->fails()) {
                return ApiFormatter::sendResponse(422, 'Validation failed', $validator->errors());
            }

            $data = $validator->safe()->except('audio');

            if ($request->hasFile('audio')) {
                if ($song->audio_path) {
                    Storage::disk('public')->delete($song->audio_path); // hapus audio lama
                }

                $data['audio_path'] = $request->file('audio')->store('songs/audio', 'public');
            }

            $song->update($data);

            return ApiFormatter::sendResponse(
                200,
                'Song updated',
                $song->load(['province:id,name,island_id', 'province.island:id,name'])
            );
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to update song', []);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
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

            // hapus file audio dari storage (kalau ada)
            if ($song->audio_path) {
                Storage::disk('public')->delete($song->audio_path);
            }

            $song->delete();

            return ApiFormatter::sendResponse(200, 'Song deleted', null);
        } catch (\Exception $e) {
            report($e);

            return ApiFormatter::sendResponse(500, 'Failed to delete song', []);
        }
    }
}
