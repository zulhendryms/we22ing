<?php

namespace App\Core\Setup\Middlewares;

use Closure;
use Illuminate\Support\Facades\App;
use App\Core\Internal\Services\LogService;

class AccessLog
{
    /** @var LogService $logService */
    private $logService;
    /**
     * Create a new middleware instance.
     * 
     * @return void
     */
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
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
        $user = $request->user();
        $this->logService->createAccessLog([
            'Message' => (!is_null($user) ? $user->UserName : $request->getClientIp()).' accessed '.$request->getRequestUri().' at '.now()->toIso8601String()
        ]);
        return $next($request);
    }
}
