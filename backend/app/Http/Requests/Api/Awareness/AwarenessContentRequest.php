<?php

namespace App\Http\Requests\Api\Awareness;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AwarenessContentRequest extends FormRequest
{
    /**
     * تحديد الصلاحية
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('contentId') !== null;

        return [
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'content_type' => [$isUpdate ? 'sometimes' : 'required', 'in:pdf,image,text,video'],
            'content_category_type' => [$isUpdate ? 'sometimes' : 'required', 'in:awareness,motivational'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            // 1. قاعدة الـ body: ممنوع إلا إذا كان النوع text
            'body' => [
                'nullable',
                'string',
                Rule::prohibitedIf($this->content_type !== 'text' && $this->filled('body'))
            ],

            // 2. قاعدة الـ file: ممنوع إذا كان النوع text
            'file' => [
                $isUpdate ? 'nullable' : 'required_unless:content_type,text',
                'file',
                'max:10240',

                Rule::prohibitedIf($this->content_type === 'text' && $this->hasFile('file')),
                Rule::when($this->content_type === 'pdf', 'mimes:pdf'),
                Rule::when($this->content_type === 'image', 'mimes:jpeg,png,jpg,webp'),
                Rule::when($this->content_type === 'video', 'mimes:mp4,mov,avi,wmv'),
            ]
        ];
    }
    /**
     * رسائل الخطأ 
     */
    public function messages(): array
    {
        return [
            'body.prohibited' => 'لا يمكنك إضافة نص (body) إلا إذا كان نوع المحتوى نصياً (text).',
            'file.prohibited' => 'لا يمكنك رفع ملفات إذا كان نوع المحتوى نصياً (text).',
            'title.required'                => 'عنوان المحتوى مطلوب.',
            'description.required'          => 'وصف المحتوى مطلوب.',
            'content_type.in'               => 'نوع المحتوى غير صحيح.',
            'content_category_type.in'      => 'تصنيف المحتوى يجب أن يكون توعوياً أو تحفيزياً.',
            'file.max'                      => 'حجم الملف الأساسي يجب ألا يتجاوز 10 ميجابايت.',
            'cover_image.image'             => 'يجب أن يكون ملف الغلاف صورة.',
            'cover_image.max'               => 'حجم صورة الغلاف يجب ألا يتجاوز 5 ميجابايت.',
            'file.mimes' => 'صيغة الملف غير متوافقة مع نوع المحتوى المختار.',
            'file.required_unless' => 'يجب إرفاق ملف لهذا النوع من المحتوى.',
        ];
    }
}
