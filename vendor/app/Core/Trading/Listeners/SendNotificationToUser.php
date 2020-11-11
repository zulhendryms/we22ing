<?php

namespace App\Core\Trading\Listeners;

use App\Core\Trading\Events\PurchaseRequestSubmit;
use App\Core\Internal\Services\OneSignalService;
use App\Core\Internal\Services\SocketioService;
use App\Core\Security\Entities\Notification;
use App\Core\Security\Entities\NotificationUser;
use App\Core\Security\Entities\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SendNotificationToUser
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
    public function handle(PurchaseRequestSubmit $event)
    {
        $userData = User::where('Oid',$event->param['User'] ?: Auth::user()->Oid)->first();
        $user = $userData->Oid;
        $user2 = isset($event->param['User2']) ? $event->param['User2'] : null;
        $user3 = isset($event->param['User3']) ? $event->param['User3'] : null;
        $company = $event->param['Company'] ?: $userData->Company;
        $title = $event->param['Title'];
        $message = $event->param['Message'];
        $icon = isset($event->param['Icon']) ? $event->param['Icon'] : 'SettingsIcon';
        $color = isset($event->param['Color']) ? $event->param['Color'] : 'primary';
        $action = isset($event->param['Action']) ? $event->param['Action'] : null;
        $purchaseRequest = $event->param['PurchaseRequest'];
        $code = $event->param['Code'];

        // kirim ke onesignal
        $this->OneSignalService->sendNotification($title, $message, $user, 'administrator');
        if ($user2) $this->OneSignalService->sendNotification($title, $message, $user2, 'administrator');
        if ($user3) $this->OneSignalService->sendNotification($title, $message, $user3, 'administrator');

        // kirim ke socketio
        $SocketioService = new SocketioService();
        // $SocketioService->sendNotification($title, $message, $user, 'administrator', $company);
        // if ($user2) $SocketioService->sendNotification($title, $message, $user2, 'administrator', $company);
        // if ($user3) $SocketioService->sendNotification($title, $message, $user3, 'administrator', $company);
        $SocketioService->sendNotification($user, $event);
        if ($user2) $SocketioService->sendNotification($user2, $event);
        if ($user3) $SocketioService->sendNotification($user3, $event);

        //save ke db
        $notification = new Notification();
        $notification->Company = $company ?: company()->Oid;
        $notification->PurchaseRequest = $purchaseRequest;
        $notification->Code = $code ?: now()->format('mdHis').'-'.str_random(3);
        $notification->Date = now()->addHours(company_timezone())->toDateTimeString();
        $notification->Title = $title;
        $notification->Description = $message;
        $notification->Icon = $icon;
        $notification->Color = $color;
        $notification->Action = $action;
        $notification->save();

        $this->newNotificationDetail($notification, $user);
        if ($user2) $this->newNotificationDetail($notification, $user2);
        if ($user3) $this->newNotificationDetail($notification, $user3);
    }

    private function newNotificationDetail($notification, $user) {
        $notificationuser = new NotificationUser();
        $notificationuser->Company = $notification->Company;
        $notificationuser->Notification = $notification->Oid;
        $notificationuser->User = $user;
        $notificationuser->save();
    }
}
