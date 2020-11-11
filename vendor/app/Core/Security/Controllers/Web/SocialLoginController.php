<?php

namespace App\Core\Security\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Socialite;
use Illuminate\Http\Request;
use App\Core\Security\Entities\User;
use App\Core\Security\Services\UserService;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Traits\PostMessageScript;

class SocialLoginController extends Controller 
{
    use PostMessageScript;
    /** @var UserService $userService */
    protected $userService;

    /**
     * @param UserService $tmpStorageService
     * @return void
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param Request $request
     * @param string $provider
     */
    public function index(Request $request, $provider) 
    {
        $returnURL = $request->query(config('constants.return_url')) ?? session(config('constants.return_url'));
        if (is_null($returnURL)) $returnURL = config('app.url');
        session()->put(config('constants.return_url'), $returnURL);

        return Socialite::driver(strtolower($provider))->redirect();
    }

    /**
     * @param Request $request
     * @param string $provider
     */
    public function store(Request $request, $provider)
    {
        $payload =  Socialite::with($provider)->user();
        $provider = strtolower($provider);

        $q = User::where('UserName', $payload->email);
        $param = [];
        if ($provider == 'facebook') {
            $param = [ 'FacebookId' => $payload->id, 'FacebookData' => json_encode($payload) ];
            $q->orWhere('FacebookId', $payload->id);
        } else if ($provider == 'google') {
            $q->orWhere('GoogleId', $payload->id);
            $param = [ 'GoogleId' => $payload->id, 'GoogleData' => json_encode($payload) ];
        }

        $user = $q->first();

        if (is_null($user)) {
            $user = $this->userService->createCustomer(array_merge(
                $param,
                [
                    'UserName' => $payload->username,
                    'Name' => $payload->name,
                    'Image' => $payload->avatar
                ]
            ));
        } else {
            if (empty($user->Image)) $user->Image = $payload->avatar;
            $user->update($param);
        }

        Auth::login($user, true);
        $returnUrl = session(config('constants.return_url'));
        session()->remove(config('constants.return_url'));
        if (is_null($returnUrl)) $returnUrl = config('app.url');

        if (is_webview()) return $this->postActionMessage('logged_in');

        if (!$request->ajax()) return redirect($returnUrl);
    }
}