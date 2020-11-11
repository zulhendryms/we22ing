<?php

namespace App\Core\Security\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Socialite;
use Illuminate\Http\Request;
use App\Core\Security\Entities\User;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\AuthService;
use App\Core\Security\Requests\Web\LoginRequest;
use App\Core\Security\Traits\PostMessageScript;

class LoginController extends Controller 
{

    use PostMessageScript;

    /** @var AuthService $authService */
    protected $authService;

    /**
     * @param AuthService $authService
     * @return void
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @param LoginRequest $request
     */
    public function store(LoginRequest $request)
    {
        $params = $request->all();
        $remember = false;
        if (isset($params['Remember'])) {
            $remember = true;
            unset($params['Remember']);
        }
        $user = $this->authService->login($params, 'web', [ 'remember' => $remember ]);

        $returnUrl = $request->query(config('constants.return_url')) ?? session(config('constants.return_url'));
        session()->remove(config('constants.return_url'));
        if (is_null($returnUrl)) $returnUrl = config('app.url');
        
        if (is_webview()) return $this->postActionMessage('logged_in');

        if (!$request->ajax()) return redirect($returnUrl);
    }

    public function destroy(Request $request)
    {
        $this->authService->logout();
        if (is_webview()) return $this->postActionMessage('logged_out');
        if (config('core.routes.home')) {
            return redirect()->route(config('core.routes.home'));
        }
        return back();
    }

}