<?php

namespace App\Http\Controllers\Api\Specialist;

use App\Http\Controllers\Controller;
use App\Models\ActivitySession;
use App\Models\Invitation;
use App\Models\Category;
use App\Http\Requests\Api\Specialist\ActivityRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ActivitySessionController extends Controller
{
    public function store(ActivityRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($request, $data) {
            $category = Category::where('slug', $data['type'] === 'session' ? 'sessions' : 'activities')->firstOrFail();
            $coverPath = null;

            //  هل يوجد ملف مرفوع؟ إذا نعم، قم بتخزينه
            if ($request->hasFile('cover_image')) {
                $coverPath = $request->file('cover_image')->store('session_covers', 'public');
            }

            $session = ActivitySession::create(array_merge($data, [
                'created_by'  => $request->user()->id,
                'category_id' => $category->id,
                'cover_image' => $coverPath,
                'status'      => 'pending',
                'duration'    => $data['duration']
            ]));

            if (!empty($data['recovered_child_ids'])) {
                foreach ($data['recovered_child_ids'] as $childId) {
                    Invitation::create([
                        'session_id' => $session->id,
                        'recovered_child_id' => $childId,
                        'status' => 'pending'
                    ]);
                }
            }
            return response()->json(['message' => 'تم إنشاء الجلسة بنجاح، بانتظار موافقة الإدارة', 'session' => $session], 201);
        });
    }
    // جلب الأطفال المتعافين بالموقع 

    public function getRecoveredChildren()
    {
        // جلب الأطفال المتعافين فقط مع الـ ID والاسم
        $children = User::where('user_type', 'recovered_child')
            ->select('id', 'full_name') // نجلب فقط ما تحتاجه الواجهة
            ->get();

        return response()->json($children);
    }
    public function update(ActivityRequest $request, $id)
    {
        $session = ActivitySession::where('created_by', $request->user()->id)->findOrFail($id);

        // التحقق من حالة الجلسة
        if (in_array($session->status, ['ongoing', 'completed'])) {
            return response()->json(['message' => 'لا يمكن تعديل الجلسة بعد أن بدأت أو انتهت'], 403);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($request, $session, $data) {

            // تحديث صورة الغلاف إذا وُجدت

            $updateData = array_merge($data, [
                'status' => 'pending'
            ]);

            if ($request->hasFile('cover_image')) {

                if ($session->getRawOriginal('cover_image')) {
                    Storage::disk('public')->delete(
                        $session->getRawOriginal('cover_image')
                    );
                }

                $updateData['cover_image'] = $request->file('cover_image')
                    ->store('session_covers', 'public');
            }

            $session->update($updateData);

            //  تحديث الأطفال المتعافين (Sync Logic)
            if (isset($data['recovered_child_ids'])) {
                $currentChildIds = $data['recovered_child_ids'];

                //  حذف الدعوات التي لم تعد موجودة في القائمة الجديدة
                $session->invitations()->whereNotIn('recovered_child_id', $currentChildIds)->delete();

                //  إضافة الدعوات الجديدة فقط (إذا لم تكن موجودة مسبقاً)
                foreach ($currentChildIds as $childId) {
                    $session->invitations()->firstOrCreate(
                        ['recovered_child_id' => $childId],
                        ['status' => 'pending']
                    );
                }
            }

            return response()->json(['message' => 'تم تحديث البيانات، وأعيدت الجلسة لحالة "قيد المراجعة"', 'session' => $session]);
        });
    }
    public function destroy($id)
    {
        $session = ActivitySession::findOrFail($id);

        if (in_array($session->status, ['ongoing', 'completed'])) {
            return response()->json(['message' => 'لا يمكن حذف جلسة بدأت أو انتهت'], 403);
        }

        if ($session->getRawOriginal('cover_image')) {
            Storage::disk('public')->delete(
                $session->getRawOriginal('cover_image')
            );
        }
        $session->delete();

        return response()->json(['message' => 'تم حذف الجلسة بنجاح']);
    }
}
