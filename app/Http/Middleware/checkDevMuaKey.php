<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class checkDevMuaKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = ['nghia@gmail.com', 'dovuong020802@gmail.com', 'minhtri204dz@gmail.com', 'ankhangit06@gmail.com'];
        if (in_array(Auth::user()->email, $allowedIps)) {
            return $next($request);
        } else {
            redirect('https://work.1997.pro.vn');
        }
    }
}
