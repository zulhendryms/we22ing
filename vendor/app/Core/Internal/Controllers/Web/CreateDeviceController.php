<?php

namespace App\Core\Internal\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\Security\Services\UserDeviceService;

class CreateDeviceController extends Controller 
{
    /**
     * @param UserDeviceService $deviceService
     */
    protected $deviceService;

    public function __construct(UserDeviceService $deviceService)
    {
        $this->$deviceService = $deviceService;   
    }

    public function index(Request $request)
    {
        $device = $this->deviceService->create([
            'OneSignalToken' => $request->cookie(config('constants.onesignal_token'))       
        ]);
        return response($device->Oid)->cookie(config('constants.device_id'), $device->Oid, 60 * 24 * 365 * 5);
    }
}