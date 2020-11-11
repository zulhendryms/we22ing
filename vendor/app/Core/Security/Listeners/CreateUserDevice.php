<?php

namespace App\Core\Security\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Security\Events\UserLoggedIn;
use Illuminate\Http\Request;
use App\Core\Security\Services\UserDeviceService;
use Illuminate\Support\Facades\Cookie;

class CreateUserDevice
{
    protected $request;
    protected $deviceService;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request, UserDeviceService $deviceService)
    {
        $this->request = $request;
        $this->deviceService = $deviceService;
    }

    /**
     * Handle the event.
     *
     * @param  UserLoggedIn  $event
     * @return void
     */
    public function handle(UserLoggedIn $event)
    {
        $user = $event->user;
        $oneSignalToken = $this->request->cookie(config('constants.onesignal_token'));
        $deviceId = $this->request->cookie(config('constants.device_id'));

        if (!empty($deviceId) || !empty($oneSignalToken)) {
            $device = $user->Devices()->where(function ($q2) use ($deviceId, $oneSignalToken) {
                if (!empty($deviceId)) {
                    $q2->where('Oid', $deviceId);
                }
        
                if (!empty($oneSignalToken)) {
                    $q2->orWhere('OneSignalToken', $oneSignalToken);
                }
            })->first();
            if (isset($device)) {
                if (!empty($oneSignalToken)) {
                    $device->OneSignalToken = $oneSignalToken;
                    $device->save();
                }
            }
        }

        if (!isset($device)) {
            // $device = $user->Devices()->create([
            //     'OneSignalToken' => $oneSignalToken
            // ]);
            $device = $this->deviceService->create([
                'User' => $user->Oid,
                'OneSignalToken' => $oneSignalToken
            ]);
        }

        Cookie::queue(config('constants.device_id'), $device->Oid, 60 * 24 * 365 * 5);
    }
}
