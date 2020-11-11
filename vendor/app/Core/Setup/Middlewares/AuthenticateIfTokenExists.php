<?php

namespace App\Core\Setup\Middlewares;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;

class AuthenticateIfTokenExists
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param   string  $type
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, $type = 'api')
    {

        if (\Illuminate\Support\Facades\Auth::check()) return $next($request);

        if ($type == 'web' && !$request->query->has('_t')) {
            $userId = session(config('constants.user_id'));
            if (isset($userId)) {
                \Illuminate\Support\Facades\Auth::loginUsingId($userId);
            }
            if (\Illuminate\Support\Facades\Auth::check()) return $next($request);
        }
       
        $guard = 'api';

        if (!$request->headers->has('authorization')) {
            
            if ($request->query->has('_t')) {
                $token = $request->query('_t');
            }
            if (isset($token)) {
                $request->headers->set('authorization', 'Bearer '.$token);
            }
        }

        if ($request->headers->has('authorization')) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
            }
        }
        if ($type == 'web' && \Illuminate\Support\Facades\Auth::check()) {
            session()->put(config('constants.user_id'), $request->user()->Oid);
        }

        if ($request->query->has('_t')) {
            $query = $request->query();
            unset($query['_t']);
            return redirect($request->url().'?'.http_build_query($query));
        }
        return $next($request);
    }
}
