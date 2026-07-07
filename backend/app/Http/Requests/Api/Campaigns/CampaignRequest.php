<?php

namespace App\Http\Requests\Api\Campaigns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put')
            || $this->isMethod('patch')
            || ($this->isMethod('post') && $this->route('id'));

        return [
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'title'        => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description'  => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'start_date'   => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'end_date'     => [$isUpdate ? 'sometimes' : 'required', 'date', 'after:start_date'],

            // إضافة الحقول الجديدة
            'type'         => [$isUpdate ? 'sometimes' : 'required', Rule::in(['collect_donations', 'registration'])],
            'button_text'  => ['nullable', 'string', 'max:50'],
            'action_link'  => ['nullable', 'string', 'max:500'],
            'contact_info' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'category_id'  => ['nullable', 'exists:categories,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('action_link') && $this->input('action_link') === '') {
            $this->merge(['action_link' => null]);
        }
    }

    public function messages(): array
    {
        return [
            'end_date.after'     => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء.',
            'type.in'            => 'نوع الحملة غير صالح.',
            'contact_info.required' => 'يرجى إدخال معلومات التواصل الخاصة بالحملة.',
            'action_link.url'    => 'رابط الحملة يجب أن يكون رابطاً صحيحاً (يبدأ بـ http أو https).',
        ];
    }
}
