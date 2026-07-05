<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\Request;


class VerificationController extends Controller
{
    // 1. القائمة الرئيسية (للجدول الطلبات المعلقة)
    public function index()
    {
        return User::where('account_status', 'pending')
            ->has('verificationDocuments')
            ->latest()
            ->paginate(10);
    }

    // 2. جلب كافة بيانات الطلب الواحد (لفتح الـ Popup)
    public function show($userId)
    {
        $user = User::with('verificationDocuments')->find($userId);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }
        return $user;
    }

    // 3. قبول الطلب
    public function approve(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }
        $user->update(['account_status' => 'approved', 'rejection_reason' => null]);
        $user->verificationDocuments()->update(['status' => 'approved']);

        return response()->json(['message' => 'تم قبول الحساب وتفعيله بنجاح']);
    }

    // 4. رفض الطلب (مع سبب الرفض)
    public function reject(Request $request, $userId)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        $user->update([
            'account_status'   => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);
        $user->verificationDocuments()->update(['status' => 'rejected']);

        return response()->json(['message' => 'تم رفض الحساب بنجاح']);
    }

    // 5. عرض المستندات (View)
    public function viewDocument($userId, $type)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }
        //  جلب سجل الوثائق الخاص بالمستخدم
        $doc = VerificationDocument::where('user_id', $userId)->firstOrFail();

        // جلب اسم الملف من قاعدة البيانات باستخدام الـ type الممرر في الرابط
        $fileName = $doc->getRawOriginal($type);

        //  التحقق من وجود القيمة (لأن الشهادة قد تكون null)
        if (!$fileName) {
            return response()->json(['message' => 'هذا المستند غير مرفوع لهذا المستخدم'], 404);
        }

        // بناء المسار والتحقق من وجود الملف فعلياً على السيرفر
        $path = storage_path('app/public/' . $fileName);

        if (!file_exists($path)) {
            return response()->json(['message' => 'الملف غير موجود في السيرفر'], 404);
        }

        return response()->file($path);
    }

    // 6. تحميل المستندات (Download)
    public function downloadDocument($userId, $type)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }
        // جلب سجل الوثائق للمستخدم
        $doc = VerificationDocument::where('user_id', $userId)->firstOrFail();

        //  جلب اسم الملف بناءً على النوع (certificate أو identity_card)
        $fileName = $doc->getRawOriginal($type);

        //  التحقق من وجود الملف في قاعدة البيانات (حالة الشهادة الاختيارية)
        if (!$fileName) {
            return response()->json(['message' => 'هذا المستند غير مرفوع لهذا المستخدم'], 404);
        }

        //  بناء المسار والتحقق من وجود الملف فعلياً على السيرفر
        $path = storage_path('app/public/' . $fileName);

        if (!file_exists($path)) {
            return response()->json(['message' => 'الملف غير موجود في السيرفر'], 404);
        }

        //  إرجاع استجابة التحميل
        return response()->download($path);
    }
}
