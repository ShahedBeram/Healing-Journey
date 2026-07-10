<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Campaigns\CampaignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;

class DonorController extends Controller
{

    // إنشاء الحملة 
    public function store(CampaignRequest $request)
    {
        $category = Category::where('slug', 'donations')->firstOrFail();

        $data = $request->validated();

        $data['cover_image'] = null;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')
                ->store('campaign_covers', 'public');
        }

        $data['status']      = 'pending';
        $data['category_id'] = $category->id;

        $campaign = $request->user()->donorProfile->campaigns()->create($data);

        return response()->json(['message' => 'تم إنشاء الحملة وإرسالها للمراجعة', 'campaign' => $campaign], 201);
    }

    // تحديث الحملة 
    public function update(CampaignRequest $request, $id)
    {
        $campaign = $request->user()->donorProfile->campaigns()->findOrFail($id);

        //  نمنع التعديل فقط إذا كانت (مكتملة)
        if ($campaign->status === 'completed') {
            return response()->json(['message' => 'لا يمكن تعديل حملة مكتملة'], 403);
        }
        $validated = $request->validated();

        // التعامل مع الصورة
        if ($request->hasFile('cover_image')) {
            if ($campaign->getRawOriginal('cover_image')) {
                Storage::disk('public')->delete(
                    $campaign->getRawOriginal('cover_image')
                );
            }
            // رفع الصورة الجديدة
            $validated['cover_image'] = $request->file('cover_image')->store('campaign_covers', 'public');
        }

        $validated['status'] = 'pending';

        //  مسح تاريخ الموافقة السابق
        $validated['approved_at'] = null;

        $campaign->update($validated);

        return response()->json([
            'message' => 'تم تحديث البيانات بنجاح، الحملة الآن قيد المراجعة مجدداً',
            'campaign' => $campaign
        ]);
    }
    public function destroy(Request $request, $id)
    {
        // 1. جلب الحملة أولاً
        $campaign = $request->user()->donorProfile->campaigns()->findOrFail($id);

        // 2. إذا كانت الحملة تملك صورة، احذفيها من السيرفر
        if ($campaign->getRawOriginal('cover_image')) {
            Storage::disk('public')->delete(
                $campaign->getRawOriginal('cover_image')
            );
        }
        // 3. حذف السجل من قاعدة البيانات
        $campaign->delete();

        return response()->json(['message' => 'تم حذف الحملة وصورتها بنجاح']);
    }
}
