<?php

namespace App\Http\Controllers\Api\Profile;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Profile\RecoveredChildUpdateRequest;
use App\Http\Requests\Api\Profile\SpecialistUpdateRequest;
use App\Models\Invitation;


class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // جلب المعلومات الأساسية للمستخدم دائماً
        $data = [
            'user' => [
                'id'              => $user->id,
                'name'            => $user->full_name,
                'email'           => $user->email,
                'user_type'       => $user->user_type,
                'profile_picture' => $user->profile_picture,
                'status'          => $user->account_status,
            ]
        ];

        // جلب التفاصيل بناءً على النوع مع معالجة الحالات الفارغة
        switch ($user->user_type) {
            case 'parent':

                $user->load([
                    'parentProfile.children' => function ($query) {
                        $query->select(
                            'id',
                            'parent_id',
                            'child_name',
                            'age',
                            'profile_picture',
                            'health_status'
                        );
                    }
                ]);

                $data['details'] = $user->parentProfile;

                break;

            case 'specialist':
                $user->load('specialistProfile');
                $profile = $user->specialistProfile;
                $data['details'] = $profile ? [
                    'profile'  => $profile,
                    'contents' => $profile ? $profile->contents()
                        ->with(['category:id,slug', 'motivationalDetails'])
                        ->latest()
                        ->paginate(12) : [],
                    'sessions' => $profile ? $profile->sessions()->latest()->paginate(10) : []
                ] : null;
                break;

            case 'donor':
                $user->load('donorProfile');
                $profile = $user->donorProfile;
                $data['details'] = $profile ? [
                    'profile'   => $profile,
                    'campaigns' => $profile ? $profile->campaigns()->latest()->paginate(10) : []
                ] : null;
                break;

            case 'recovered_child':
                //  تحميل البروفايل
                $user->load('recoveredChildProfile');
                $profile = $user->recoveredChildProfile;

                //  التحقق من وجود البروفايل أولاً لتجنب الـ null
                if (!$profile) {
                    return response()->json(['message' => 'Profile not found'], 404);
                }

                //  استدعاء العلاقة من $profile (وليس من $user)
                $data['details'] = [
                    'profile'     => $profile,
                    'contents'    => $profile->contents()
                        ->with(['category:id,slug', 'motivationalDetails'])
                        ->latest()
                        ->paginate(3),

                    //  استدعاء joinedSessions من البروفايل نفسه
                    'sessions'    => $profile->joinedSessions()->latest('id')->paginate(3),


                    'invitations' => $profile->invitations()
                        ->with([
                            'session:id,title,date_time,created_by',
                            'session.creator:id,full_name'
                        ])
                        ->whereIn('status', ['sent', 'accepted', 'declined'])
                        ->latest()
                        ->paginate(1),

                    'statistics'  => [
                        'total_contents'   => $profile->contents()->count(),
                        'total_sessions'   => $profile->joinedSessions()->where('type', 'session')->count(),
                        'total_activities' => $profile->joinedSessions()->where('type', 'activity')->count(),
                        'total_impact'     => '250+',
                    ]
                ];
                break;
            default:
                $data['details'] = null;
                break;
        }

        return response()->json($data);
    }
    public function update(Request $request)
    {
        $user = $request->user();

        // إذا ولي أمر أو متبرع يُمنع من التعديل للبروفايل من هان 
        if (in_array($user->user_type, ['parent', 'donor'])) {
            return response()->json([
                'message' => 'عذراً، هذا المسار غير مخصص لك. يرجى استخدام إعدادات الحساب.'
            ], 403); // 403 Forbidden تعني ممنوع الوصول
        }

        // 1. التحقق من البيانات (Validation)

        if ($user->user_type === 'specialist') {
            $data = app(SpecialistUpdateRequest::class)->validated();
        } elseif ($user->user_type === 'recovered_child') {
            $data = app(RecoveredChildUpdateRequest::class)->validated();
        } else {
            $data = [];
        }

        // 2. تحديث الصورة الشخصية (هذا الجزء سيعمل للمتبرع وللجميع)
        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = $request->file('profile_picture')->store('profiles', 'public');
            $user->save();
        }

        // 3. التحديث للبيانات الخاصة (المتبرع سيتخطى هذا الجزء )
        switch ($user->user_type) {
            case 'specialist':
                $user->specialistProfile->update($data);
                break;

            case 'recovered_child':
                $user->recoveredChildProfile->update($data);
                break;

            case 'donor':
                // بيانات مستقبلية
                break;
        }

        return response()->json(['message' => 'تم تحديث الملف الشخصي بنجاح']);
    }

    public function handleInvitation(Request $request, $invitationId)
    {
        $request->validate([
            'status' => 'required|in:sent,accepted,declined'
        ]);

        $invitation = Invitation::where('id', $invitationId)
            ->where('recovered_child_id', $request->user()->id)
            ->firstOrFail();

        $invitation->update([
            'status' => $request->status,
            'responded_at' => in_array($request->status, ['accepted', 'declined'])
                ? now()
                : null,
        ]);

        return response()->json([
            'message' => 'تم تحديث حالة الدعوة بنجاح'
        ]);
    }
    public function getAllInvitations(Request $request)
    {
        $profile = $request->user()->recoveredChildProfile;

        return response()->json(
            $profile->invitations()
                ->with([
                    'session:id,title,date_time'
                ])
                ->whereIn('status', ['sent', 'accepted', 'declined'])
                ->latest('id')
                ->paginate(3)
        );
    }
    //   جلب كل المحتوى
    public function getAllContents(Request $request)
    {
        $user = $request->user();

        if ($user->user_type === 'specialist') {
            $profile = $user->specialistProfile;
            if (!$profile) {
                return response()->json(['data' => []]);
            }

            return response()->json(
                $profile->contents()
                    ->with(['category:id,slug', 'motivationalDetails'])
                    ->latest()
                    ->paginate(6)
            );
        }

        $profile = $user->recoveredChildProfile;
        if (!$profile) {
            return response()->json(['data' => []]);
        }

        return response()->json(
            $profile->contents()
                ->with(['category:id,slug', 'motivationalDetails'])
                ->latest()
                ->paginate(6)
        );
    }

    //   جلب كل الجلسات
    public function getAllSessions(Request $request)
    {
        $profile = $request->user()->recoveredChildProfile;
        return response()->json($profile->joinedSessions()->latest()->paginate(6));
    }
}
