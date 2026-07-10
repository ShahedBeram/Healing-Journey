<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;


class RecoveredChildUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $steps = $this->input('journey_steps');
        if (is_string($steps)) {
            $decoded = json_decode($steps, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['journey_steps' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            // حقول بطاقة التعافي
            'age' => 'nullable|integer|min:0|max:100',
            'recovery_duration' => 'nullable|string|max:100',
            'cancer_type' => 'nullable|string|max:100',
            'recovery_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'recovery_story' => 'nullable|string',
            'journey_steps' => 'nullable|array',
            'journey_steps.*.label' => 'nullable|string|max:100',
            'journey_steps.*.sub' => 'nullable|string|max:150',
        ];
    }
}
