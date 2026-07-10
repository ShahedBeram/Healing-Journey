<?php

namespace App\Http\Controllers\Api\Awareness;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AwarenessMotivationalContent;

class AwarenessHubController extends Controller
{
    /**
     * عرض قائمة المحتوى التوعوي والتحفيزي مع التصفح والفلترة
     */
    public function index(Request $request)
    {
        $contents = AwarenessMotivationalContent::with(['content.submitter:id,full_name'])
            ->whereHas('content', function ($q) {
                $q->where('status', 'approved');
            })
            ->when($request->query('type'), function ($query, $type) {
                $query->where('content_category_type', $type);
            })
            ->latest()
            ->paginate(6);

        return response()->json($contents); // التوحيد هنا
    }
}
