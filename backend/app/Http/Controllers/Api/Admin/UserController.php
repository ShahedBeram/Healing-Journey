<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // جلب المستخدمين (ما عدا المرفوضين)
    public function index(Request $request)
    {
        $query = User::query()
            ->select('id', 'full_name', 'email', 'account_status')
            ->whereIn('account_status', ['pending', 'approved']);

        // البحث بالاسم أو البريد الإلكتروني
        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        return response()->json(
            $query->latest()->paginate(3)
        );
    }

    // تفعيل (approved) أو تعطيل مؤقت (pending)
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        // منع تغيير حالة المستخدم المرفوض (rejected) من هنا
        if ($user->account_status === 'rejected') {
            return response()->json(['message' => 'لا يمكن تغيير حالة حساب مرفوض'], 403);
        }

        // التبديل بين approved و pending
        $newStatus = ($user->account_status === 'approved') ? 'pending' : 'approved';

        $user->update(['account_status' => $newStatus]);

        return response()->json([
            'message' => 'تم تغيير حالة المستخدم بنجاح',
            'new_status' => $newStatus
        ]);
    }
}
