<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            if(config('settings.site_offline')){
                return abort(503, config('settings.offline_reason'));
            } else {
                if ($request->getPathInfo() === '/upload') {
                    Cookie::queue(Cookie::make('REDIRECT_2_UPLOAD', 'true', 60));
                    return route('frontend.sign-in');
                }
                return abort(403);
            }
        }
    }
}
