<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;
use Illuminate\Support\Facades\DB;

class OneSignalService {

    /** @var HttpService $httpService */
    private $httpService; 
    private $appId;

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService
        ->baseUrl(config('services.onesignal.url'))
        ->json();
        $this->appId = config('services.onesignal.app_id');
    }
    
    //TESTLOG
    //2020-06-21 LOG MASUK DI BROWSER DAN DI MOBILE

    public function send($title, $message, $ids)
    {
        $this->httpService->post('/notifications', [
            'app_id' => $this->appId,
            'headings' => [
                'en' => $title
            ],
            'contents' => [
                'en' => $message
            ],
            'include_player_ids' => $ids
        ]);
    }

    public function sendToUser($title, $message, $id)
    {
        $devices = DB::table('device')
            ->distinct('OneSignalToken')
            ->whereNotNull('OneSignalToken')
            ->where('User', $id)
            ->pluck('OneSignalToken');
        return $this->send($title, $message, $devices);
    }

    public function sendToUsers($title, $message, $ids)
    {
        $devices = DB::table('device')
            ->distinct('OneSignalToken')
            ->whereNotNull('OneSignalToken')
            ->whereIn('User', $ids)
            ->pluck('OneSignalToken');
        return $this->send($title, $message, $devices);
    }

    public function sendToAllUsers($title, $message)
    {
        $devices = DB::table('device')
        ->distinct('OneSignalToken')
        ->whereNotNull('OneSignalToken')
        ->pluck('OneSignalToken');
        return $this->send($title, $message, $devices);
    }
}

?>
