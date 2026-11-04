<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EnregistrerPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // l'accès est limité au rôle livreur par la route
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'precision_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'age_ms' => ['required', 'integer', 'min:0', 'max:3600000'],
        ];
    }
}
