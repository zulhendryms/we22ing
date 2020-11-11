<?php

namespace App\AdminApi\POS\Controllers;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Security\Services\RoleModuleService;
 
class POSCompanyController extends Controller
{
    protected $roleService;

    public function __construct(
        RoleModuleService $roleService  
        )
    {
        $this->roleService = $roleService;
    }
    public function index(Request $request)
    {
        try {            
            $user = Auth::user();
            $role = $user->BusinessPartner ? $user->BusinessPartnerObj->BusinessPartnerGroupObj->BusinessPartnerRoleObj->Code : "Cash";
            if ($user->CompanyObj->BusinessPartner == $user->BusinessPartner) $data = '';
            elseif ($role == 'Customer' || $role == 'Agent') return null;
            elseif ($role == 'Supplier') return null;

            $result = [];
            $role = $this->roleService->list('POS');
            $action = $this->roleService->action('POS');

            $type = $request->input('type') ?: 'combo';
            $query = "SELECT p.Oid, p.Code, p.Date, p.Source, p.TotalAmount, c.Code AS CurrencyName, co.Name CompanyName, p.ContactName, s.Code StatusName 
                FROM pospointofsale p
                LEFT OUTER JOIn company co ON co.Oid = p.Company
                LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
                WHERE p.CompanySource = '{$user->Company}'";
            $data = DB::select($query);
            foreach ($data as $row) {
                $row->Role = [
                    'IsRead' => $role->IsRead,
                    'IsAdd' => 0,
                    'IsEdit' => 0,
                    'IsDelete' => 0,
                    'Cancel' => $row->StatusName == 'entry',
                    'Complete' => $row->StatusName == 'entry', //$this->roleService->isAllowComplete($data->StatusObj, $action->Complete),
                    'Entry' => $row->StatusName == 'paid', //$this->roleService->isAllowEntry($data->StatusObj, $action->Entry),
                    'Paid' => $row->StatusName == 'entry', //$this->roleService->isAllowPaid($data->StatusObj, $action->Paid),
                    'ViewJournal' => $row->StatusName == 'paid', //$this->roleService->isPosted($data->StatusObj, 1),
                    'ViewStock' => $row->StatusName == 'paid', //$this->roleService->isPosted($data->StatusObj, 1),
                    'Print' => $row->StatusName == 'paid' //$this->roleService->isPosted($data->StatusObj, 1),
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function show(Request $request, $oid) {
        $data = DB::select("SELECT * FROM pospointofsale WHERE Oid = '{$oid}'");
        if ($data) {
            $role = $this->roleService->list('POS');
            $action = $this->roleService->action('POS');
            
            $data = $data[0];
            $data->POSSessionObj = null;
            $tmp = DB::select("SELECT Oid,Code,Name FROM mstbusinesspartner WHERE Oid = '{$data->Supplier}'");
            $data->SupplierObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT Oid,Code,Name FROM mstbusinesspartner WHERE Oid = '{$data->Customer}'");
            $data->CustomerObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT Oid,Code,Name FROM syspointofsaletype WHERE Oid = '{$data->PointOfSaleType}'");
            $data->PointOfSaleTypeObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT Oid,UserName FROM user WHERE Oid = '{$data->User}'");
            $data->UserObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT Oid,Code,Name FROM mstcurrency WHERE Oid = '{$data->Currency}'");
            $data->CurrencyObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT Oid,Code,Name FROM sysstatus WHERE Oid = '{$data->Status}'");
            $data->StatusObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT * FROM mstpaymentmethod WHERE Oid = '{$data->PaymentMethod}'");
            $data->PaymentMethodObj = $tmp ? $tmp[0] : null;
            $tmp = DB::select("SELECT * FROM pospointofsalelog WHERE PointOfSale = '{$data->Oid}'");
            $data->Logs = $tmp ? $tmp[0] : null;

            $tmp = DB::select("SELECT t.*, i.Code AS ItemCode, i.Name AS ItemName FROM trvtransactiondetail t LEFT OUTER JOIN mstitem i ON i.Oid = t.Item WHERE t.TravelTransaction = '{$data->Oid}'");
            $data->TravelDetails = $tmp ? $tmp : null;
            $tmp = DB::select("SELECT t.*, i.Code AS ItemCode, i.Name AS ItemName FROM pospointofsaledetail t LEFT OUTER JOIN mstitem i ON i.Oid = t.Item WHERE t.PointOfSale = '{$data->Oid}'");
            $data->Details = $tmp ? $tmp : null;
            $tmp = DB::select("SELECT t.*, i.Code AS ItemCode, i.Name AS ItemName FROM poseticket t LEFT OUTER JOIN mstitem i ON i.Oid = t.Item WHERE t.PointOfSale = '{$data->Oid}'");
            $data->ETickets = $tmp ? $tmp : null;
            
            if ($data->TravelDetails) {
                foreach($data->TravelDetails as $row) {
                    $row->ItemObj = [
                        'Oid' => $row->Item,
                        'Code' => $row->ItemCode,
                        'Name' => $row->ItemName,
                    ];
                    unset($row->Item);
                    unset($row->ItemCode);
                    unset($row->ItemName);
                }
            }
            if ($data->Details) {
                foreach($data->Details as $row) {
                    $row->ItemObj = [
                        'Oid' => $row->Item,
                        'Code' => $row->ItemCode,
                        'Name' => $row->ItemName,
                    ];
                    unset($row->Item);
                    unset($row->ItemCode);
                    unset($row->ItemName);
                }

            }
            if ($data->ETickets) {
                foreach($data->ETickets as $row) {
                    $row->ItemObj = [
                        'Oid' => $row->Item,
                        'Code' => $row->ItemCode,
                        'Name' => $row->ItemName,
                    ];
                    unset($row->Item);
                    unset($row->ItemCode);
                    unset($row->ItemName);
                }
            }

            $data->Role = [
                'IsRead' => $role->IsRead,
                'IsAdd' => 0,
                'IsEdit' => 0,
                'IsDelete' => 0,
                'Cancel' => $data->StatusObj->Code == 'entry',
                'Complete' => $this->roleService->isAllowComplete($data->StatusObj, $action->Complete),
                'Entry' => $this->roleService->isAllowEntry($data->StatusObj, $action->Entry),
                'Paid' => $this->roleService->isAllowPaid($data->StatusObj, $action->Paid),
                'ViewJournal' => $this->roleService->isPosted($data->StatusObj, 1),
                'ViewStock' => $this->roleService->isPosted($data->StatusObj, 1),
                'Print' => $this->roleService->isPosted($data->StatusObj, 1),
            ];
        }
        return response()->json($data, Response::HTTP_OK);
    }
}
