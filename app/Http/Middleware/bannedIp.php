<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class bannedIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bannedIp = [
            '118.99.2.29'
        ];
        if (in_array(explode(',', $request->header('X-Forwarded-For'))[0], $bannedIp)) {
            abort(404);
        }else {
            return $next($request);
        }
    }
}
