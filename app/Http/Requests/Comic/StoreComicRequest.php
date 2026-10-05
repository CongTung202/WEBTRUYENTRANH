<?php

namespace App\Http\Requests\Comic;

use Illuminate\Foundation\Http\FormRequest;

class StoreComicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:comics,title',
            'author' => 'nullable|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ongoing,completed,dropped',
            'cover_image' => 'nullable|image|max:10240',
            'banner_image' => 'nullable|image|max:10240',
            'genres' => 'nullable|array',
            'genres.*' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
            'is_recommended' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }
}
