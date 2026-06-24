<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceMediaRequest extends FormRequest
{
    /**
     * Only authenticated admins can upload media
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check() && auth('sanctum')->user()->role === 'admin';
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'files'   => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,glb,gltf', 'max:20480'], // 20MB max per file
            'type'    => ['nullable', 'string', 'in:image,video,model3d'],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'files.required'    => 'Please provide at least one file to upload.',
            'files.array'       => 'Files must be sent as an array.',
            'files.min'         => 'Please provide at least one file.',
            'files.max'         => 'You can upload a maximum of 10 files at once.',
            'files.*.required'  => 'Each file entry must not be empty.',
            'files.*.file'      => 'Each entry must be a valid file.',
            'files.*.mimes'     => 'Allowed formats: jpg, jpeg, png, webp, mp4, glb, gltf.',
            'files.*.max'       => 'Each file must not exceed 20MB.',
            'type.in'           => 'Type must be one of: image, video, model3d.',
        ];
    }

    /**
     * Translated attribute names
     */
    public function attributes(): array
    {
        return [
            'files'   => 'media files',
            'files.*' => 'file',
            'type'    => 'media type',
        ];
    }
}
