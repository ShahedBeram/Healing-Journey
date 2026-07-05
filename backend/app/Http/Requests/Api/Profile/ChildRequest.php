<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChildRequest extends FormRequest
{
    /**
     * تحديد صلاحية الطلب
     */
    public function authorize(): bool
    {
        // يجب أن تكون true ليعمل الـ Request
        // أو يمكنك وضع: return auth()->check(); للتأكد أن المستخدم مسجل دخول
        return true;
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        return [
            'child_name'      => 'required|string|max:100',
            'age'             => 'required|integer|min:0|max:18',
            'gender'          => ['required', Rule::in(['male', 'female'])],
            'health_status'   => 'required|string',
            'illness_story'   => 'nullable|string',
            'profile_picture' => [
                $this->isMethod('put') || $this->isMethod('patch') || $this->isMethod('post') ? 'nullable' : 'required',
                'image',
                'max:2048'
            ],
        ];
    }
}
