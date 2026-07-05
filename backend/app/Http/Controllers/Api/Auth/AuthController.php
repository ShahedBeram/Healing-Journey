<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\ParentModel;
use App\Models\Donor;
use App\Models\AwarenessSpecialist;
use App\Models\RecoveredChild;
use App\Models\VerificationDocument;


class AuthController extends Controller
{
    // REGISTER
    public function register(RegisterRequest $request)
    {
        // 1. التحقق من وجود المستخدم
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            // إذا كان مرفوضاً، نحذف الحساب القديم ليتمكن من التسجيل من جديد
            // ملاحظة: تأكدي أن الـ Migration للمستخدم يحتوي على onUpdate('cascade')->onDelete('cascade')
            if ($existingUser->account_status === 'rejected') {
                $existingUser->delete();
            } else {
                // إذا كان مقبولاً أو معلقاً، نمنع التسجيل
                return response()->json([
                    'message' => 'هذا البريد الإلكتروني مسجل بالفعل وحالته ' . $existingUser->account_status
                ], 422);
            }
        }

        // 2. البدء بعملية الإنشاء باستخدام Transaction لضمان سلامة البيانات
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['phone'] = str_replace(['+', ' '], '', $data['phone']);

            // معالجة صورة الملف الشخصي
            if ($request->hasFile('profile_picture')) {
                $data['profile_picture'] = $request->file('profile_picture')->store('profiles', 'public');
            }

            // 3. إنشاء المستخدم
            $user = User::create([
                'full_name'       => $data['full_name'],
                'email'           => $data['email'],
                'password'        => Hash::make($data['password']),
                'phone'           => $data['phone'],
                'job_title'       => $data['job_title'] ?? null,
                'profile_picture' => $data['profile_picture'] ?? null,
                'user_type'       => $data['user_type'],
                'account_status'  => $data['user_type'] === 'parent' ? 'approved' : 'pending',
            ]);

            // 4. إنشاء البروفايل الخاص بكل نوع
            if ($user->user_type === 'parent') {
                ParentModel::create(['user_id' => $user->id]);
            } elseif ($user->user_type === 'donor') {
                Donor::create(['user_id' => $user->id]);
            } elseif ($user->user_type === 'specialist') {
                AwarenessSpecialist::create([
                    'user_id'   => $user->id,
                    'specialty' => $request->specialty ?? null,
                    'bio'       => $request->bio ?? null,
                ]);
            } elseif ($user->user_type === 'recovered_child') {
                RecoveredChild::create(['user_id' => $user->id]);
            }

            // 5. رفع مستندات التحقق (لغير الـ Parent)
            if ($user->user_type !== 'parent') {
                $identityPath = $request->file('identity_card')->store('verification', 'public');

                $certificatePath = null;
                if ($request->hasFile('certificate')) {
                    $certificatePath = $request->file('certificate')->store('verification', 'public');
                }

                VerificationDocument::create([
                    'user_id'       => $user->id,
                    'identity_card' => $identityPath,
                    'certificate'   => $certificatePath,
                    'status'        => 'pending',
                ]);
            }

            return response()->json([
                'message' => $user->account_status === 'pending'
                    ? 'تم تسجيل حسابك بنجاح، بانتظار مراجعة الأدمن.'
                    : 'تم تفعيل حسابك بنجاح، أهلاً بك!',
                'user'    => $user->load(['verificationDocuments' => function ($query) {
                    $query->select('id', 'user_id', 'identity_card', 'certificate');
                }])
            ], 201);
        });
    }
    // Login 
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        // التحقق من حالة الحساب
        if ($user->account_status === 'pending') {
            return response()->json(['message' => 'حسابك لا يزال قيد المراجعة من قبل الإدارة.'], 403);
        }

        if ($user->account_status === 'rejected') {
            return response()->json([
                'message' => 'عذراً، تم رفض طلبك.',
                'rejection_reason' => $user->rejection_reason // هنا يظهر السبب الذي كتبه الأدمن
            ], 403);
        }

        // تحديث وقت آخر دخول
        $user->update(['last_login_at' => now()]);

        // إنشاء الـ Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user'    => $user,
            'token'   => $token
        ]);
    }
    public function logout(Request $request)
    {
        // حذف الـ Token الخاص بالجهاز الذي قام بطلب تسجيل الخروج
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ], 200);
    }
}
