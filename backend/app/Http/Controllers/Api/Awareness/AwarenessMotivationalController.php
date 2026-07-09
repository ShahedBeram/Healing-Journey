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
 /*   public function update(AwarenessContentRequest $request, $contentId)
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
    }*/
    /**
     * تحديث المحتوى
     */
    /*public function update(AwarenessContentRequest $request, $contentId)
    {
        $content = Content::findOrFail($contentId);
        $details = AwarenessMotivationalContent::where('content_id', $contentId)->firstOrFail();

        $data = $request->validated();

        // إزالة الملفات الخام من الـ array، لأننا رح نتعامل معها يدوياً
        // (منعاً لتسرب UploadedFile objects لـ $content->update())
        unset($data['file'], $data['cover_image']);

        // النوع/التصنيف الفعلي: لو ما انبعت بالـ request، نحافظ على القيمة الحالية
        $categoryType = $data['content_category_type'] ?? $details->content_category_type;
        $category = Category::where('slug', $categoryType)->firstOrFail();

        return DB::transaction(function () use ($request, $content, $details, $data, $category, $categoryType) {

            // 1. التعامل مع الملف الأساسي (file)
            // إذا أرسل المستخدم ملفاً جديداً أو طلب حذف الملف الحالي
            if ($request->hasFile('file') || $request->boolean('remove_file')) {

                // حذف الملف القديم فيزيائياً من السيرفر
                if ($content->getRawOriginal('file_path')) {
                    Storage::disk('public')->delete($content->getRawOriginal('file_path'));
                }

                // إذا كان هناك ملف جديد يتم رفعه، وإلا نجعله null
                if ($request->hasFile('file')) {
                    $data['file_path'] = $request->file('file')->store('awareness_files', 'public');
                } else {
                    $data['file_path'] = null;
                }
            }
            // لو ما فيه ملف جديد ولا طلب حذف => ما منلمس file_path إطلاقاً، بيضل القديم

            // 2. التعامل مع صورة الغلاف (cover_image)
            if ($request->hasFile('cover_image')) {
                if ($content->getRawOriginal('cover_image')) {
                    Storage::disk('public')->delete($content->getRawOriginal('cover_image'));
                }
                $data['cover_image'] = $request->file('cover_image')->store('awareness_covers', 'public');
            }

            // 3. تجهيز بيانات التحديث
            $updateData = array_merge($data, [
                'status'      => 'pending',
                'category_id' => $category->id,
            ]);

            // 4. تنفيذ التحديث
            $content->update($updateData);

            $details->update([
                'content_category_type' => $categoryType,
            ]);

            return response()->json([
                'message' => 'تم تحديث المحتوى وإعادة إرساله للمراجعة',
                'data'    => $content->fresh(),
            ]);
        });
    }*/
    /**
     * تحديث المحتوى
     */
    public function update(AwarenessContentRequest $request, $contentId)
    {
        $content = Content::findOrFail($contentId);
        $details = AwarenessMotivationalContent::where('content_id', $contentId)->firstOrFail();

        $data = $request->validated();

        // إزالة الملفات من البيانات قبل update
        unset($data['file'], $data['cover_image']);

        // الحفاظ على القيم الحالية إذا لم ترسل في الطلب
        $categoryType = $data['content_category_type'] ?? $details->content_category_type;

        $category = Category::where('slug', $categoryType)->firstOrFail();

        return DB::transaction(function () use (
            $request,
            $content,
            $details,
            $data,
            $category,
            $categoryType
        ) {

            /*
        |--------------------------------------------------------------------------
        | التعامل مع الملف الأساسي
        |--------------------------------------------------------------------------
        */

            // حذف الملف القديم فقط عند وجود remove_file
            if ($request->boolean('remove_file')) {

                if ($content->getRawOriginal('file_path')) {
                    Storage::disk('public')->delete(
                        $content->getRawOriginal('file_path')
                    );
                }

                $data['file_path'] = null;
            }


            // رفع ملف جديد (مع استبدال القديم)
            if ($request->hasFile('file')) {

                if ($content->getRawOriginal('file_path')) {
                    Storage::disk('public')->delete(
                        $content->getRawOriginal('file_path')
                    );
                }

                $data['file_path'] = $request
                    ->file('file')
                    ->store('awareness_files', 'public');
            }


            /*
        |--------------------------------------------------------------------------
        | التعامل مع صورة الغلاف
        |--------------------------------------------------------------------------
        */

            if ($request->hasFile('cover_image')) {

                if ($content->getRawOriginal('cover_image')) {
                    Storage::disk('public')->delete(
                        $content->getRawOriginal('cover_image')
                    );
                }

                $data['cover_image'] = $request
                    ->file('cover_image')
                    ->store('awareness_covers', 'public');
            }


            /*
        |--------------------------------------------------------------------------
        | تحديث البيانات
        |--------------------------------------------------------------------------
        */

            $updateData = array_merge($data, [
                'status'      => 'pending',
                'category_id' => $category->id,
            ]);


            $content->update($updateData);


            $details->update([
                'content_category_type' => $categoryType,
            ]);


            return response()->json([
                'message' => 'تم تحديث المحتوى وإعادة إرساله للمراجعة',
                'data'    => $content->fresh(),
            ]);
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
