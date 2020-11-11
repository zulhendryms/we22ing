<?php

namespace App\Core\Security\Services;

use App\Core\Security\Entities\User;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Security\Events\UserCreated;
use Illuminate\Support\Facades\DB;
use App\Core\Security\Entities\Device;

class UserDeviceService 
{
    /**
     * Create user device
     * 
     * @param array $params
     */
    public function create($params, $saveToSession = false)
    {
        $agent = new \Jenssegers\Agent\Agent;
        if (!isset($params['User']) && Auth::check()) {
            $params['User'] = Auth::user()->Oid;
        }
        if (!isset($params['OS'])) {
            $params['OS'] = $agent->platform();
        }
        if (!isset($params['Brand'])) {
            $params['Brand'] = $agent->browser();
            $params['Version'] = $agent->version($params['Brand']);
        }
        $device = Device::create($params);
        if ($saveToSession) session()->put(config('constants.device_id'), $device->Oid);
        return $device;
    }

    /**
     * Delete user device
     * 
     * @param string $id
     * @return void
     */
    public function delete($id)
    {
        return Device::destroy($id);
    }

    /**
     * Delete user device stored in session
     * 
     * @return void
     */
    public function deleteFromSession()
    {
        if (session()->isStarted()) {
            $id = session(config('constants.device_id'));
            if (isset($id)) {
                session()->remove(config('constants.device_id'));
                return Device::destroy(session($id));
            }
        }
    }
}