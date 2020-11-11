<?php

namespace App\Core\Setup\Middlewares;

use Closure;
use Illuminate\Support\Facades\App;
use App\Core\Master\Entities\Currency;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Cookie;
use App\Core\Security\Entities\Device;

class UserConfig
{

    /** @var Agent $agent */
    protected $agent;

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
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
        // $curr = company()->Currency;
        $curr = null;
        $mobile = false;
        if (session()->isStarted()) {
            if (session()->has(config('constants.currency'))) $curr = session()->get(config('constants.currency'));
            if (session()->has(config('constants.language'))) $lang = session()->get(config('constants.language'));
            if (session()->has(config('constants.mobile')) && $this->agent->isMobile()) $mobile = session()->get(config('constants.mobile'));
        }
        if ($request->query->has(config('constants.currency'))) {
            $curr = $request->query(config('constants.currency'));
        }
        if ($request->query->has(config('constants.language'))) {
            $lang = $request->query(config('constants.language'));
        }
        if ($request->query->has(config('constants.mobile')) && $this->agent->isMobile()) {
            $mobile = $request->query(config('constants.mobile'));
        }
        if ($request->query->has(config('constants.onesignal_token'))) {
            $oneSignalToken = $request->query(config('constants.onesignal_token'));
            if (!empty($oneSignalToken)) {
                if (!is_null($request->user())) {
                    $deviceId = $request->cookie(config('constants.device_id'));
                    if (!is_null($deviceId)) {
                        $request->user()->Devices()
                        ->where('Oid', $deviceId)->update([ 'OneSignalToken' => $oneSignalToken ]);
                    }
                }
                Cookie::queue(
                    config('constants.onesignal_token'),
                    $oneSignalToken,
                    60 * 24 * 365 * 5
                );
            }
        }
        if (session()->isStarted()) {
            session()->put(config('constants.currency'), $curr);
            session()->put(config('constants.language'), $lang);
            if ($mobile) session()->put(config('constants.mobile'), $mobile);
        }
        App::setLocale($lang);
        setlocale(LC_TIME, $lang == 'en' ? 'en_US' : 'zh_CN');

        $request->currency = Currency::find($curr);
        $request->language = $lang;

        return $next($request);
    }
}
