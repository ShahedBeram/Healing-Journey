<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildContent;
use Illuminate\Http\Request;

class ChildContentController extends Controller
{
    public function index(Request $request)
    {
        $contents = ChildContent::query()
            ->with(['content', 'childProfile'])
            ->whereHas('content', function ($q) {
                $q->where('status', 'approved');
            })
            ->when($request->query('type'), function ($q, $type) {
                $q->where('content_category_type', $type);
            })
            ->when($request->query('sort') === 'most_interactive', function ($q) {
                $q->join('contents', 'child_contents.content_id', '=', 'contents.id')
                    ->select('child_contents.*')
                    ->orderByRaw('(contents.likes_count + contents.comments_count) DESC');
            }, function ($q) {
                $q->latest();
            })
            ->paginate(6);

        return response()->json($contents);
    }
}
