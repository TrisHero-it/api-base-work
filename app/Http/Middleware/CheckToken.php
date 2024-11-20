<?php

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class   CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('authorization');
        if (empty($token)) {
            return response()->json([
                'error' => 'Bạn chưa đăng nhập'
            ]);
        }
        $token = explode(' ', $token);
        if ($token[0] !== 'Bearer') {
            return response()->json([
                'error' => 'Sai loại token'
            ]);
        }
        $token = $token[1];
        $accounts = Account::query()->where('remember_token', $token)->first();
        if (isset($accounts)) {
            return $next($request);
        }else {
            return response()->json([
                'error' => 'Bạn đang định hack web tôi à'
            ]);
        }
    }
}
