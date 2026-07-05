<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // التحقق من وجود مستخدم وأن نوعه موجود ضمن الأدوار المسموح بها
        if ($request->user() && in_array($request->user()->user_type, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'غير مصرح لك بالدخول لهذه الصفحة'], 403);
    }
}
