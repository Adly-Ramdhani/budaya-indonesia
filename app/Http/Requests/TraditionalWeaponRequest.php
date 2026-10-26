<?php

namespace App\Http\Requests;

use App\Enums\PublishStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TraditionalWeaponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => [
                Rule::requiredIf(fn () => $this->input('status') === PublishStatus::Published->value),
                'nullable',
                'string',
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::enum(PublishStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Deskripsi wajib diisi sebelum dipublikasikan.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}