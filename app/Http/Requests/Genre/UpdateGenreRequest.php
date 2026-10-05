<?php

namespace App\Http\Requests\Genre;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $genreId = $this->route('genre') instanceof \App\Models\Genre
            ? $this->route('genre')->id
            : $this->route('genre');

        return [
            'name' => 'required|string|max:100|unique:genres,name,' . $genreId,
            'description' => 'nullable|string',
        ];
    }
}
