<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;


class RecoveredChildUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
        ];
    }
}
