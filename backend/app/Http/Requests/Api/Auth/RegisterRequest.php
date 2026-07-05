<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:100',

            //  التحقق من الإيميل مع استثناء الحسابات المرفوضة
            'email' => [
                'required',
                'email',
            ],

            'password' => 'required|min:6|confirmed',
            'phone' => 'required|string|max:20|regex:/^\+?[0-9]{9,15}$/',
            'user_type' => 'required|in:parent,donor,specialist,recovered_child,admin',
            'job_title' => 'nullable|string|max:100',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // الهوية إجبارية لكل أنواع المستخدمين ما عدا الـ parent
            'identity_card' => [
                'required_unless:user_type,parent',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:5120' // 5MB
            ],

            // الشهادة اختيارية دائماً
            'certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ];
    }
}
