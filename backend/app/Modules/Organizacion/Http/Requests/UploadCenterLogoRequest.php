<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Subida del logo del centro (RF-009).
 */
final class UploadCenterLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'version' => ['required', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Seleccioná una imagen para el logo.',
            'logo.image' => 'El archivo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser JPG, PNG o WebP.',
            'logo.max' => 'El logo no puede superar los 2 MB.',
        ];
    }
}
