<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Content;
use App\Models\DonationCampaign;
use App\Models\ActivitySession;

class InteractionController extends Controller
{
    // =========================
    // Permissions
    // =========================
    private function validatePermission($user, $type)
    {
        if ($user->user_type === 'donor' && $type !== 'campaign') return false;

        if (in_array($user->user_type, ['recovered_child', 'specialist']) && $type === 'campaign') return false;

        return true;
    }

    // =========================
    // Resolve correct model
    // =========================

    private function resolveModel($type, $id)
    {
        $model = match ($type) {

            // كلهم بيرجعوا نفس المحتوى الأساسي
            'content',
            'child_content',
            'awareness_content' => Content::findOrFail($id),

            'campaign' => DonationCampaign::findOrFail($id),

            'session' => ActivitySession::findOrFail($id),

            default => throw new \Exception("نوع غير مدعوم"),
        };


        // الطريقة الصحيحة: يجب أن تكون الحالة ليست 'approved' AND ليست 'active'
        if (isset($model->status) && $model->status !== 'approved' && $model->status !== 'active') {

            abort(response()->json([
                'message' => 'لا يمكن التفاعل مع محتوى غير معتمد أو غير نشط'
            ], 403));
        }


        return $model;
    }

    // =========================
    // LIKE TOGGLE
    // =========================
    public function toggleLike(Request $request, $type, $id)
    {
        $user = Auth::user();

        if (!$this->validatePermission($user, $type)) {
            return response()->json([
                'message' => 'غير مسموح'
            ], 403);
        }

        if ($request->filled('child_id') && $type !== 'child_content') {
            return response()->json([
                'message' => 'يمكن اختيار طفل فقط عند التفاعل مع محتوى الأطفال'
            ], 422);
        }

        //  جلب المحتوى الأساسي
        $model = $this->resolveModel($type, $id);

        //  تحقق من تطابق النوع مع الجداول الفرعية 
        if ($type === 'child_content') {
            $exists = \App\Models\ChildContent::where('content_id', $id)->exists();

            if (!$exists) {
                return response()->json([
                    'message' => 'هذا المحتوى ليس من نوع child_content'
                ], 422);
            }
        }

        if ($type === 'awareness_content') {
            $exists = \App\Models\AwarenessMotivationalContent::where('content_id', $id)->exists();

            if (!$exists) {
                return response()->json([
                    'message' => 'هذا المحتوى ليس من نوع awareness_content'
                ], 422);
            }
        }

        $childId = null;

        if ($user->user_type === 'parent' && $request->filled('child_id')) {

            if (!$user->parentProfile) {
                return response()->json([
                    'message' => 'لم يتم العثور على ملف ولي الأمر'
                ], 404);
            }

            $child = $user->parentProfile
                ->children()
                ->findOrFail($request->child_id);

            $childId = $child->id;
        }

        $like = Like::where('user_id', $user->id)
            ->where('likeable_id', $id)
            ->where('likeable_type', get_class($model))
            ->where('child_id', $childId)
            ->first();

        if ($like) {
            $like->delete();

            return response()->json([
                'message' => 'unliked'
            ]);
        }

        Like::create([
            'user_id'       => $user->id,
            'likeable_id'   => $id,
            'likeable_type' => get_class($model),
            'child_id'      => $childId,
        ]);

        return response()->json([
            'message' => 'liked'
        ]);
    }

    // =========================
    // COMMENT
    // =========================
    public function storeComment(Request $request, $type, $id)
    {
        $user = Auth::user();

        if (!$this->validatePermission($user, $type)) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        if ($request->filled('child_id')) {
            return response()->json(['message' => 'التعليق لولي الأمر فقط'], 403);
        }

        $model = $this->resolveModel($type, $id);
        if ($type === 'child_content') {

            // لازم يكون ID تابع child_contents وليس awareness
            $exists = \App\Models\ChildContent::where('content_id', $id)->exists();

            if (!$exists) {
                return response()->json([
                    'message' => 'هذا المحتوى ليس من نوع child_content'
                ], 422);
            }
        }

        if ($type === 'awareness_content') {

            $exists = \App\Models\AwarenessMotivationalContent::where('content_id', $id)->exists();

            if (!$exists) {
                return response()->json([
                    'message' => 'هذا المحتوى ليس من نوع awareness_content'
                ], 422);
            }
        }

        $request->validate([
            'comment_text' => 'required|string'
        ]);

        Comment::create([
            'user_id' => $user->id,
            'comment_text' => $request->comment_text,
            'commentable_id' => $id,
            'commentable_type' => get_class($model),
            'child_id' => null
        ]);

        return response()->json(['message' => 'added']);
    }
    public function getComments($type, $id)
    {
        $user = Auth::user();

        if (!$this->validatePermission($user, $type)) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        $model = $this->resolveModel($type, $id);

        $commentsQuery = \App\Models\Comment::where('commentable_id', $id)
            ->where('commentable_type', get_class($model));

        $count = $commentsQuery->count(); // عدد التعليقات

        $comments = $commentsQuery
            ->with('user:id,full_name,profile_picture')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                return [
                    'name' => $comment->user->full_name,
                    'avatar' => $comment->user->profile_picture,
                    'comment' => $comment->comment_text,
                    'time' => $comment->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'count' => $count,
            'data' => $comments
        ]);
    }

    // =========================
    // INTERACTION OPTIONS (Parent only)
    // =========================
    public function getInteractionOptions($type, $id)
    {
        $user = Auth::user();

        if ($user->user_type !== 'parent') {
            return response()->json([
                'message' => 'غير مسموح الا لولي الأمر'
            ], 403);
        }

        if ($type !== 'child_content') {
            return response()->json([
                'message' => 'only child content'
            ], 422);
        }

        $model = $this->resolveModel($type, $id);

        $likes = Like::where('likeable_id', $id)
            ->where('likeable_type', get_class($model))
            ->get();

        $likedChildIds = $likes
            ->pluck('child_id')
            ->filter()
            ->toArray();

        $parentLiked = $likes
            ->where('user_id', $user->id)
            ->whereNull('child_id')
            ->isNotEmpty();

        return response()->json([

            'parent' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'image' => $user->profile_picture,
                'type' => 'parent',
                'liked' => $parentLiked,
            ],

            'children' => optional($user->parentProfile)->children?->map(function ($child) use ($likedChildIds) {
                return [
                    'id' => $child->id,
                    'name' => $child->child_name,
                    'age' => $child->age,
                    'image' => $child->profile_picture,
                    'type' => 'child',
                    'liked' => in_array($child->id, $likedChildIds),
                ];
            })->values() ?? [],
        ]);
    }
}
