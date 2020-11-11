<?php

namespace App\AdminApi\Development\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Core\Security\Entities\Notification;
use Validator;
use App\Core\Internal\Services\SocketioService;
use App\Core\Internal\Services\OneSignalService;

class TestingController extends Controller
{
    protected $OneSignalService;
    protected $SocketioService;
    public function __construct(OneSignalService $OneSignalService)
    {
        $this->SocketioService = new SocketioService();
        $this->OneSignalService= $OneSignalService;
    }
    public function testRemoveNotification(Request $request) {
        $tmp = Notification::where('Oid',$request->Oid)->first();
        // dd($tmp);
        if ($tmp || $tmp->count() > 0) $this->SocketioService->removeNotification($tmp);
    }

    public function testOneSignal(Request $request) {
        $return = false;
        if ($request->has('return')) $return = $request->input('return');
        if ($request->input('type') == 'send') {
            // $to = $request->input('to');
            // $this->OneSignalService->sendNotification('testing header', 'testing message', $to, 'administrator');
            return $this->OneSignalService->test2(1, $return);
        } elseif ($request->input('type') == 'send2') {
            // $to = $request->input('to');
            // $this->OneSignalService->sendNotification2($to, $return);
            return $this->OneSignalService->test2(2, $return);
        } elseif ($request->input('type') == 'get') {
            // return $this->OneSignalService->test($return);
            return $this->OneSignalService->test2(3, $return);
        }        
    }
}
