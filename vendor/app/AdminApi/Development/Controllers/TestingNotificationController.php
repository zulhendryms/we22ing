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

class TestingNotificationController extends Controller
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
        if ($tmp || $tmp->count() > 0) $this->SocketioService->removeNotification($tmp);
    }

    private function getTo($request) {
        // 5b2f0dc2-e9ec-4efb-9876-e59329372a46	    06beb986-2ca0-11ea-94dc-1a582ceaab05	admin@ezbooking.co
        // fb4105e3-a272-4ac8-b8b7-3d051a1ce70c	    06beb986-2ca0-11ea-94dc-1a582ceaab05	vivi1@ezbooking.co
        if ($request->has('to')) {
            if (in_array($request->input('to'),['admin','admin@ezbooking.co'])) return '5b2f0dc2-e9ec-4efb-9876-e59329372a46';
            elseif (in_array($request->input('to'),['vivi','vivi1','vivi1@ezbooking.co'])) return 'fb4105e3-a272-4ac8-b8b7-3d051a1ce70c';
            elseif (in_array($request->input('to'),['all'])) return [
                '5b2f0dc2-e9ec-4efb-9876-e59329372a46',
                'fb4105e3-a272-4ac8-b8b7-3d051a1ce70c'
            ];
        } else return '5b2f0dc2-e9ec-4efb-9876-e59329372a46';
    }

    private function getCompany($request) {
        return $request->has('company') ? $request->input('company') : '06beb986-2ca0-11ea-94dc-1a582ceaab05';
    }

    public function testSocketIO(Request $request) {
        $company = $this->getCompany($request);        
        $to = $this->getTo($request);
        $param = [
            "Company" => $company,
            "User" => '5b2f0dc2-e9ec-4efb-9876-e59329372a46',
            "Oid" => '5b2f0dc2-e9ec-4efb-9876-e59329372a46',
            "Type" => "Log",
            "Code" => now()->addHours(company_timezone())->toDateTimeString(),
            "Date" => now()->addHours(company_timezone())->toDateTimeString(),
            "Title" => 'test '.now()->addHours(company_timezone())->toDateTimeString(),
            "Message" => 'test message'.now()->addHours(company_timezone())->toDateTimeString(),
            "App" => 'administrator',
            "Icon" => 'IconBox',
            "Color" => '#000000',
            "Action" => [
              "name" => "Open",
              "icon" => "ArrowUpRightIcon",
              "type" => "download",
              "url" => "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"
            ]
        ];
        if ($request->has('return')) if ($request->input('return') == 'param') return $param;
        $response = $this->SocketioService->sendNotification($param, $to);
        return $response;
    }

    public function testOneSignal(Request $request) {
        $company = $this->getCompany($request);        
        $to = $this->getTo($request);
        $param = [
            "Company" => $company,
            "Code" => now()->addHours(company_timezone())->toDateTimeString(),
            "Date" => now()->addHours(company_timezone())->toDateTimeString(),
            "Title" => 'test '.now()->addHours(company_timezone())->toDateTimeString(),
            "Message" => 'test message'.now()->addHours(company_timezone())->toDateTimeString(),
            "Type" => 'Chat',
            "Icon" => 'IconBox',
            "Color" => '#000000',
        ];
        $response = $this->OneSignalService->sendNotification($param['Title'], $param['Message'], $to, 'administrator', true);
        if ($request->has('return')) if ($request->input('return') == 'param') return $param;
        else return $response;
    }
}
