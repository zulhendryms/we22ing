<?php

namespace App\Laravel\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Core\Security\Middlewares\ConnectCompanyDatabase::class,
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Laravel\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Laravel\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Laravel\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Laravel\Http\Middleware\VerifyCsrfToken::class,
            'token-check:web',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'token-check:api',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Laravel\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'token-check' => \App\Core\Setup\Middlewares\AuthenticateIfTokenExists::class,
        '2fa' => \PragmaRX\Google2FALaravel\Middleware::class,
        'access' => \App\Core\Setup\Middlewares\AccessLog::class,
        'config' => \App\Core\Setup\Middlewares\UserConfig::class,
        'cors' => \Barryvdh\Cors\HandleCors::class,
        'company-switcher' => \App\Core\Security\Middlewares\ConnectCompanyDatabase::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    // protected $middlewarePriority = [
    //     \Illuminate\Session\Middleware\StartSession::class,
    //     \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    //     \App\Core\Security\Middlewares\ConnectCompanyDatabase::class,
    //     \App\Http\Middleware\Authenticate::class,
    //     \Illuminate\Session\Middleware\AuthenticateSession::class,
    //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
    //     \Illuminate\Auth\Middleware\Authorize::class,
    // ];
}