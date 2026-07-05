<?php

namespace App\Http\Requests\Api\Child;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentRequest extends FormRequest
{
    /**
     * تحديد الصلاحية (يمكنك ربطها بسياسة الصلاحيات لاحقاً)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        $isUpdate = $this->route('contentId') !== null;

        return [

            'title' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255'
            ],

            'description' => [
                $isUpdate ? 'sometimes' : 'required',
                'string'
            ],

            'content_type' => [
                $isUpdate ? 'sometimes' : 'required',
                'in:pdf,image,text,video'
            ],

            'content_category_type' => [
                $isUpdate ? 'sometimes' : 'required',
                'in:story,drawing,other'
            ],

            'cover_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120'
            ],


            'body' => [
                $this->content_type === 'text' ? 'required' : 'nullable',
                'string',

                Rule::prohibitedIf(
                    $this->content_type !== 'text'
                        && $this->filled('body')
                ),
            ],

            'file' => [

                $this->content_type !== 'text' && !$isUpdate
                    ? 'required'
                    : 'nullable',

                'file',
                'max:10240',

                Rule::prohibitedIf(
                    $this->content_type === 'text'
                        && $this->hasFile('file')
                ),

                Rule::when(
                    $this->content_type === 'pdf',
                    'mimes:pdf'
                ),

                Rule::when(
                    $this->content_type === 'image',
                    'mimes:jpeg,png,jpg,webp'
                ),

                Rule::when(
                    $this->content_type === 'video',
                    'mimes:mp4,mov,avi,wmv'
                ),
            ]
        ];
    }

    /**
     * رسائل الخطأ 
     */
    public function messages(): array
    {
        return [
            'title.required'        => 'عنوان المحتوى مطلوب.',
            'description.required'  => 'وصف المحتوى مطلوب.',
            'content_type.in'       => 'نوع المحتوى غير صحيح.',
            'file.max'              => 'حجم الملف الأساسي يجب ألا يتجاوز 10 ميجابايت.',
            'cover_image.image'     => 'يجب أن يكون ملف الغلاف صورة.',
            'cover_image.max'       => 'حجم صورة الغلاف يجب ألا يتجاوز 5 ميجابايت.',
            'body.prohibited' => 'يمكن إضافة النص فقط عندما يكون نوع المحتوى text.',
            'file.prohibited' => 'لا يمكن رفع ملف مع محتوى نصي.',
            'file.mimes' => 'صيغة الملف لا تتوافق مع نوع المحتوى.',
            'file.required_unless' => 'يجب إرفاق ملف لهذا النوع من المحتوى.',
        ];
    }
}
