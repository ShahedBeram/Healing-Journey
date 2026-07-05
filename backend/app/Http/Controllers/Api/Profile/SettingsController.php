<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemSetting;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    // عرض بيانات الإعدادات
    public function index(Request $request)
    {
        $user = $request->user();

        $userData = [
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'user_type' => $user->user_type,
        ];

        // الصورة تظهر في الإعدادات فقط للأدمن والمتبرع وولي الأمر
        if (in_array($user->user_type, ['admin', 'donor', 'parent'])) {
            $userData['profile_picture'] = $user->profile_picture;
        }

        return response()->json(['user' => $userData]);
    }
    public function updateGeneralSettings(Request $request)
    {
        $user = $request->user();

        if ($request->has('user_type')) {
            return response()->json(['message' => 'عذراً، نوع المستخدم غير قابل للتعديل.'], 403);
        }

        // 1. تعريف الفئات المصرح لها (ملاحظة: استخدمنا parent كما في قاعدة بياناتك)
        $allowedUsers = ['admin', 'donor', 'parent'];
        if (!in_array($user->user_type, $allowedUsers)) {
            // هذه هي الخطوة الأهم: مسح الملف من الطلب تماماً
            $request->files->remove('profile_picture');
        }
        // 2. القواعد الأساسية
        $rules = [
            'full_name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string',
            'job_title' => 'nullable|string',
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ];

        // 3. التحقق الصارم من الصورة
        if (in_array($user->user_type, $allowedUsers)) {
            $rules['profile_picture'] = 'sometimes|image|mimes:jpeg,png,jpg|max:2048';
        } else {
            // إذا حاول غير المصرح له إرسال صورة، نمنعه فوراً
            if ($request->hasFile('profile_picture')) {
                return response()->json(['message' => 'عذراً، لا يمكنك تحديث الصورة الشخصية من هذا المسار.'], 403);
            }
        }

        $request->validate($rules);

        // 4. تحديث البيانات الأساسية
        $user->fill($request->only(['full_name', 'email', 'phone', 'job_title']));

        // 5. تحديث كلمة المرور
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة'], 422);
            }
            $user->password = Hash::make($request->new_password);
        }

        // 6. تحديث الصورة (فقط للمصرح لهم)
        if ($request->hasFile('profile_picture') && in_array($user->user_type, $allowedUsers)) {
            if ($user->getRawOriginal('profile_picture')) {
                Storage::disk('public')->delete($user->getRawOriginal('profile_picture'));
            }
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $user->profile_picture = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'تم تحديث الإعدادات بنجاح',
            'user' => $user
        ]);
    }
    // جلب كل الإعدادات (لكل الزوار والمستخدمين)

    public function getSystemSettings()

    {

        $settings = SystemSetting::pluck('value', 'key');

        return response()->json(['settings' => $settings]);
    }



    // تعديل الإعدادات (للأدمن فقط)

    public function updateSystemSettings(Request $request)

    {

        // التحقق من أن المستخدم أدمن

        if ($request->user()->user_type !== 'admin') {

            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        // تحديث الاعدادات

        foreach ($request->all() as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
        return response()->json(['message' => 'تم تحديث إعدادات المنصة بنجاح']);
    }
}
