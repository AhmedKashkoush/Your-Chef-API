<?php

namespace App\Http\Middleware\Users;

use App\Models\User;
use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OnlineStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user){
            User::where('id',$user->id)->update(['online_status' => 1]);
            $time = now()->addMinute();
            Cache::put('online-status'.$user->id, $user->online_status, $time);
        }
        return $next($request);
    }
}
