<?php

namespace App\Http\Middleware;

use Closure;

class FakeAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!empty($request->header('Authorization'))){
            $authHeader = $request->header('Authorization');
            $token = explode(" ", $authHeader);
            if(!empty($token[1]) && getenv('TOKEN') === $token[1]){
                return $next($request);
            }
        }
        return response()->json([
            'error' => 'Unauthorized!'
        ], 403);
    }
}
