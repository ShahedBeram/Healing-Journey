<?php

namespace App\Http\Requests\Api\Communication;

use Illuminate\Foundation\Http\FormRequest;

class ReplyContactMessageRequest extends FormRequest
{

    // (مستقبلاً يمكنك التحقق هنا إذا كان المستخدم 'admin')

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // يشترط أن يكون الرد نصاً وموجوداً (لا يمكن إرسال رد فارغ)
            'reply_text' => 'required|string|min:5',
        ];
    }


    public function messages(): array
    {
        return [
            'reply_text.required' => 'يجب كتابة نص للرد قبل الإرسال.',
            'reply_text.min' => 'يجب أن يكون نص الرد 5 أحرف على الأقل.',
        ];
    }
}
