<?php

namespace App\Core\Internal\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SetOneSignalTokenController extends Controller 
{
    public function index(Request $request)
    {
        $token = $request->input('onesignal_token');
        if (!is_null($request->user())) {
            $deviceId = $request->cookie(config('constants.device_id'));
            if (!is_null($deviceId)) {
                $request->user()->Devices()
                ->where('Oid', $deviceId)->update([ 'OneSignalToken' => $token ]);
            }
        }
        return response(1)->cookie(config('constants.onesignal_token'), $token, 60 * 24 * 365 * 5);
    }
}