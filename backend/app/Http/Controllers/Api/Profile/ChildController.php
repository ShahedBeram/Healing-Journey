<?php

namespace App\Http\Controllers\Api\Profile;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Profile\ChildRequest;
use App\Models\ChildContent;
use App\Models\Content;
use App\Models\Category;
use App\Http\Requests\Api\Child\ContentRequest;


class ChildController extends Controller
{

    //  العرض التفصيلي (لصفحة الطفل الكاملة)
    // تظهر: كل بيانات الطفل (بما فيها قصة المرض) + قائمة المحتوى
    public function show(Request $request, $id)
    {
        return $request->user()->parentProfile->children()
            ->with('childContents.content')
            ->findOrFail($id);
    }

    // إضافة طفل جديد
    public function store(ChildRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['profile_picture'] = null;
        $validatedData['points'] = 0;

        if ($request->hasFile('profile_picture')) {
            $validatedData['profile_picture'] = $request->file('profile_picture')->store('children_profiles', 'public');
        }

        $child = $request->user()->parentProfile->children()->create($validatedData);
        return response()->json(['message' => 'تم إضافة الطفل بنجاح', 'child' => $child], 201);
    }

    //  تحديث بيانات الطفل
    public function update(ChildRequest $request, $id)
    {
        $child = $request->user()->parentProfile->children()->findOrFail($id);
        $validatedData = $request->validated();

        unset($validatedData['points']);

        if ($request->hasFile('profile_picture')) {
            if ($child->getRawOriginal('profile_picture')) {
                Storage::disk('public')
                    ->delete($child->getRawOriginal('profile_picture'));
            }
            $validatedData['profile_picture'] = $request->file('profile_picture')->store('children_profiles', 'public');
        }

        $child->update($validatedData);
        return response()->json(['message' => 'تم تحديث بيانات الطفل بنجاح', 'child' => $child]);
    }

    //  حذف الطفل
    public function destroy(Request $request, $id)
    {
        $child = $request->user()->parentProfile->children()->findOrFail($id);
        if ($child->getRawOriginal('profile_picture')) {
            Storage::disk('public')
                ->delete($child->getRawOriginal('profile_picture'));
        }
        $child->delete();
        return response()->json(['message' => 'تم حذف الطفل بنجاح']);
    }

    // إنشاء محتوى طفل 
    /**
     * 1. إنشاء محتوى جديد (Store)
     */
    public function storeContent(ContentRequest $request, $childId)
    {
        $child = $request->user()->parentProfile->children()->findOrFail($childId);
        $category = Category::where('slug', 'child-content')->firstOrFail();

        return DB::transaction(function () use ($request, $child, $category) {
            // التحقق من الملفات (تخزين الملف الأساسي وصورة الغلاف)
            $filePath = $request->hasFile('file') ? $request->file('file')->store('content_files', 'public') : null;
            $coverPath = $request->hasFile('cover_image') ? $request->file('cover_image')->store('content_covers', 'public') : null;

            // استخدام validated() للحصول على البيانات الموثقة
            $data = $request->validated();

            $content = Content::create([
                'title'        => $data['title'],
                'description'  => $data['description'],
                'body'         => $data['body'] ?? null,
                'content_type' => $data['content_type'],
                'file_path'    => $filePath,
                'cover_image'  => $coverPath,
                'status'       => 'pending',
                'submitted_by' => $request->user()->id,
                'category_id'  => $category->id
            ]);

            ChildContent::create([
                'content_id'            => $content->id,
                'child_profile_id'      => $child->id,
                'content_category_type' => $data['content_category_type'],
            ]);

            return response()->json(['message' => 'تم الإرسال للمراجعة'], 201);
        });
    }

    /**
     * 2. تحديث المحتوى (Update)
     */
    public function updateContent(ContentRequest $request, $childId, $contentId)
    {
        $content = Content::findOrFail($contentId);
        $childContent = ChildContent::where('content_id', $contentId)->firstOrFail();
        $data = $request->validated();

        return DB::transaction(function () use ($request, $content, $childContent, $data) {
            // تحديث الملف الأساسي إذا وُجد
            if ($request->hasFile('file')) {
                if ($content->file_path) Storage::disk('public')->delete($content->file_path);
                $content->file_path = $request->file('file')->store('content_files', 'public');
            }

            // تحديث صورة الغلاف إذا وُجدت
            if ($request->hasFile('cover_image')) {
                if ($content->cover_image) Storage::disk('public')->delete($content->cover_image);
                $content->cover_image = $request->file('cover_image')->store('content_covers', 'public');
            }
            $content->save();
            // تحديث البيانات الأساسية باستخدام validated()
            $content->update(array_merge(
                $request->only(['title', 'description', 'body', 'content_type']),
                ['status' => 'pending'] // إعادة الحالة للمراجعة
            ));

            // تحديث نوع المحتوى الفرعي
            if (isset($data['content_category_type'])) {
                $childContent->update(['content_category_type' => $data['content_category_type']]);
            }

            return response()->json(['message' => 'تم التحديث بنجاح']);
        });
    }

    /**
     * 3. حذف المحتوى (Destroy)
     */
    public function destroyContent($childId, $contentId)
    {
        $content = Content::findOrFail($contentId);

        // حذف الملفات من الـ Storage
        if ($content->file_path) Storage::disk('public')->delete($content->file_path);
        if ($content->cover_image) Storage::disk('public')->delete($content->cover_image);

        $content->delete();

        return response()->json(['message' => 'تم حذف المحتوى بنجاح']);
    }
}
