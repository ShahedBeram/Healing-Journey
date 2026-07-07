<?php

namespace App\Http\Controllers\Api\Awareness;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\AwarenessMotivationalContent;
use App\Models\Category;
use App\Http\Requests\Api\Awareness\AwarenessContentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AwarenessMotivationalController extends Controller
{
    /**
     * إنشاء محتوى توعوي أو تحفيزي جديد
     */
    public function store(AwarenessContentRequest $request)
    {

        // إذا كان المختار awareness نأخذ الفئة 'awareness'، وإذا motivational نأخذ 'motivational'
        $category = Category::where('slug', $request->content_category_type)->firstOrFail();

        return DB::transaction(function () use ($request, $category) {
            $data = $request->validated();

            $filePath = $request->hasFile('file') ? $request->file('file')->store('awareness_files', 'public') : null;

            $coverPath = null;
            if ($request->hasFile('cover_image')) {
                $coverPath = $request->file('cover_image')->store('awareness_covers', 'public');
            }


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

            AwarenessMotivationalContent::create([
                'content_id' => $content->id,
                'content_category_type' => $data['content_category_type'],
            ]);
            $content->load('motivationalDetails');
            return response()->json(['message' => 'تم إرسال المحتوى للمراجعة بنجاح', 'data' => $content], 201);
        });
    }

    /**
     * تحديث المحتوى
     */
    public function update(AwarenessContentRequest $request, $contentId)
    {
        $content = Content::findOrFail($contentId);
        $details = AwarenessMotivationalContent::where('content_id', $contentId)->firstOrFail();
        $data = $request->validated();

        // جلب الفئة الجديدة في حال تغير النوع أثناء التعديل
        $category = Category::where('slug', $data['content_category_type'])->firstOrFail();

        return DB::transaction(function () use ($request, $content, $details, $data, $category) {
            if ($request->hasFile('file')) {
                if ($content->file_path) Storage::disk('public')->delete($content->file_path);
                $content->file_path = $request->file('file')->store('awareness_files', 'public');
            }
            $updateData = array_merge($data, [
                'status' => 'pending',
                'category_id' => $category->id,
            ]);

            if ($request->hasFile('cover_image')) {

                if ($content->getRawOriginal('cover_image')) {
                    Storage::disk('public')->delete(
                        $content->getRawOriginal('cover_image')
                    );
                }

                $updateData['cover_image'] = $request->file('cover_image')
                    ->store('awareness_covers', 'public');
            }

            $content->update($updateData);

            $details->update(['content_category_type' => $data['content_category_type']]);

            return response()->json(['message' => 'تم تحديث المحتوى وإعادة إرساله للمراجعة', 'data' => $content]);
        });
    }

    /**
     * حذف المحتوى
     */
    public function destroy($contentId)
    {
        $content = Content::findOrFail($contentId);

        if ($content->file_path) Storage::disk('public')->delete($content->file_path);
        if ($content->getRawOriginal('cover_image')) {
            Storage::disk('public')->delete(
                $content->getRawOriginal('cover_image')
            );
        }

        $content->delete();

        return response()->json(['message' => 'تم حذف المحتوى بنجاح']);
    }
}
