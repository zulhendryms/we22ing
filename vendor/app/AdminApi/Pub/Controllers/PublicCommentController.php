<?php

namespace App\AdminApi\Pub\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Trading\Entities\PurchaseRequest;
use App\Core\Security\Entities\Notification;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Security\Entities\User;
use App\Core\Collaboration\Entities\Task;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\FileCloudService;
use App\Core\Base\Services\HttpService;
use App\Core\Internal\Events\EventSendNotificationSocketOneSignal;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\Core\Internal\Services\SocketioService;

class PublicCommentController extends Controller
{
    private $httpService;
    protected $fileCloudService;
    protected $roleService;
    private $module;
    private $crudController;
    protected $SocketioService;
    public function __construct(
        FileCloudService $fileCloudService,
        RoleModuleService $roleService,
        HttpService $httpService
        )
    {
        $this->roleService = $roleService;
        $this->httpService = $httpService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
        $this->fileCloudService = $fileCloudService;
        $this->module = 'pubcomment';
        $this->crudController = new CRUDDevelopmentController();
        $this->SocketioService = new SocketioService();
    }

    public function config(Request $request)
    {
        $fields = $this->httpService->get('/portal/api/development/table/vuemaster?code=PublicComment');
        foreach ($fields as &$row) { //combosource
            if ($row->headerName  == 'Company') $row->source = comboselect('company');
            if ($row->headerName == 'PurchaseRequest') $row->source = comboselect('trdpurchaserequest');
        };
        return $fields;
    }

    public function list(Request $request)
    {
        $fields = $this->httpService->get('/portal/api/development/table/vuemaster?code=PublicComment');
        $data = DB::table('pubcomment as data') //jointable
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')

            ->leftJoin('user AS User', 'User.Oid', '=', 'data.User')
            ->leftJoin('trdpurchaserequest AS PurchaseRequest', 'PurchaseRequest.Oid', '=', 'data.PurchaseRequest');
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'pubcomment', 'Oid');
        $role = $this->roleService->list('PublicComment'); //rolepermission
        foreach ($data as $row) $row->Role = $this->roleService->generateRoleMasterCopy($role);
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = PublicComment::whereNull('GCRecord');

            $data = $data->orderBy('Oid')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSub($Oid)
    {
        $data = PublicComment::with('UserObj')->findOrFail($Oid);
        $data->CompanyName = $data->CompanyObj ? $data->CompanyObj->Name : null;
        $user = User::findOrFail($data->User);
        $data = $user->returnUserObj($data, 'User');
        return $data;
    }

