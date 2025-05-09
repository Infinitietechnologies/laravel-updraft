<?php

namespace LaravelUpdraft\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

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
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            App::setLocale($locale);
        }
        
        // Force the locale to be persistent for this request
        $response = $next($request);
        
        // If the response is a view, make sure it has the current locale
        if (method_exists($response, 'getContent')) {
            // This ensures any content rendered after middleware runs
            // still has the correct locale
            App::setLocale(App::getLocale());
        }
        
        return $response;
    }
}