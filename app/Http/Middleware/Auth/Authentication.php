<?php

namespace App\Http\Middleware\Auth;

use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;

class Authentication
{
    use ResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeaderName = 'X-Authorization';
        if (!$request->hasHeader($authHeaderName)) {
            return $this -> unAuthorized(); 
        }
        else if ($request->hasHeader($authHeaderName)){
            if ($request->header($authHeaderName) != 'yourchefproject'){
                return $this -> unAuthorized();             
            }
        }
        return $next($request);
    }
}
