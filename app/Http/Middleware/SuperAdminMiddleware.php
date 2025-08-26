<?php

namespace App\Http\Middleware;

use App\Enums\AdminTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin || $admin->admin_type !== AdminTypeEnum::SUPER_ADMIN) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. Super admin required.'], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'Access denied. Super admin required.');
        }

        return $next($request);
    }
}
