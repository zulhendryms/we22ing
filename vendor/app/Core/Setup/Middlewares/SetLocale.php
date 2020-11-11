<?php

namespace App\Core\Setup\Middlewares;

use Closure;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        $lang = 'en';
        $hasSession = $request->hasSession();
        if ($hasSession) {
            $lang = session()->get(config('constants.language'));
        }
        if ($request->query->has('lang')) {
            $lang = $request->query('lang');
            if ($hasSession) session()->put('lang', $request->query('lang'));
        }
        App::setLocale($lang);
        return $next($request);
    }
}
