<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Content;
use App\Models\DonationCampaign;
use App\Models\ActivitySession;
use App\Models\ChildContent;

class AdminActionController extends Controller
{
    // عرض تفاصيل المحتوى (يُستدعى عند الضغط على "عرض")
    public function show($type, $id)
    {
        return response()->json(['data' => $this->getModel($type, $id)]);
    }
    public function meta($id)
    {
        $model = ChildContent::with([
            'content:id,title',
            'childProfile:id,child_name'
        ])->findOrFail($id);

        return response()->json([
            'title' => $model->content->title ?? null,
            'child_name' => $model->childProfile->child_name ?? null,
        ]);
    }
    // الموافقة على المحتوى وتحديث النقاط إن وُجدت
    public function approve(Request $request, $type, $id)
    {
        $model = $this->getModel($type, $id);
        // لا يسمح بإرسال points إلا مع محتوى الأطفال
        if ($request->filled('points') && $type !== 'child_content') {
            return response()->json([
                'message' => 'يمكن إدخال النقاط لمحتوى الأطفال فقط'
            ], 422);
        }
        $status = $type === 'campaign' ? 'active' : 'approved';
        $model->update([
            'status' => $status,
            'decided_by' => Auth::id(),
            'approved_at' => now()
        ]);

        if ($type === 'child_content') {
            $request->validate(['points' => 'required|numeric']);
            ChildContent::where('content_id', $id)->update(['points_awarded' => $request->points]);
        }
        //  إرسال الدعوات عند الموافقة على الجلسة
        elseif ($type === 'session') {
            $model->invitations()->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return response()->json(['message' => 'تمت الموافقة بنجاح']);
    }

    // رفض المحتوى
    public function reject($type, $id)
    {
        $this->getModel($type, $id)->update([
            'status' => 'rejected',
            'decided_by' => Auth::id()
        ]);

        return response()->json(['message' => 'تم رفض المحتوى']);
    }

    private function getModel($type, $id)
    {
        switch ($type) {

            case 'child_content':

                $childContent = \App\Models\ChildContent::with([
                    'content',
                    'childProfile:id,child_name,profile_picture'
                ])->where('content_id', $id)->first();

                if (!$childContent) {
                    throw new \Exception("هذا المحتوى ليس child_content");
                }

                return $childContent->content->setRelation('childContentDetails', $childContent);

            case 'awareness_content':

                $content = Content::with('motivationalDetails')
                    ->whereHas('motivationalDetails')
                    ->findOrFail($id);

                return $content;

            case 'campaign':

                return DonationCampaign::findOrFail($id);

            case 'session':

                return ActivitySession::findOrFail($id);

            default:
                throw new \InvalidArgumentException("نوع المحتوى غير صحيح");
        }
    }
}
