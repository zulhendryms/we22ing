<?php

namespace App\Laravel\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use App\Core\Internal\Services\LogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    /** @var LogService $logService */
    protected $logService;


    public function __construct(\Illuminate\Contracts\Container\Container $container, LogService $logService)
    {
        parent::__construct($container);
        $this->logService = $logService;
    }

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) return response()->json(['message' => $exception->getMessage()], 401);
        $returnUrl = $request->query(config('constants.return_url'));
        if ($request->route()->getName() !== 'Core\Security\Web::login.store') {
            return back()->withInput()->withErrors($exception->getMessage());
        } else if (empty($returnUrl)) {
            $returnUrl = $request->fullUrl();
        }
        return redirect()->route(config('core.routes.login'), [ config('constants.return_url') => $returnUrl ]);
    }

    protected function createErrorLog($request, Exception $exception)
    {
        $this->logService->createErrorLog([
            'Message' => $exception->getMessage(),
            'Description' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if (
            !($exception instanceof \App\Core\Base\Exceptions\UserFriendlyException) &&
            !($exception instanceof \Illuminate\Validation\ValidationException)
        ) {
            $this->createErrorLog($request, $exception);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->view(config('core.pages.404'));
        }
       
        return parent::render($request, $exception);
    }
}
