<?php

namespace App\Http\Controllers\Api\Session;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivitySession;

class ActivitySessionControllerHub extends Controller
{
    public function index(Request $request)
    {
        $query = ActivitySession::query()
            // عرض المقبول، الجاري، والمنتهي فقط
            ->whereIn('status', ['approved', 'ongoing', 'completed'])

            // فلترة حسب النوع (session أو activity)
            ->when($request->query('type'), function ($q, $type) {
                $q->where('type', $type);
            })

            ->latest('date_time');

        return response()->json($query->paginate(6));
    }
}
