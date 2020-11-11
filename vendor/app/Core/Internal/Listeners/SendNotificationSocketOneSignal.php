<?php

namespace App\Core\Internal\Listeners;

use App\Core\Internal\Events\EventSendNotificationSocketOneSignal;
use App\Core\Internal\Services\OneSignalService;
use App\Core\Internal\Services\SocketioService;
use App\Core\Security\Entities\Notification;
use App\Core\Security\Entities\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SendNotificationSocketOneSignal
{

    protected $OneSignalService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(OneSignalService $OneSignalService)
    {
        $this->OneSignalService = $OneSignalService;
    }

    /** 
     * Handle the event.
     *
     * @param  mixed $event
     * @return void
     */
    public function handle(EventSendNotificationSocketOneSignal $event)
    {
        $oneSignal = true;
        $param = $event->param;
        $param['App'] = isset($param['App']) ? $param['App'] : 'Administrator';
        $param['Company'] = $param['Company'] ?: company()->Oid;
        $param['Title'] = isset($param['Title']) ? $param['Title'] : 'Title Testing';
        $param['Message'] = isset($param['Message']) ? $param['Message'] : 'Message Testing';    
        $param['Icon'] = isset($param['Icon']) ? $param['Icon'] : 'SettingsIcon';
        $param['Color'] = isset($param['Color']) ? $param['Color'] : 'primary';
        $param['Action'] = isset($param['Action']) ? $param['Action'] : null;
        $param['ActionUrl'] = isset($param['ActionUrl']) ? $param['ActionUrl'] : null;
        $param['Code'] = isset($param['Code']) ? $param['Code'] : now()->format('mdHis').'-'.str_random(3);
        $param['Type'] = isset($param['Type']) ? $param['Type'] : 'notification';

        // kirim ke socketio
        $socketioService = new SocketioService();
        $user = $param['User'];
        $socket = [];
        if (gettype($user) == 'array') {            
            $user = removeDuplicateArray($user);
            if (count($user) == 1) $user = $user[0];
        }
        if (gettype($user) == 'object') $user = $user[0];
        if (gettype($user) == 'array') {
            unset($param['User']);
            foreach ($user as $u) {
                if (!$u) {
                    continue;
                }
                $tmp = $this->newNotification($param, $u);
                $param['Oid'] = $tmp->Oid;
                $param['To'] = 'administrator_'.$param['Company'].'_'.$u;
                $socket[] = $param;
            }
            // dd(json_encode($socket));
            $socketioService->sendNotification($socket);
            if ($oneSignal) $this->OneSignalService->sendNotification($param['Title'], $param['Message'], $user, 'administrator');            
        } else {
            $tmp = $this->newNotification($param, $user);
            $param['Oid'] = $tmp->Oid;
            $param['To'] = 'administrator_'.$param['Company'].'_'.$user;
            $socket = $param;
            // dd(json_encode($socket));
            $socketioService->sendNotification($socket);
            if ($oneSignal) $this->OneSignalService->sendNotification($param['Title'], $param['Message'], $user, 'administrator');
        }
    }

    private function newNotification($param, $user) {
        $notification = new Notification();
        $notification->Company = $param['Company'] ?: company()->Oid;
        $notification->User = $user;
        $notification->PublicPost = isset($param['PublicPost']) ? $param['PublicPost'] : null;
        $notification->ObjectType = isset($param['ObjectType']) ? $param['ObjectType'] : null;
        $notification->Code = $param['Code'];
        $notification->Date = now()->addHours(company_timezone())->toDateTimeString();
        $notification->Type = $param['Type'];
        $notification->Title = $param['Title'];
        $notification->Description = $param['Message'];
        $notification->Icon = $param['Icon'];
        $notification->Color = $param['Color'];
        $notification->Action = json_encode($param['Action']);
        $notification->save();
        return $notification;
    }
}
