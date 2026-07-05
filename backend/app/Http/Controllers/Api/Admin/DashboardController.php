<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DonationCampaign;
use App\Models\ActivitySession;
use App\Models\Content;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. الإحصائيات (الأرقام العلوية)
        $stats = [
            'awarness&motivationalContents_sessions' => Content::whereHas('motivationalDetails')
                ->count()
                +
                ActivitySession::count(),

            'child_profiles' => \App\Models\ChildProfile::count(),

            'donation_campaigns' => DonationCampaign::count(),

            'parents' => User::where('user_type', 'parent')->count(),
        ];

        // 2. نمو المجتمع (نسبة الزيادة هذا الشهر مقارنة بالماضي)
        $currentMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        $totalNow = User::where('created_at', 'like', "$currentMonth%")->count();
        $totalLast = User::where('created_at', 'like', "$lastMonth%")->count();
        $growth = $totalLast > 0
            ? (($totalNow - $totalLast) / $totalLast) * 100
            : 0;

        $growth = max(0, round($growth, 1));


        // 3. بيانات المخطط البياني (تفاعل آخر 6 أشهر)
        $months = collect(range(0, 5))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        })->reverse();

        $chartData = $months->map(function ($month) {

            $total = DB::table(DB::raw("(
        SELECT created_at, (likes_count + comments_count) as total FROM contents
        UNION ALL
        SELECT created_at, (likes_count + comments_count) as total FROM donation_campaigns
        UNION ALL
        SELECT created_at, (likes_count + comments_count) as total FROM activity_sessions
    ) as all_interactions"))
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
                ->sum('total');

            return [
                'month' => $month,
                'total_interaction' => (int) $total
            ];
        });

        // 4. قائمة المحتوى مع الفلترة
        $tab = $request->query('tab', 'all');
        $query = match ($tab) {
            'campaigns' => DonationCampaign::query()->select('id', 'title', 'status', 'created_at', DB::raw("'حملات تبرع' as category_label"), DB::raw("'campaign' as type")),
            'sessions'  => ActivitySession::query()->select('id', 'title', 'status', 'created_at', DB::raw("'جلسات وأنشطة' as category_label"), DB::raw("'session' as type")),
            'child_content' => Content::whereHas('childContentDetails')->select('id', 'title', 'status', 'created_at', DB::raw("'محتوى أطفال' as category_label"), DB::raw("'child_content' as type")),
            'awareness' => Content::whereHas('motivationalDetails')->select('id', 'title', 'status', 'created_at', DB::raw("'محتوى توعوي وتحفيزي' as category_label"), DB::raw("'awareness_content' as type")),
            default => Content::select('id', 'title', 'status', 'created_at', DB::raw("'محتوى عام' as category_label"), DB::raw("'content' as type"))
                ->unionAll(DonationCampaign::select('id', 'title', 'status', 'created_at', DB::raw("'حملات تبرع' as category_label"), DB::raw("'campaign' as type")))
                ->unionAll(ActivitySession::select('id', 'title', 'status', 'created_at', DB::raw("'جلسات وأنشطة' as category_label"), DB::raw("'session' as type")))
        };

        return response()->json([
            'stats'            => $stats,
            'community_growth' => round($growth, 1),
            'chart_data'       => $chartData,
            'data'             => $query->orderBy('created_at', 'desc')->paginate(4)
        ]);
    }
}
