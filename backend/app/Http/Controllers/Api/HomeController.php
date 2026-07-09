<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildProfile;
use App\Models\AwarenessMotivationalContent;
use App\Models\ChildContent;
use App\Models\ActivitySession;
use App\Models\DonationCampaign;
use App\Models\RecoveredChild;

class HomeController extends Controller
{
    public function index()
    {
        $stats = [
            // 1. عدد بروفايلات الأطفال المصابين
            'beneficiaries' => ChildProfile::count(),

            // 2. المحتوى التوعوي + الجلسات (المجموع)
            'awareness_and_sessions' =>
            AwarenessMotivationalContent::whereHas('content', function ($q) {
                $q->where('status', 'approved');
            })->count() +
                ActivitySession::where('status', 'approved')->count(),

            // 3. حملات التبرع
            'campaigns' => DonationCampaign::where('status', 'approved')->count(),

            // 4. محتوى الأطفال
            'children_content' => ChildContent::whereHas('content', function ($query) {
                $query->where('status', 'approved');
            })->count(),
        ];

        // 3 قصص نجاح حديثة
        $successStories = RecoveredChild::whereHas('user', function ($query) {
            $query->where('account_status', 'approved');
        })
            ->with('user:id,full_name')
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($story) {
                return [
                    'id'             => $story->user_id,
                    'full_name'      => $story->user->full_name,
                    'recovery_story' => $story->recovery_story,
                    'nickname'       => $story->nickname,
                ];
            });

        return response()->json([
            'stats' => $stats,
            'success_stories' => $successStories
        ]);
    }
}
