<?php

namespace App\Core\Security\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Socialite;
use Illuminate\Http\Request;
use App\Core\Security\Entities\User;
use App\Core\Security\Requests\Web\LoginRequest;
use App\Core\Security\Services\UserService;
use App\Core\Security\Requests\Web\RegisterRequest;
use App\Core\Security\Services\AuthService;
use App\Core\Security\Traits\PostMessageScript;

class RegisterController extends Controller 
{

    use PostMessageScript;

    /** @var UserService $userService */
    protected $userService;
    /** @var UserService $userService */
    protected $authService;

    /**
     * @param UserService $userService
     * @return void
     */
    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;    
        $this->authService = $authService;    
    }

    public function store(RegisterRequest $request)
    {
        $param = $request->input();
        unset($param['ConfirmPassword']);
        unset($param[config('constants.return_url')]);
        $user = $this->userService->createCustomer($param);
        $this->authService->login([ 
            'UserName' => $param['UserName'], 
            'Password' => $param['Password'] 
        ], 'web', [ 'remember' => true ]);
        
        $returnUrl = $request->query(config('constants.return_url')) ?? session(config('constants.return_url'));
        session()->remove(config('constants.return_url'));
        if (is_null($returnUrl)) $returnUrl = config('app.url');

        if (is_webview()) return $this->postActionMessage('logged_in');

        if (!$request->ajax()) return redirect($returnUrl);
    }

}