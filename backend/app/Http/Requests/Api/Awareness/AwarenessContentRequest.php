<?php

namespace App\Http\Requests\Api\Awareness;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Content;

class AwarenessContentRequest extends FormRequest
{
    /**
     * تحديد الصلاحية
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function isUpdate(): bool
    {
        return $this->route('contentId') !== null;
    }

    /**
     * المحتوى الحالي بالداتابيز (null لو store جديد)
     */
    protected function existingContent(): ?Content
    {
        if (!$this->isUpdate()) {
            return null;
        }
        return Content::find($this->route('contentId'));
    }

    /**
     * النوع القديم (بالداتابيز) - null لو store جديد
     */
    protected function oldContentType(): ?string
    {
        return $this->existingContent()?->content_type;
    }

    /**
     * النوع "الفعلي" بعد هذا الطلب:
     * - لو انبعت content_type بالطلب => نستخدمه
     * - وإلا (update بدون تغيير النوع) => النوع الحالي بالداتابيز
     * - وإلا (store جديد بدون content_type بعد) => null
     */
    protected function effectiveContentType(): ?string
    {
        if ($this->filled('content_type')) {
            return $this->content_type;
        }

        return $this->oldContentType();
    }

    /**
     * هل هذا الطلب يتطلب رفع ملف جديد إجبارياً؟
     * (بمعنى: الملف القديم -لو موجود- لم يعد صالحاً أو غير موجود أصلاً)
     */
    protected function requiresNewFile(): bool
    {
        $newType = $this->effectiveContentType();

        // النوع الفعلي نصي => لا حاجة لملف إطلاقاً
        if ($newType === 'text') {
            return false;
        }

        // store جديد بنوع غير نصي => ملف مطلوب (نفس منطق required_unless الأصلي)
        if (!$this->isUpdate()) {
            return true;
        }

        $content = $this->existingContent();
        if (!$content) {
            return false; // لن يصل هنا عملياً (findOrFail بالـ controller بيمسكها لاحقاً)
        }

        $oldType = $content->content_type;

        // كان نصياً وصار نوع ملف => ما عندو ملف أصلاً، لازم يرفق
        if ($oldType === 'text') {
            return true;
        }

        // تغيّر النوع بين نوعي ملفات مختلفين => الملف القديم غير مطابق
        if ($newType !== $oldType) {
            return true;
        }

        // نفس النوع، لكن طلب حذف صريح بدون بديل
        if ($this->boolean('remove_file')) {
            return true;
        }

        // نفس النوع، ما فيه حذف => الملف القديم لا يزال صالحاً، غير مطلوب رفع جديد
        return false;
    }

    public function rules(): array
    {
        $isUpdate = $this->isUpdate();
        $effectiveType = $this->effectiveContentType();

        return [
            'title' => [$isUpdate ? 'sometimes' : 'required', 'required', 'string', 'max:255'],
            'description' => [$isUpdate ? 'sometimes' : 'required', 'required', 'string'],
            'content_type' => [$isUpdate ? 'sometimes' : 'required', 'required', 'in:pdf,image,text,video'],
            'content_category_type' => [$isUpdate ? 'sometimes' : 'required', 'required', 'in:awareness,motivational'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            'body' => [
                'nullable',
                'string',
                Rule::prohibitedIf($effectiveType !== 'text' && $this->filled('body'))
            ],

            'file' => [
                // الإلزامية: نفس منطق store بالضبط، بس محسوبة ديناميكياً حسب التغيير
                Rule::requiredIf(fn() => $this->requiresNewFile()),

                'file',

                Rule::when($effectiveType === 'video', 'max:51200', 'max:10240'),

                Rule::prohibitedIf($effectiveType === 'text' && $this->hasFile('file')),

                // القاعدة الوحيدة المتبقية كـ closure: فرض remove_file صراحة
                // عند التحويل من نوع ملف => text (حتى لا يبقى ملف "يتيم" بالداتابيز)
                function ($attribute, $value, $fail) use ($isUpdate) {
                    if (!$isUpdate) {
                        return;
                    }

                    $oldType = $this->oldContentType();
                    $newType = $this->effectiveContentType();

                    if ($oldType && $oldType !== 'text' && $newType === 'text' && !$this->boolean('remove_file')) {
                        $fail('عند التحويل إلى نوع نصي، يجب تفعيل خيار حذف الملف السابق (remove_file).');
                    }
                },

                // فحص الصيغة الحقيقية للملف حسب النوع الفعلي (نفس صرامة store)
                Rule::when($effectiveType === 'pdf', 'mimes:pdf'),
                Rule::when($effectiveType === 'image', 'mimes:jpeg,png,jpg,webp'),
                Rule::when($effectiveType === 'video', 'mimes:mp4,mov,avi,wmv,webm,m4v'),
            ],
        ];
    }

    /**
     * رسائل الخطأ
     */
    public function messages(): array
    {
        $effectiveType = $this->effectiveContentType();

        return [
            'body.prohibited' => 'لا يمكنك إضافة نص (body) إلا إذا كان نوع المحتوى نصياً (text).',
            'file.prohibited' => 'لا يمكنك رفع ملفات إذا كان نوع المحتوى نصياً (text).',
            'title.required'                => 'عنوان المحتوى مطلوب.',
            'description.required'          => 'وصف المحتوى مطلوب.',
            'content_type.in'               => 'نوع المحتوى غير صحيح.',
            'content_category_type.in'      => 'تصنيف المحتوى يجب أن يكون توعوياً أو تحفيزياً.',
            'file.required'                 => 'يجب إرفاق ملف لهذا النوع من المحتوى.',
            'file.max'                      => $effectiveType === 'video'
                ? 'حجم ملف الفيديو يجب ألا يتجاوز 50 ميجابايت.'
                : 'حجم الملف الأساسي يجب ألا يتجاوز 10 ميجابايت.',
            'cover_image.image'             => 'يجب أن يكون ملف الغلاف صورة.',
            'cover_image.max'               => 'حجم صورة الغلاف يجب ألا يتجاوز 5 ميجابايت.',
            'file.mimes' => $effectiveType === 'video'
                ? 'صيغة الفيديو المدعومة: MP4, MOV, AVI, WMV, WEBM.'
                : 'صيغة الملف غير متوافقة مع نوع المحتوى المختار.',
        ];
    }
}