    public function show(PublicComment $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function sendNotification($data, $to, $module, $message) {
        $user = Auth::user();

        switch ($module) {
            case 'Task':
                $code = 'Task: ';
                break;
            case 'PurchaseOrder':
                $code = ($data->Type == 'Purchaseorder' ? $data->Code : $data->RequestCode).": ";
                break;
            case 'CashBank':
                $code = $data->Code.': ';
                break;
        }    
        $action = [
            'name' => 'Open',
            'icon' => 'ArrowUpRightIcon',
            'type' => 'open_view',
            'portalget' => "development/table/vueview?code=".$module,
            'get' => strtolower($module)."/".$data->Oid,
        ];
        $param = [
            'User' => $to,
            'Company' => $data->Company,
            'PublicPost' => $data->Oid,
            'Icon' => 'MessageSquareIcon',
            'Color' => 'primary',
            'ObjectType' => $module,
            'Code' => $data->Oid,
            'Title' => $user->Name.': '.substr($message,0,50),
            'Message' => 'Comment at '.$code.' '.$module,
            'Action' => $action,
            'Type' => 'Feed'
        ];
        event(new EventSendNotificationSocketOneSignal($param));
    }

    public function create(Request $request) {
        $req = $request;
        try {
            $data;
            DB::transaction(function () use ($request, &$data) {
                $type = $request->has('Type') ? $request->input('Type') : 'PublicPost';
                $Oid = $request->input('Oid');
                
                $data = $this->crudController->saving($this->module, $request, null, false);
                $user = Auth::user();
                
                if (!in_array($type, ['ItemContent','Item','TruckingTransactionFuel','CashBankSubmission'])) {
                    $post = PublicPost::findOrFail($Oid);
                    $data->PublicPost = $post->Oid;
                    if (!in_array($post->ObjectType, ['PostVideo','PostImage','PostText'])) $data->{$post->ObjectType} = $post->Oid;
                    $data->Company = $post->Company;
                } else {
                    $post = null;
                    $data->{$type} = $Oid;
                    $data->Company = $user->Company;
                }
                $data->save();
                
                $tmp = Notification::where('PublicPost',$data->Oid)->where('Type','Comment')->get();
                if ($tmp && $tmp->count() > 0) {
                    $this->SocketioService->removeNotification($tmp);
                }
                DB::update("DELETE FROM notification WHERE PublicPost='{$data->Oid}' AND Type='Comment'");

                // DATA IN USED
                // $notifications = Notification::whereNull('GCRecord')->where('PublicPost', $post->Oid)->where('Type','Comment')->get();
                // foreach($notifications as $row) $row->delete();

                if ($post) { // notification
                    if ($post->ObjectType == 'Task') {
                        $tmp = Task::findOrFail($Oid);
    
                        $users=[];
                        if ($tmp->User && $tmp->User != $user->Oid) $users = array_merge($users, [$tmp->User]);
                        if ($tmp->User1 && $tmp->User1 != $user->Oid) $users = array_merge($users, [$tmp->User1]);
                        if ($tmp->User2 && $tmp->User2 != $user->Oid) $users = array_merge($users, [$tmp->User2]);
                        if ($tmp->User3 && $tmp->User3 != $user->Oid) $users = array_merge($users, [$tmp->User3]);
                        if ($tmp->UserFinal && $tmp->UserFinal != $user->Oid) $users = array_merge($users, [$tmp->UserFinal]);
                        $users = User::whereIn('Oid', $users)->pluck('Oid');
                        $this->sendNotification($tmp, $users, 'Task', $data->Message);
                    } elseif ($post->ObjectType == 'PurchaseOrder') {
                        $tmp = PurchaseOrder::where('Oid',$Oid)->first();
    
                        $notification = PublicApproval::where('PublicPost',$tmp->Oid)->where('User','!=',$user->Oid)->get();
                        $users=[];
                        foreach($notification as $row) $users[] = $row->user;
                        if ($tmp->Purchaser != $user->Oid) $users[] = $tmp->Purchaser;
                        if ($tmp->CreatedBy != $user->Oid) $users[] = $tmp->CreatedBy;
                        $this->sendNotification($tmp, $users, 'PurchaseOrder', $data->Message);
                    } elseif ($post->ObjectType == 'CashBank') {
                        $tmp = CashBank::findOrFail($Oid);
    
                        $users=[];
                        $notification = PublicApproval::where('PublicPost',$tmp->Oid)->where('User','!=',$user->Oid)->get();
                        foreach($notification as $row) $users[] = $row->user;
                        if ($tmp->CreatedBy != $user->Oid) $users[] = $tmp->CreatedBy;
                        $this->sendNotification($tmp, $users, 'CashBank', $data->Message);
                    }

                }
                $data->User = isset($request->User) ? $request->User : $user->Oid;
                if (!$data->Company) $data->Company = $user->Company;
                $data->save();

                if ($data->PublicPost) {
                    $post = PublicPost::with('Comments')->findOrFail($data->PublicPost);
                    $post->CountComment = $post->Comments()->count();
                    $post->save();
                }

                if (!$data) throw new UserFriendlyException('Data is failed to be saved');
            });

            $role = $this->roleService->list('PublicComment'); //rolepermission
            $data = $this->showSub($data->Oid);
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(PublicComment $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->delete();
            });
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
