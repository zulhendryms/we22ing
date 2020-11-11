<?php

namespace App\AdminApi\Security\Controllers;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Base\Services\HttpService;
use App\Core\Security\Entities\Notification;
use Illuminate\Support\Facades\Auth;
use App\Core\Trading\Entities\PurchaseRequest;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Trading\Events\PurchaseRequestSubmit;

class NotificationController extends Controller
{
    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            
            $dataNot = Notification::where('User', $user->Oid)->whereIn('Type',['Approve','Approval','Notification','Reject'])->orderBy('Date','desc')->limit(20)->get();
            $dataNotCount = Notification::where('User', $user->Oid)->whereIn('Type',['Approve','Approval','Notification','Reject'])->addSelect('Oid')->whereNull('DateRead')->get();
            foreach($dataNot as $row) {
                $row->Action = json_decode($row->Action);
                $row->UserObj = $row->CreatedByObj ? $row->CreatedByObj->UserProfileObj() : [
                    'Oid'=> $row->CreatedBy,
                    'Name'=> 'Unknown',
                    'Image'=> null,
                    'Color'=> '34cceb',
                ];
                // $row->Action = $row->ActionUrl;
            }

            $dataFeed = Notification::where('User', $user->Oid)->whereIn('Type',['Feed','Comment'])->orderBy('Date','desc')->limit(20)->get();
            $dataFeedCount = Notification::where('User', $user->Oid)->whereIn('Type',['Feed','Comment'])->addSelect('Oid')->whereNull('DateRead')->get();
            foreach($dataFeed as $row) {
                $row->Action = json_decode($row->Action);
                $row->UserObj = $row->CreatedByObj ? $row->CreatedByObj->UserProfileObj() : [
                    'Oid'=> $row->CreatedBy,
                    'Name'=> 'Unknown',
                    'Image'=> null,
                    'Color'=> '34cceb',
                ];
                // $row->Action = $row->ActionUrl;
            }

            $dataLog = Notification::where('User', $user->Oid)->whereIn('Type',['Log'])->orderBy('Date','desc')->limit(20)->get();
            $dataLogCount = Notification::where('User', $user->Oid)->whereIn('Type',['Log'])->addSelect('Oid')->whereNull('DateRead')->get();
            foreach($dataLog as $row) {
                $row->Action = json_decode($row->Action);
                $row->UserObj = $row->CreatedByObj ? $row->CreatedByObj->UserProfileObj() : [
                    'Oid'=> $row->CreatedBy,
                    'Name'=> 'Unknown',
                    'Image'=> null,
                    'Color'=> '34cceb',
                ];
                // $row->Action = $row->ActionUrl;
            }
            return [
                'NotificationCount' => $dataNotCount->count(),
                'Notification' => $dataNot,
                'FeedCount' => $dataFeedCount->count(),
                'Feed' => $dataFeed,
                'LogCount' => $dataLogCount->count(),
                'Log' => $dataLog,
            ];

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function createPost() {
        $user = Auth::user();
            
        $post = PublicPost::where('ObjectType','PurchaseOrder')->first();
        $notification = new Notification();
        $notification->Company = $user->Company;
        $notification->User = $user->Oid;
        $notification->PublicPost = $post->Oid;
        $notification->ObjectType = 'PurchaseOrder';
        $notification->Code = $post->Code;
        $notification->Date = $post->Date;
        $notification->Type = 'Notification';
        $notification->Title = $post->Description;
        $notification->Description = 'Please Approve';
        $notification->Icon = 'CheckCircleIcon';
        $notification->Color = 'Primary';
        $notification->Action = json_encode([                
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_view',
            'portalget' => "development/table/vueview?code=PurchaseOrder",
            'get' => "purchaseorder/".$post->Oid,
        ]);
        $notification->ActionUrl = 'purchaseorder/form?item=' . $post->Oid.'&type=PurchaseRequest';
        $notification->save();
    }

    private function createComment() {
        $user = Auth::user();
            
        $post = PublicPost::where('ObjectType','PurchaseOrder')->first();
        $notification = new Notification();
        $notification->Company = $user->Company;
        $notification->User = $user->Oid;
        $notification->PublicPost = $post->Oid;
        $notification->ObjectType = 'PurchaseOrder';
        $notification->Code = $post->Code;
        $notification->Date = $post->Date;
        $notification->Type = 'Feed';
        $notification->Title = $user->UserName.' write some message';
        $notification->Description = 'This is test message';
        $notification->Icon = 'MessageSquareIcon';
        $notification->Color = 'Primary';
        $notification->Action = json_encode([                
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_view',
            'portalget' => "development/table/vueview?code=PurchaseOrder",
            'get' => "purchaseorder/".$post->Oid,
        ]);
        $notification->ActionUrl = 'purchaseorder/form?item=' . $post->Oid.'&type=PurchaseRequest';
        $notification->save();
    }

    private function createLog() {
        $user = Auth::user();
            
        $post = PublicPost::where('ObjectType','PurchaseOrder')->first();
        $notification = new Notification();
        $notification->Company = $user->Company;
        $notification->User = $user->Oid;
        $notification->PublicPost = $post->Oid;
        $notification->ObjectType = 'PurchaseOrder';
        $notification->Code = $post->Code;
        $notification->Date = $post->Date;
        $notification->Type = 'Log';
        $notification->Title = $user->UserName.' is notified';
        $notification->Description = 'This is approved by someone else';
        $notification->Icon = 'AlarmIcon';
        $notification->Color = 'Primary';
        $notification->Action = json_encode([                
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_view',
            'portalget' => "development/table/vueview?code=PurchaseOrder",
            'get' => "purchaseorder/".$post->Oid,
        ]);
        $notification->ActionUrl = 'purchaseorder/form?item=' . $post->Oid.'&type=PurchaseRequest';
        $notification->save();
    }
    
    public function create(Request $request)
    {
        try {
            $this->createPost();
            $this->createPost();
            $this->createPost();
            $this->createPost();
            $this->createComment();
            $this->createComment();
            $this->createComment();
            $this->createComment();
            $this->createLog();
            $this->createLog();
            $this->createLog();
            $this->createLog();

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function quick(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'quick';
            $count = Notification::where('User', $user->Oid)->whereNull('DateRead')->get();
            $data = Notification::where('User', $user->Oid);
            if ($type == 'quick') $data = $data->whereNull('DateRead')->limit(50);
            $data = $data->orderBy('Date','desc')->get();
            foreach($data as $row) {
                $row->ActionNew = json_decode($row->Action);
                $row->Action = $row->ActionUrl;
            }
            return [
                'Count' => $count->count(),
                'List' => $data,
            ];

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function read(Request $request, $oid = null)
    {
        try {   
            $user = Auth::user();
            $data = Notification::where('User', $user->Oid)->whereNull('DateRead');
            if ($oid) $data->where('Oid', $oid);
            $data = $data->get();
            foreach($data as $row) {
                $row->DateRead = now()->addHours(company_timezone())->toDateTimeString();
                $row->save();                
            }
            return $data;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function test(Request $request)
    {
        $login = Auth::user();
        $user = $request->has('user') ? $request->input('user') : $login->Oid;
        $company = $request->has('company') ? $request->input('company') : $login->Company;
        $param = [
            'User'=> $user,
            'Company' => $company,
            'Title' => $request->input('title') ? $request->input('title') : 'Title '.now()->format('mdHis').str_random(2),
            'Message' => $request->input('message') ? $request->input('message') : 'This is a Testing Message '.now()->format('mdHis').str_random(2),
        ];
        event(new PurchaseRequestSubmit($param));
    }
}
