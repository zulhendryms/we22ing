<?php
namespace App\AdminApi\Accounting\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Base\Services\HttpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\CashBankSubmission;
use App\Core\Accounting\Entities\CashBankSubmissionDetail;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

use App\AdminApi\Pub\Controllers\PublicApprovalController;
use App\AdminApi\Pub\Controllers\PublicPostController;
use App\Core\Master\Entities\Company;
use App\Core\Internal\Entities\Status;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Trading\Entities\PurchaseInvoice;

class CashBankSubmissionController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    private $publicPostController;
    private $publicApprovalController;
    public function __construct(
        HttpService $httpService,
        RoleModuleService $roleService
    ) {
        $this->module = 'acccashbanksubmission';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
        $this->publicApprovalController = new PublicApprovalController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('CashBankSubmission'); //rolepermission
            foreach ($data->data as $row) {
                $tmp = CashBankSubmission::findOrFail($row->Oid);
                $row->Action = $this->action($tmp);
                $row->Role = $this->roleService->generateActionMaster2($row, $role);
            }
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function showSub($Oid)
    {
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            $data->Action = $this->action($data);
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }


    public function show(CashBankSubmission $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            if (!$Oid) {
                $req = requestToObject($request);
                $status = Status::whereIn('Code',['entry','submit'])->pluck('Oid');
                $check = CashBankSubmission::whereNull('GCRecord')
                    ->where('Company',$req->Company)
                    ->whereIn('Status', $status)
                    ->first();
                if ($check) throw new \Exception('There is a submission previously, pls kindly edit/add '.$check->Code);
            }

            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                if (!$Oid) {
                    $data->Code = now()->format('ymdHis') . '-' . str_random(3);                    
                    $query = "SELECT bp.Oid AS BusinessPartner, c.Oid AS Currency, 
                        CONCAT(IFNULL(bp.Name,''),' - ',IFNULL(c.Code,''),' (',IFNULL(pt.Name,''),')') AS Description,
                        DATE_FORMAT(Date, '%Y-%m') AS Period,
                        SUM(IFNULL(TotalAmount,0) - IFNULL(PaidAmount,0)) AS InvoiceAmount
                        FROM trdpurchaseinvoice p
                        LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status  
                        LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
                        LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
                        LEFT OUTER JOIN mstpaymentterm pt ON pt.Oid = bp.PaymentTerm
                        WHERE IFNULL(TotalAmount,0) - IFNULL(PaidAmount,0) > 0 
                        AND p.Company = '{$data->Company}' AND s.Code = 'posted'
                        GROUP BY bp.Oid,c.Oid,bp.Name,c.Code, DATE_FORMAT(p.Date, '%Y-%m')";
                    $invoices = DB::select($query);
                    // AND s.Code IN ('posted','complete')

                    foreach ($invoices as $row) {
                        $detail = new CashBankSubmissionDetail();
                        $detail->Company = $data->Company;
                        $detail->CashBankSubmission = $data->Oid;
                        $detail->BusinessPartner = $row->BusinessPartner;
                        $detail->Period = $row->Period;
                        $detail->Description = $row->Description;
                        $detail->InvoiceAmount = $row->InvoiceAmount;
                        $detail->Currency = $row->Currency;
                        $detail->save();
                    }

                    //PUBLIC POST & APPROVAL
                    $this->publicPostController->sync($data, 'CashBankSubmission');
                    if (isset($data->Department) && in_array($data->StatusObj->Code, ['entry']))
                        $this->publicApprovalController->formCreate($data, 'CashBankSubmission');
                }
            });
            $role = $this->roleService->list('CashBankSubmission'); //rolepermission
            $data = $this->showSub($data->Oid);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy($data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function action(CashBankSubmission $data)
    {
        $actionPrintprereport = [
            'name' => 'Print CashBank Submission',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => false,
            'get' => 'prereport/cashbanksubmission?oid={Oid}&report=cashbanksubmission',
            'afterRequest' => 'init'
        ];
        $actionSubmit = $this->publicApprovalController->formAction($data, 'CashBankSubmission', 'submit');
        $actionRequest = $this->publicApprovalController->formAction($data, 'CashBankSubmission', 'request');
        $actionDelete = [
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => 'cashbanksubmission/{Oid}'
        ];
        $actionEntry = $this->publicApprovalController->formAction($data, 'CashBankSubmission', 'entry');
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "request":
                $return[] = $actionEntry;
                $return[] = $actionSubmit;
                break;
            case "entry":
                $return[] = $actionRequest;
                $return[] = $actionSubmit;
                $return[] = $actionDelete;
            break;
            case "posted":
                $return[] = $actionPrintprereport;
                $return[] = $actionEntry;
            break;
            case "submit":
                $return[] = $actionPrintprereport;
                $return = $this->publicApprovalController->formAction($data, 'CashBankSubmission', 'approval');
                $return[] = $actionEntry;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }
}

// SELECT co.Code,
// CONCAT(IFNULL(bp.Name,''),' - ',IFNULL(c.Code,''),' (',IFNULL(pt.Name,''),')') AS Description,
// DATE_FORMAT(Date, '%Y-%m') AS Period, p.Code, p.Date,
// TotalAmount  AS InvoiceAmount,
//   PaidAmount,
//   IFNULL(TotalAmount,0) - IFNULL(PaidAmount,0) AS BalanceAmount
// FROM trdpurchaseinvoice p
// LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status  
// LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
// LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
// LEFT OUTER JOIN mstpaymentterm pt ON pt.Oid = bp.PaymentTerm
// LEFT OUTER JOIN company co ON co.Oid = p.Company
// WHERE IFNULL(TotalAmount,0) - IFNULL(PaidAmount,0) > 0 
// ORDER BY co.Code, bp.Name,c.Code, DATE_FORMAT(p.Date, '%Y-%m');