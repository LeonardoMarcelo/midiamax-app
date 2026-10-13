<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Instagram;

use Illuminate\Foundation\Http\FormRequest;

class IndexInstagramAudioRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
