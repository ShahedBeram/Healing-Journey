<?php

namespace App\Http\Requests\Api\Specialist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // نتحقق مما إذا كان الطلب عبارة عن تحديث (إذا كان هناك id في الـ Route)
        $isUpdate = $this->route('id') !== null;

        return [
            'title'                => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description'          => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'date_time'            => [$isUpdate ? 'sometimes' : 'required', 'date', 'after:now'],
            'type'                 => [$isUpdate ? 'sometimes' : 'required', Rule::in(['session', 'activity'])],

            'session_category'     => [$isUpdate ? 'sometimes' : 'required', Rule::in(['nutrition', 'awareness', 'psychological', 'motivational'])],
            'target_audience'      => [$isUpdate ? 'sometimes' : 'required', Rule::in(['parents', 'child', 'parents and child'])],

            'participation_method' => [$isUpdate ? 'sometimes' : 'required', 'string'],

            'duration' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:15', 'max:300'],
            'cover_image'          => [$isUpdate ? 'nullable' : 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            'recovered_child_ids'   => 'nullable|array',
            'recovered_child_ids.*' => 'integer|exists:users,id,user_type,recovered_child',
            'join_link' => [$isUpdate ? 'sometimes' : 'nullable', 'url'],
            'form_link' => [$isUpdate ? 'sometimes' : 'nullable', 'url'],
        ];
    }
}
