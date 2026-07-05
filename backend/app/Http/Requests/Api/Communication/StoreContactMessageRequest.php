<?php

namespace App\Http\Requests\Api\Communication;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * تحديد صلاحية المستخدم
     */
    public function authorize(): bool
    {
        return true; //   للسماح بالجميع بالإرسال
    }

    /**
     * قوانين التحقق (Validation Rules)
     */
    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ];
    }
}
