<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        // استخدام paginate(3) لتقسيم النتائج لـ 3 عناصر في الصفحة الواحدة
        return Category::latest()->paginate(3);
    }

    /**
     * إضافة تصنيف جديد مع توليد الـ slug تلقائياً
     */
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // توليد الـ slug من الاسم المدخل
        $validated['slug'] = Str::slug($request->name);

        $category = Category::create($validated);

        return response()->json([
            'message'  => 'تم إضافة التصنيف بنجاح',
            'category' => $category
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // تحديث الـ slug تلقائياً إذا تم تغيير الاسم
        if ($request->has('name')) {
            $validated['slug'] = Str::slug($request->name);
        }

        $category->update($validated);

        return response()->json([
            'message'  => 'تم تحديث البيانات بنجاح',
            'category' => $category
        ]);
    }

    /**
     * تبديل حالة التصنيف (تفعيل / تعطيل)
     */
    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);

        // عكس القيمة الحالية (true -> false أو العكس)
        $category->is_active = !$category->is_active;
        $category->save();

        return response()->json([
            'message'   => 'تم تغيير حالة التصنيف',
            'is_active' => $category->is_active
        ]);
    }

    /**
     * حذف تصنيف
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'تم حذف التصنيف بنجاح']);
    }
}
