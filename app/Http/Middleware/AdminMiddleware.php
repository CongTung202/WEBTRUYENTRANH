<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Chưa đăng nhập!'], 401);
            }
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập với tài khoản Admin để tiếp tục.');
        }

        if (!auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bạn không có quyền truy cập trang quản trị!'], 403);
            }
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập khu vực Quản trị!');
        }

        return $next($request);
    }
}
