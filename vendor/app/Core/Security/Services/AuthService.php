<?php

namespace App\Core\Security\Services;

use App\Core\Security\Entities\User;
use App\Core\Security\Entities\Device;
use App\Core\External\Services\OAuthService;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Events\UserLoggedIn;
use App\Core\Security\Exceptions\LoginFailedException;
use App\Core\Internal\Services\SocketioService;

class AuthService 
{
    protected $oauthService;
    protected $userDeviceService;
    protected $SocketioService;

    public function __construct(
        OAuthService $oauthService, 
        UserDeviceService $userDeviceService
    )
    {
        $this->oauthService = $oauthService;
        $this->userDeviceService = $userDeviceService;
        $this->SocketioService = new SocketioService();
    }

    /**
     * Login a user
     * 
     * @param User|array $params
     * @param array $options
     * @return mixed
     */
    public function login($params, $type = 'web', $options = [ 'remember' => false ])
    {
        if ($type == 'api') {
            $payload = $this->apiLogin($params);
        } else {
            $payload = $this->webLogin($params, $options['remember']);
        }
        if (is_null($payload)) $this->loginFailed();
        $this->loggedIn($params);
        return $payload;
    }

    /**
     * Login using web method
     * 
     * @param User|array $params
     * @return User
     */
    protected function webLogin($params, $remember = false)
    {
        if ($params instanceof User) {
            Auth::login($params, $remember);
        } else {
            Auth::attempt([ 'UserName' => $params['UserName'], 'password' => $params['Password'] ], $remember);
        }
        return Auth::user();
    }

    /**
     * Login using api method
     * 
     * @param array $params
     * @return mixed
     */
    protected function apiLogin($params)
    {
        try {
            $login = (array) $this->oauthService->passwordGrant(
                $params['UserName'],
                $params['Password'],
                $params['Company']
            );
            return $login;
        } catch (\Exception $ex) {
            $this->loginFailed();
        }
    }

    public function logout($type = 'web', $options = null)
    {
        if ($type == 'api') {
            $user = Auth::user();
            $device = Device::where('User', $user->Oid)->orderBy('CreatedAt','Desc')->first();
            $this->apiLogout($device);            
            $user->token()->revoke();
            return 1;
        }
        return $this->webLogout();
    }

    /**
     * Logout for web
     * 
     * @return void
     */
    protected function webLogout()
    {
        $this->userDeviceService->deleteFromSession();
        session()->remove(config('constants.user_id'));
        session()->remove(config('constants.language'));
        session()->remove(config('constants.timezone'));
        session()->remove(config('constants.device_id'));
        session()->remove(config('constants.currency'));
        Auth::logout();
    }

    /**
     * Logout for api
     * 
     * @param string $deviceId
     * @return void
     */
    protected function apiLogout($deviceId)
    {
        if (isset($deviceId)) $this->userDeviceService->delete($deviceId);
    }

     /**
     * Create token
     * 
     * @param User $user
     * @return string
     */
    public function createToken(User $user)
    {
        return $user->createToken(null, [])->accessToken;
    }

    /**
     * Throw login failed exception
     * 
     * @throws LoginFailedExceptions
     */
    protected function loginFailed()
    {
        throw new LoginFailedException("Login failed, Username and Password doesn't match");
    }

    /**
     * Trigger user logged in event
     * 
     * @param string $username
     * @return void
     */
    protected function loggedIn($param)
    {
        $username = $param['UserName'];
        $user = Auth::user();
        if (is_null($user)) $user = User::where('UserName', $username)->first();
        if (isset($param['Device'])) {
            $to = 'administrator_'.$user->Company.'_'.$user->Oid;
            if (substr($param['Device'],3)=='web') {
                $valid = $user->SessionWeb == $param['Device'];
                if (!$valid) {
                    //apirevoke
                    // if ($user->SessionWeb) $this->SocketioService->forceLogOut($to,$user->SessionWeb);
                    $user->SessionWeb = $param['Device'];
                    $user->save();
                }
            } else {
                $valid = $user->SessionMobile == $param['Device'];
                if (!$valid) {
                    // apirevoke
                    // if ($user->SessionMobile) $this->SocketioService->forceLogOut($to,$user->SessionMobile);
                    $user->SessionMobile = $param['Device'];
                    $user->save();
                }
            }
        }
        if (isset($user)) event(new UserLoggedIn($user));
    }
}