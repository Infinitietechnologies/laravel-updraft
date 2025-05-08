<?php

namespace LaravelUpdraft\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if locale is set in session
        if (session()->has('locale')) {
            app()->setLocale(session('locale'));
        }
        
        return $next($request);
    }
}