<?php

namespace App\Core\Internal\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\External\Services\OneSignalService;

class SendNotificationController extends Controller 
{
    /** @param OneSignalService $oneSignalService */
    protected $oneSignalService;

    public function __construct(OneSignalService $oneSignalService)
    {
        $this->oneSignalService = $oneSignalService;
    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {
       $this->validate($request, [
           'Title' => 'required',
           'Message' => 'required'
       ]);
       $title = $request->input('Title');
       $message = $request->input('Message');
       if ($request->input('All')) {
            $this->oneSignalService->sendToAllUsers($title, $message);
            return;
       }
       $this->oneSignalService->sendToUsers($title, $message, $request->input('Users'));
    }
}