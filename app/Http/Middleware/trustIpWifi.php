<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class trustIpWifi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $arrayIp = [
            "127.0.0.1",
            '1.54.23.56',
            '42.116.188.0'
        ];
        if (
            in_array(explode(',', $request->header('X-Forwarded-For'))[0], $arrayIp)
            || (Auth::user()->attendance_at_home == true && Auth::user()->quit_work == false)
        ) {
            return $next($request);
        } else {
            abort(403, 'Không được truy cập từ IP này');
        }
    }
}
