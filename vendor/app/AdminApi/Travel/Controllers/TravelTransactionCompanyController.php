<?php

namespace App\AdminApi\Travel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\POS\Services\POSStatusService;
use App\Core\Security\Services\RoleModuleService;
use App\Core\POS\Services\POSETicketService;
use App\Core\Accounting\Services\SalesPOSService;
use App\Core\Accounting\Services\SalesPOSSessionService;
use App\Core\Base\Services\HttpService;
use App\Core\POS\Entities\POSETicketLog;
use App\Core\Internal\Entities\Status;
use Carbon\Carbon;
use Validator;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TravelTransactionCompanyController extends Controller
{
    protected $posETicketService;
    protected $posStatusService;
    protected $roleService;
    protected $salesPosService;
    protected $salesPosSessionService;
    protected $httpService;
    private $module;
    private $crudController;

    public function __construct(
        POSStatusService $posStatusService, 
        POSETicketService $posETicketService,
        RoleModuleService $roleService,
        SalesPOSService $salesPosService,
        SalesPOSSessionService $salesPosSessionService,
        HttpService $httpService
        )
    {
        $this->posStatusService = $posStatusService;
        $this->posETicketService = $posETicketService;
        $this->roleService = $roleService;
        $this->salesPosService = $salesPosService;
        $this->salesPosSessionService = $salesPosSessionService;
        $this->httpService = $httpService;
        $this->httpService
            // ->baseUrl(config('services.ezbmodule.url'))
            ->baseUrl('http://ezbpostest.ezbooking.co:888')
            ->json();
        $this->module = 'trvtransaction';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 200, 'h'=>0, 'n'=>'Code'];
        $fields[] = ['w'=> 120, 'h'=>0, 'n'=>'Company'];
        $fields[] = ['w'=> 120, 'h'=>0, 'n'=>'Date'];
        $fields[] = ['w'=> 120, 'h'=>0, 'n'=>'ContactName'];
        $fields[] = ['w'=> 120, 'h'=>0, 'n'=>'StatusName'];
        $fields = $this->crudController->jsonConfig($fields);
        
        return $fields;
    }
    
    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = "SELECT p.Oid, p.Code, co.Name AS Company, DATE_FORMAT(p.Date, '%Y-%m-%d') AS Date, p.ContactName, s.Code AS StatusName
                FROM pospointofsale p
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN traveltransaction t ON t.Oid = p.Oid
                LEFT OUTER JOIN trvtransactiondetail d ON p.Oid = d.TravelTransaction
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                WHERE i.Company = '{$user->Company}'  AND p.Company != '{$user->Company}'  AND s.Code IN ('paid','complete')
                GROUP BY p.Oid, p.Code, p.Date, p.ContactName, s.Code";
            $data = DB::select($query);
            $role = $this->roleService->list('POS');
            $action = $this->roleService->action('POS');
            foreach ($data as $row) $row->Role = $this->generateRole($row->StatusName, $role, $action);
            // return serverSideReturn($data, $fields);
            return $data;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    private function generateRole($status, $role = null, $action = null) {
        if ($status) $status = Status::where('Code',$status);
        if (!$role) $role = $this->list('POS');
        if (!$action) $action = $this->action('POS');

        return [
            'IsRead' => isset($role->IsRead) ? $role->IsRead : false,
            'IsAdd' => isset($role->IsAdd) ? $role->IsAdd : false,
            'IsEdit' => $this->roleService->isAllowDelete($status, isset($role->IsEdit) ? $role->IsEdit : false),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($status, $role->IsDelete),
            'Cancel' => $this->roleService->isAllowCancel($status, isset($action->Cancel) ? $action->Cancel : false),
            'Entry' => $this->roleService->isAllowEntry($status, isset($action->Entry) ? $action->Entry : false),
            'Post' => $this->roleService->isAllowPost($status, isset($action->Posted) ? $action->Posted : false),
            'ViewJournal' => $this->roleService->isPosted($status, 1),
            'ViewStock' => $this->roleService->isPosted($status, 1),
        ];
    }
    
    public function detailList($Oid = null)
    { 
        try {
            $user = Auth::user();

            $query = "SELECT p.*, t.*, p.Code AS CodeTransaction, s.Code AS StatusName
                FROM pospointofsale p
                LEFT OUTER JOIN traveltransaction t ON t.Oid = p.Oid
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                WHERE p.Oid='{$Oid}'";
            $traveltransaction = DB::select($query);
            $traveltransaction = $traveltransaction[0];

            $query = "SELECT d.*, i.Name AS ItemName, it.Code AS ItemTypeCode
                FROM trvtransactiondetail d
                LEFT OUTER JOIN traveltransaction t ON t.Oid = d.TravelTransaction
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN sysitemtype it ON it.Oid = i.ItemType
                WHERE t.Oid='{$traveltransaction->Oid}' AND i.Company = '{$user->Company}' AND it.Code IN ('Attraction','Restaurant')";
            $traveldetail1 = DB::select($query);

            $query = "SELECT d.*, i.Name AS ItemName, it.Code AS ItemTypeCode
                FROM trvtransactiondetail d
                LEFT OUTER JOIN traveltransaction t ON t.Oid = d.TravelTransaction
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN sysitemtype it ON it.Oid = i.ItemType
                WHERE t.Oid='{$traveltransaction->Oid}' AND i.Company = '{$user->Company}' AND it.Code IN ('Outbound','Hotel')";
            $traveldetail2 = DB::select($query);

            $query = "SELECT e.*
                FROM poseticket e
                LEFT OUTER JOIN mstitem i ON i.Oid = e.Item
                WHERE e.PointOfSale='{$traveltransaction->Oid}' AND e.Company = '{$user->Company}'";
            $etickets = DB::select($query);

            $result = [
                'TravelDetails1' => $traveldetail1,
                'TravelDetails2' => $traveldetail2,
                'Etickets' => $etickets
            ];

            $traveltransaction->Code = $traveltransaction->CodeTransaction;
            $data1 = collect($traveltransaction);

            $data = $data1->merge($result);
            return $data;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
