<?php

namespace App\AdminApi\Trading\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;

use App\Core\Trading\Entities\PurchaseRequest;
use App\Core\Trading\Entities\PurchaseRequestDetail;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Trading\Entities\PurchaseOrderDetail;
use App\Core\Trading\Entities\PurchaseOrderLog;
use App\Core\Trading\Entities\PurchaseDelivery;
use App\Core\Trading\Entities\PurchaseDeliveryDetail;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Trading\Entities\PurchaseInvoiceDetail;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Pub\Controllers\PublicApprovalController;
use App\AdminApi\Pub\Controllers\PublicPostController;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\Core\Accounting\Services\PurchaseInvoiceService;
use App\Core\Accounting\Services\JournalService;
use App\AdminApi\Development\Controllers\CRUDLinkController;
use App\AdminApi\Development\Controllers\ServerCRUDController;

class PurchaseOrderController extends Controller
{
    protected $roleService;
    private $publicPostController;
    private $publicApprovalController;
    private $autoNumberService;
    private $crudController;
    protected $PurchaseInvoiceService;
    private $linkController;
    private $serverCRUD;
    public function __construct(
        RoleModuleService $roleService,
        HttpService $httpService,
        AutoNumberService $autoNumberService
    ) {
        $this->module = 'trdpurchaseorder';
        $this->roleService = $roleService;
        $this->autoNumberService = $autoNumberService;
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
        $this->publicApprovalController = new PublicApprovalController();
        $this->crudController = new CRUDDevelopmentController();
        $this->PurchaseInvoiceService = new PurchaseInvoiceService(new JournalService);
        $this->linkController = new CRUDLinkController();
        $this->serverCRUD = new ServerCRUDController();
    }

    public function config(Request $request)
    {
        try {
            $data = $this->crudController->config($this->module);
            $data[0]->topButton = [
                [
                    'name' => 'Add Purchase Request',
                    'icon' => 'PlusIcon',
                    'type' => 'open_form',
                    'url' => "purchaseorder/form?type=PurchaseRequest"
                ],
                // [
                // 'name' => 'Add Purchase Order',
                // 'icon' => 'PlusIcon',
                // 'type' => 'open_form',
                // 'url' => "purchaseorder/form?type=PurchaseOrder"
                // ]
            ];
            return response()->json($data, Response::HTTP_OK);
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
            $user = Auth::user();
            $data = DB::table('trdpurchaseorder as data');

            //SECURITY FILTER COMPANY
            if ($user->CompanyAccess) {
                $data = $data->leftJoin('company AS CompanySecurity', 'CompanySecurity.Oid', '=', 'data.Company');
                $tmp = json_decode($user->CompanyAccess);
                $data = $data->whereIn('CompanySecurity.Code', $tmp);
            }

            //SECURITY FILTER
            // if (!$user->IsAccessAllPurchaseRequest) $data = $data->where('Department.Purchaser',$user->Oid);
            if ($request->has('Company')) {
                if ($request->input('Company') != 'null') $data = $data->whereRaw("data.Company = '" . $request->input('Company') . "'");
            }
            if ($request->has('Account')) {
                if ($request->input('Account') != 'null') $data->whereRaw("data.Account = '" . $request->input('Account') . "'");
            }
            if ($request->has('DateFrom')) {
                if ($request->input('DateFrom') != 'null') $data = $data->whereRaw("data.Date >= '" . $request->input('DateFrom') . "'");
            }
            if ($request->has('DateTo')) {
                if ($request->input('DateTo') != 'null') $data = $data->whereRaw("data.Date <= '" . $request->input('DateTo') . "'");
            }

            //PRESEARCH
            $type = $request->has('Status') ? $request->input('Status') : 'All';
            if ($type == 'Entry') $data = $data->whereIn('Status.Code', ['entry', 'requested']);
            if ($type == 'Submit') $data = $data->whereIn('Status.Code', ['submit']);
            if ($type == 'Approved') $data = $data->whereIn('Status.Code', ['posted', 'complete']);
            if ($type == 'Complete') $data = $data->whereIn('Status.Code', ['complete']);
            if ($type == 'Posted') $data = $data->whereIn('Status.Code', ['posted']);
            if ($type == 'Rejected') $data = $data->whereIn('Status.Code', ['rejected', 'reject']);
            if ($type == 'Cancelled') $data = $data->whereIn('Status.Code', ['cancel']);

            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('PurchaseOrder'); //rolepermission
            foreach ($data->data as $row) {
                $tmp = PurchaseOrder::findOrFail($row->Oid);
                $row->Action = $this->action($tmp);
                $row->Role = $this->generateRole($row, $role);
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
            $data = DB::table($this->module . ' as data');
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
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        $data->Config = json_decode('{"main":{"name":"Main","icon":"SettingsIcon","editButton":"false","hideWhen":null,"fieldGroups":[{"name":"Main","icon":"SettingsIcon","hideWhen":null,"fields":[{"fieldToSave":"CompanyName","overrideLabel":"Company","type":"ViewOnly","initialDisable":true,"validationParams":"required","column":"1/3","default":null,"source":[],"store":"combosource/company","hiddenField":"CompanyName"},{"fieldToSave":"Type","overrideLabel":"Type","type":"ViewOnly","initialDisable":true,"column":"1/3","disabled":true,"default":null},{"fieldToSave":"StatusName","overrideLabel":"Status","type":"ViewOnly","initialDisable":true,"column":"1/3","disabled":true,"default":null,"source":[],"store":"master/status","hiddenField":"StatusName"},{"fieldToSave":"CurrencyName","overrideLabel":"Currency","type":"ViewOnly","initialDisable":true,"validationParams":"required","column":"1/2","default":null,"onChange":{"link":[{"link":"Rate","action":"request","get":"/currency/rate?currency={Oid}&date={Date}"}]},"source":[],"store":"master/currency","hiddenField":"CurrencyName"},{"fieldToSave":"Rate","overrideLabel":"Rate","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","default":null},{"fieldToSave":"PurchaserName","overrideLabel":"Purchaser","type":"ViewOnly","initialDisable":true,"column":"1/2","disabled":true,"default":null,"source":[],"store":"autocomplete/user","hiddenField":"PurchaserName"},{"fieldToSave":"DepartmentName","overrideLabel":"Department","type":"ViewOnly","initialDisable":true,"validationParams":"required","column":"1/2","default":null,"onChange":{"link":[{"link":"Purchaser"}]},"source":[],"store":"combosource/department","hiddenField":"DepartmentName"}]},{"name":"Purchase Order","icon":"SettingsIcon","hideWhen":{"link":"url","condition":["PurchaseRequest"]},"fields":[{"fieldToSave":"Code","overrideLabel":"Code","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null},{"fieldToSave":"Date","overrideLabel":"Date","type":"ViewOnly","initialDisable":true,"validationParams":"required","column":"1/2","default":null},{"fieldToSave":"DueDate","overrideLabel":"DueDate","type":"ViewOnly","initialDisable":true,"column":"1/2","disabled":true,"default":null},{"fieldToSave":"BusinessPartnerName","overrideLabel":"BusinessPartner","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"onChange":{"link":[{"link":"Currency"},{"link":"PaymentTerm"},{"link":"Employee"},{"link":"Rate","action":"request","get":"/currency/rate?currency={Currency}&date={Date}"}]},"source":[],"store":"autocomplete/businesspartner","params":{"type":"combo","role":"Supplier"},"hiddenField":"BusinessPartnerName"},{"fieldToSave":"PaymentTermName","overrideLabel":"PaymentTerm","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/paymentterm","hiddenField":"PaymentTermName"},{"fieldToSave":"DiscountAmount","overrideLabel":"DiscountAmount","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","default":null},{"fieldToSave":"TotalAmount","overrideLabel":"TotalAmount","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","disabled":true,"default":null}]},{"name":"Purchase Request","icon":"SettingsIcon","hideWhen":null,"fields":[{"fieldToSave":"RequestCode","overrideLabel":"RequestCode","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null},{"fieldToSave":"RequestDate","overrideLabel":"RequestDate","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null},{"fieldToSave":"RequestCodeReff","overrideLabel":"RequestCodeReff","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null},{"fieldToSave":"SupplierChosen","overrideLabel":"SupplierChosen","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[{"Oid":"1","Name":"Supplier 1"},{"Oid":"2","Name":"Supplier 2"},{"Oid":"3","Name":"Supplier 3"}]}]},{"name":"Requestor","icon":"SettingsIcon","hideWhen":{"link":"url","condition":["PurchaseOrder"]},"fields":[{"fieldToSave":"Requestor1Name","overrideLabel":"Requestor 1","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/employee","hiddenField":"Requestor1Name"},{"fieldToSave":"Requestor2Name","overrideLabel":"Requestor 2","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/employee","hiddenField":"Requestor2Name"},{"fieldToSave":"Requestor3Name","overrideLabel":"Acknowledge","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/employee","hiddenField":"Requestor3Name"},{"fieldToSave":"RequestorName","overrideLabel":"Requestor","type":"ViewOnly","initialDisable":true,"column":"1/2","disabled":true,"default":null,"source":[],"store":"autocomplete/user","hiddenField":"RequestorName"}]},{"name":"Supplier","icon":"SettingsIcon","hideWhen":{"link":"url","condition":["PurchaseOrder"]},"fields":[{"fieldToSave":"Supplier1Name","overrideLabel":"Supplier 1","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"onChange":{"link":[{"link":"Supplier1PaymentTerm"}]},"source":[],"store":"autocomplete/businesspartner","params":{"type":"combo","role":"Supplier"},"hiddenField":"Supplier1Name"},{"fieldToSave":"Supplier1PaymentTermName","overrideLabel":"Payment Term 1","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/paymentterm","hiddenField":"Supplier1PaymentTermName"},{"fieldToSave":"DiscountAmount1","overrideLabel":"DiscountAmount1","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","default":null},{"fieldToSave":"Supplier1Amount","overrideLabel":"Total Amount 1","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","disabled":true,"default":null},{"fieldToSave":"Supplier2Name","overrideLabel":"Supplier 2","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"onChange":{"link":[{"link":"Supplier2PaymentTerm"}]},"source":[],"store":"autocomplete/businesspartner","params":{"type":"combo","role":"Supplier"},"hiddenField":"Supplier2Name"},{"fieldToSave":"Supplier2PaymentTermName","overrideLabel":"Payment Term 2","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/paymentterm","hiddenField":"Supplier2PaymentTermName"},{"fieldToSave":"DiscountAmount2","overrideLabel":"DiscountAmount2","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","default":null},{"fieldToSave":"Supplier2Amount","overrideLabel":"Total Amount 2","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","disabled":true,"default":null},{"fieldToSave":"Supplier3Name","overrideLabel":"Supplier 3","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"onChange":{"link":[{"link":"Supplier3PaymentTerm"}]},"source":[],"store":"autocomplete/businesspartner","params":{"type":"combo","role":"Supplier"},"hiddenField":"Supplier3Name"},{"fieldToSave":"Supplier3PaymentTermName","overrideLabel":"Payment Term 3","type":"ViewOnly","initialDisable":true,"column":"1/2","default":null,"source":[],"store":"master/paymentterm","hiddenField":"Supplier3PaymentTermName"},{"fieldToSave":"DiscountAmount3","overrideLabel":"DiscountAmount3","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","default":null},{"fieldToSave":"Supplier3Amount","overrideLabel":"Total Amount 3","type":"ViewOnly","initialDisable":true,"validationParams":"money","column":"1/2","disabled":true,"default":null}]},{"name":"Description","icon":"SettingsIcon","hideWhen":null,"fields":[{"fieldToSave":"Note","overrideLabel":"Note","type":"ViewOnly","initialDisable":true,"default":null},{"fieldToSave":"Note2","overrideLabel":"Note2","type":"ViewOnly","initialDisable":true,"default":null},{"fieldToSave":"BillingAddress","overrideLabel":"BillingAddress","type":"ViewOnly","initialDisable":true,"default":null}]},{"name":"Information","icon":"SettingsIcon","hideWhen":null,"fields":[{"fieldToSave":"CreatedByName","type":"ViewOnly","overrideLabel":"Created By","column":"1/4"},{"fieldToSave":"CreatedAt","type":"ViewOnly","overrideLabel":"Created At","column":"1/4"},{"fieldToSave":"UpdatedByName","type":"ViewOnly","overrideLabel":"Updated By","column":"1/4"},{"fieldToSave":"UpdatedAt","type":"ViewOnly","overrideLabel":"Updated At","column":"1/4"}]},{"name":"Details","icon":"SettingsIcon","column":"1/1","fields":[{"name":"Details","icon":"SettingsIcon","type":"table","list":[{"field":"ItemName","name":"Item","width":150,"validationParams":"required"},{"field":"Note","name":"Note","width":150},{"field":"Quantity","name":"Qty","width":20,"editType":[{"disabled":false,"fieldToSave":"Quantity","type":"inputtext"}],"validationParams":"money"},{"field":"QuantityDelivered","name":"Q.Del","width":20,"validationParams":"money"},{"field":"QuantityInvoiced","name":"Q.Inv","width":20,"validationParams":"money"},{"field":"ItemUnitName","name":"ItemUnit","width":150,"validationParams":"required"},{"field":"Price1","name":"Price1","width":150,"validationParams":"money"},{"field":"Price2","name":"Price2","width":150,"validationParams":"money"},{"field":"Price3","name":"Price3","width":150,"validationParams":"money"},{"field":"CostCenterName","name":"CostCenter","width":150}]}]},{"name":"Approvals","icon":"SettingsIcon","column":"1/1","fields":[{"name":"Approvals","icon":"SettingsIcon","type":"table","list":[{"field":"Sequence","name":"Sequence","width":150,"validationParams":"money"},{"field":"UserName","name":"User","width":150},{"field":"Action","name":"Action","width":150},{"field":"ActionDate","name":"ActionDate","width":150},{"field":"Note","name":"Note","width":150}]}]},{"name":"Logs","icon":"SettingsIcon","column":"1/1","fields":[{"name":"Logs","icon":"SettingsIcon","type":"table","list":[{"field":"Date","name":"Date","width":150},{"field":"Type","name":"Type","width":100},{"field":"UserName","name":"User","width":150},{"field":"Note","name":"Note","width":150}]}]},{"name":"Comments","icon":"SettingsIcon","column":"1/1","fields":[{"name":"Comments","type":"table","icon":"CommentIcon","post":"publiccomment/create?Oid={Oid}&Type=PurchaseOrder","list":[{"field":"CreatedAt","name":"Created At","width":100},{"field":"UserName","width":100},{"field":"Message","width":300}]},{"name":"Comments","fieldToSave":"Comments","type":"comments","icon":"CommentIcon","post":"publiccomment/create?Oid={Oid}&Type=PurchaseOrder"}]},{"name":"Images","icon":"SettingsIcon","column":"1/1","fields":[{"name":"Images","type":"table","icon":"ImageIcon","post":"image?Oid={Oid}&Type=PurchaseOrder","delete":"image/{Oid}","list":[{"field":"CreatedAt","name":"Uploaded At","width":150},{"field":"Image","type":"image","width":300}]},{"name":"Images","fieldToSave":"Images","type":"gallery","icon":"ImageIcon","post":"image?Oid={Oid}&Type=PurchaseOrder","delete":"image/{Oid}"}]},{"name":"Files","icon":"SettingsIcon","column":"1/1","fields":[{"name":"Files","type":"table","icon":"FilePlusIcon","post":"file/upload?Oid={Oid}&Type=PurchaseOrder","delete":"file/{Oid}","list":[{"field":"CreatedAt","name":"Uploaded At","width":100},{"field":"FileName","type":"url","width":500}]},{"name":"Files","fieldToSave":"Files","type":"files","icon":"FilePlusIcon","post":"file/upload?Oid={Oid}&Type=PurchaseOrder","delete":"file/{Oid}"}]}]}}');
        // $data->Config = $this->crudController->vueview("PurchaseOrder");
        return $data;
    }

    public function show(PurchaseOrder $data)
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
            $data = null;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                //PO CODE RANDOM, PR CODE GENERATE
                if (!$Oid) $data->Code = now()->format('ymdHis') . '-' . str_random(3);
                if ($data->RequestCode == '<<Auto>>') $data->RequestCode = $this->autoNumberService->generate($data, 'trdpurchaseorder', 'RequestCode');

                //PAYMENT TERM
                if (isset($data->PaymentTerm)) $data->DueDate = addPaymentTermDueDate($data->Date, $data->PaymentTerm);

                //PR CHOSEN
                if (isset($data->SupplierChosen)) {
                    $data->BusinessPartner = $data->{'Supplier' . $data->SupplierChosen};
                    $data->PaymentTerm = $data->{'Supplier' . $data->SupplierChosen . 'PaymentTerm'};
                    $data->DiscountAmount = $data->{'DiscountAmount' . $data->SupplierChosen};
                }
                $data->save();

                //PUBLIC POST & APPROVAL
                $this->publicPostController->sync($data, 'PurchaseOrder');
                if (isset($data->Department) && in_array($data->StatusObj->Code, ['entry']))
                    $this->publicApprovalController->formCreate($data, 'PurchaseOrder');

                //DETAIL & TOTAL
                if (isset($data->Details)) {
                    foreach ($data->Details as $detail) {
                        if (isset($data->SupplierChosen)) $detail->Price = $detail->{'Price' . $data->SupplierChosen};
                        $detail = $this->crudController->saveTotal($detail);
                    }
                }

                // $data = $this->crudController->saveTotal($data);
                $this->calculateTotalAmount($data);
                // if (in_array($data->StatusObj->Code,['posted','complete'])) $this->linkController->PurchaseOrderCalculateOutstanding("'".$data->Oid."'");
                if (!$data) throw new \Exception('Data is failed to be saved');
            });
            $role = $this->roleService->list('PurchaseOrder'); //rolepermission
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

    public function destroy(PurchaseOrder $data)
    {
        try {
            $data = $this->crudController->delete($this->module, $data);
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

    public function presearch(Request $request)
    {
        return [
            [
                "fieldToSave" => "Status",
                "type" => "combobox",
                'hiddenField' => 'StatusName',
                "column" => "1/5",
                "source" => [
                    [
                        "Oid" => "All",
                        "Name" => "All"
                    ],
                    [
                        "Oid" => "Entry",
                        "Name" => "Entry"
                    ],
                    [
                        "Oid" => "Submit",
                        "Name" => "Submit"
                    ],
                    [
                        "Oid" => "Approved",
                        "Name" => "Approved"
                    ],
                    [
                        "Oid" => "Cancelled",
                        "Name" => "Cancelled"
                    ],
                    [
                        "Oid" => "Rejected",
                        "Name" => "Rejected"
                    ],
                    [
                        "Oid" => "Posted",
                        "Name" => "Posted Only"
                    ],
                    [
                        "Oid" => "Complete",
                        "Name" => "Complete Only"
                    ],
                ],
                "store" => "",
                "defaultValue" => "All"
            ],
            [
                'fieldToSave' => 'Company',
                'type' => 'combobox',
                'column' => '1/5',
                'validationParams' => 'required',
                'hiddenField' => 'CompanyName',
                'source' => 'company',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'combosource/company',
                    'params' => null
                ]
            ],
            [
                'fieldToSave' => 'DateFrom',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateTo',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                "type" => "action",
                "column" => "1/5"
            ]
        ];
    }

    public function action(PurchaseOrder $data)
    {
        $url = 'purchaseorder';
        $actionOpen = [
            'name' => 'Open',
            'icon' => 'ViewIcon',
            'type' => 'open_url',
            'url' => $url . '/form?item={Oid}&type=' . ($data->Type ?: 'PurchaseRequest'),
        ];
        $actionEntry = [
            'name' => 'Change to ENTRY',
            'icon' => 'UnlockIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/unpost',
        ];
        $actionPosted = [
            'name' => 'Change to POSTED',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/post',
        ];
        $actionCancelled = [
            'name' => 'Change to Cancelled',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancelled',
        ];
        $actionConvertToPurchaseDelivery = [
            'name' => 'Convert to PurchaseDelivery',
            'icon' => 'ZapIcon',
            'type' => 'global_form',
            'showModal' => false,
            'post' => $url . '/{Oid}/convert1',
            'afterRequest' => "apply",
            'form' => [
                [
                    'fieldToSave' => 'CodeReff',
                    'overrideLabel' => 'Code Reference',
                    'type' => 'inputtext'
                ],
                [
                    'fieldToSave' => 'Date',
                    'overrideLabel' => 'Date',
                    'type' => 'inputdate'
                ],
            ],
        ];
        $actionConvertToPurchaseInvoice = [
            'name' => 'Convert to PurchaseInvoice',
            'icon' => 'ZapIcon',
            'type' => 'global_form',
            'post' => $url . '/{Oid}/convert2',
            'afterRequest' => "apply",
            'form' => [
                [
                    'fieldToSave' => 'Code',
                    'overrideLabel' => 'Code',
                    'type' => 'inputtext'
                ],
                [
                    'fieldToSave' => 'CodeReff',
                    'overrideLabel' => 'Code Reference',
                    'type' => 'inputtext'
                ],
                [
                    'fieldToSave' => 'Date',
                    'overrideLabel' => 'Date',
                    'type' => 'inputdate'
                ],
            ],
        ];
        $actionPrint = [
            'name' => 'Print',
            'icon' => 'PrinterIcon',
            'type' => 'global_form',
            'hide' => false,
            'open_report' => true,
            'post' => 'prereport/' . $url . '?oid={Oid}',
            'afterRequest' => 'init',
            "form" => [
                [
                    'fieldToSave' => "Report",
                    'overrideLabel' => "Report Format",
                    'type' => "combobox",
                    'hiddenField' => 'ReportName',
                    'column' => "1/2",
                    'source' => [],
                    'store' => "",
                    'source' => [
                        ['Oid' => 'purchaseorder', 'Name' => 'Purchase Order (Standard)'],
                        ['Oid' => 'purchaserequest', 'Name' => 'Purchase Request'],
                    ]
                ],
                [
                    'fieldToSave' => "PaperSize",
                    'overrideLabel' => "Paper Size",
                    'type' => "combobox",
                    'hiddenField' => 'ReportName',
                    'column' => "1/2",
                    'source' => [],
                    'store' => "",
                    'default' => "A4",
                    'source' => [
                        ['Oid' => 'A4', 'Name' => 'A4'],
                        ['Oid' => 'Half', 'Name' => 'Half Continuous'],
                        ['Oid' => 'Full', 'Name' => 'Full Continuous'],
                    ]
                ]
            ]
        ];
        $actionDelete = [
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => $url . '/{Oid}'
        ];
        $actionEditCodeNote = [
            "name" => "Edit Code & Note",
            "icon" => "ActivityIcon",
            "type" => "global_form",
            "showModal" => false,
            "get" => "purchaseorder/{Oid}",
            "post" => "purchaseorder/{Oid}",
            "afterRequest" => "apply",
            "form" => [
                [
                    'fieldToSave' => "Code",
                    'type' => "inputtext",
                    'validationParams' => 'required'
                ],
                [
                    'fieldToSave' => "RequestCodeReff",
                    'type' => "inputtext",
                ],
                [
                    'fieldToSave' => "Note",
                    'type' => "inputarea"
                ],
            ]
        ];
        $openPurchaseInvoice = [
            'name' => 'Related Invoice',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'purchaseorder/relatedpurchaseinvoice/{Oid}'
        ];
        $openPurchaseDelivery = [
            'name' => 'Related Delivery',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'purchaseorder/relatedpurchasedelivery/{Oid}'
        ];
        $actionSubmit = $this->publicApprovalController->formAction($data, 'PurchaseOrder', 'submit');
        $actionRequest = $this->publicApprovalController->formAction($data, 'PurchaseOrder', 'request');
        $actionCancel = [
            'name' => 'Cancel',
            'icon' => 'ArrowUpCircleIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancel',
            'afterRequest' => 'apply'
        ];
        $actionOpenView = [
            'name' => 'Open Purchase Order 2',
            'icon' => 'ListIcon',
            'type' => 'open_view2',
            'get' => 'purchaseorder/{Oid}'    
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                $return[] = $actionOpen;
                $return[] = $actionOpenView;
                // $return[] = $actionDelete;
                break;
            case "request":
                $return[] = $actionEntry;
                $return[] = $actionSubmit;
                $return[] = $actionOpenView;
                break;
            case "entry":
                $return[] = $actionRequest;
                $return[] = $actionSubmit;
                // $return[] = $actionDelete;
                $return[] = $actionCancel;
                $return[] = $actionOpenView;
                break;
            case "submit":
                $return = $this->publicApprovalController->formAction($data, 'PurchaseOrder', 'approval');
                $return[] = $actionEntry;
                // $return[] = $printprereportpr;
                $return[] = $actionPrint;
                break;
            case "posted":
                $return[] = $actionEditCodeNote;
                $return[] = $actionEntry;
                $return[] = $actionConvertToPurchaseDelivery;
                $return[] = $actionConvertToPurchaseInvoice;
                $return[] = $actionPrint;
                $return[] = $actionOpenView;
                $return[] = $openPurchaseInvoice;
                $return[] = $openPurchaseDelivery;
                break;
            case "complete":
                $return[] = $actionEditCodeNote;
                $return[] = $actionPrint;
                $return[] = $actionOpenView;
                $return[] = $openPurchaseInvoice;
                $return[] = $openPurchaseDelivery;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }

    public function relatedPurchaseInvoice(PurchaseOrder $data) {
        $tmp = PurchaseOrderDetail::where('PurchaseOrder', $data)->pluck('Oid');
        $tmp = PurchaseInvoiceDetail::whereNotNull('GCRecord')
            ->whereIn('PurchaseOrderDetail', $tmp)->pluck('PurchaseInvoice');
        $data = PurchaseInvoice::whereIn('Oid',$tmp)->get();
        $results = [];
        foreach($data as $row) {
            $result[] = [
                'Oid' => $data->Oid,
                'Code' => $data->Code,
                'Date' => $data->Date,
                'BusinessPartner' => $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null,
                'Status' => $data->StatusObj ? $data->StatusObj->Code : null,
                'Action' => [
                    'name' => 'Open Purchase Invoice',
                    'icon' => 'ListIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=PurchaseInvoice",
                    'get' => 'purchaseinvoice/{Oid}'    
                ]
            ];
        }
        return $results;
    }

    public function relatedPurchaseDelivery(PurchaseOrder $data) {
        $tmp = PurchaseOrderDetail::where('PurchaseOrder', $data)->pluck('Oid');
        $tmp = PurchaseDeliveryDetail::whereNotNull('GCRecord')
            ->whereIn('PurchaseOrderDetail', $tmp)->pluck('PurchaseDelivery');
        $data = PurchaseDelivery::whereIn('Oid',$tmp)->get();
        $results = [];
        foreach($data as $row) {
            $result[] = [
                'Oid' => $data->Oid,
                'Code' => $data->Code,
                'Date' => $data->Date,
                'BusinessPartner' => $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null,
                'Status' => $data->StatusObj ? $data->StatusObj->Code : null,
                'Action' => [
                    'name' => 'Open Purchase Delivery',
                    'icon' => 'ListIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=PurchaseDelivery",
                    'get' => 'purchasedelivery/{Oid}'    
                ]
            ];
        }
        return $results;
    }

    private function calculateTotalAmount(PurchaseOrder $data)
    {
        $totalAmount = 0;
        $totalAmount1 = 0;
        $totalAmount2 = 0;
        $totalAmount3 = 0;
        foreach ($data->Details as $row) {
            if (isset($data->SupplierChosen)) $row->Price = $row->{'Price' . $data->SupplierChosen};
            $totalAmount += ($row->Quantity ?: 0) * ($row->Price ?: 0);
            $totalAmount1 += ($row->Quantity ?: 0) * ($row->Price1 ?: 0);
            $totalAmount2 += ($row->Quantity ?: 0) * ($row->Price2 ?: 0);
            $totalAmount3 += ($row->Quantity ?: 0) * ($row->Price3 ?: 0);
            logger($row->Oid . ' ' . $totalAmount);
        }
        $data->SubtotalAmount = $totalAmount;
        $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountAmount;
        $data->Supplier1Amount = $totalAmount1 - $data->DiscountAmount1;
        $data->Supplier2Amount = $totalAmount2 - $data->DiscountAmount2;
        $data->Supplier3Amount = $totalAmount3 - $data->DiscountAmount3;
        $data->TotalAmountWording = convert_number_to_words($data->TotalAmount);
        $data->save();
    }

    public function statusUnpost(PurchaseOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Entry')->first()->Oid;
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusPost(PurchaseOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Posted')->first()->Oid;
                $data->Type = 'PurchaseOrder';
                $data->Code = $this->autoNumberService->generate($data, 'trdpurchaseorder');
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function cancelled(PurchaseOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::cancelled()->first()->Oid;
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function convertToPurchaseDelivery(Request $request, $source)
    {
        try {
            //validation
            $tmp = PurchaseOrderDetail::where('PurchaseOrder', $source)->pluck('Oid');
            $check = PurchaseDeliveryDetail::whereIn('PurchaseOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Purchase Order has already Purchase Deliver");
            $check = PurchaseInvoiceDetail::whereIn('PurchaseOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Purchase Order has already Purchase Invoice");
            $check = CashBankDetail::whereIn('PurchaseOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Purchase Order has already Cash Bank");

            $data = new PurchaseDelivery();
            DB::transaction(function () use ($request, $source, &$data) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

                // get data
                $source = PurchaseOrder::with('Details')->where('Oid', $source)->first();
                $businessPartner = BusinessPartner::with('BusinessPartnerAccountGroupObj')->where('Oid', $source->BusinessPartner)->first();

                //important field
                $data->PurchaseOrder = $source->Oid;
                $data->Code = '<<Auto>>';
                $data->Date = $request->Date ?: Carbon::now();
                $data->CodeReff = $request->CodeReff ?: $source->CodeReff;
                $data->Account = $source->Account ?: $businessPartner->BusinessPartnerAccountGroupObj->PurchaseDelivery;
                $data->Status = Status::entry()->first()->Oid;

                //samefield
                $convertField = ['Company', 'BusinessPartner', 'Currency', 'Quantity', 'Employee', 'Warehouse', 'DiscountAmount', 'SubtotalAmount', 'TotalAmount', 'Rate', 'Note'];
                foreach ($convertField as $f) if (isset($source->{$f})) $data->{$f} = $source->{$f};
                $data->save();

                //autonumber
                $data->Code = $this->autoNumberService->generate($data, 'trdpurchasedelivery');

                $data->IsConvertPD = true;
                $data->save();

                foreach ($data->Details as $row) {
                    $detail = new PurchaseDeliveryDetail();
                    $detail->Company = $source->Company;
                    $detail->PurchaseDelivery = $data->Oid;
                    $detail->Reference = 'PO: ' . $row->ParentCode;
                    $detail->PurchaseOrderDetail = $row->Oid;
                    $detail->save();

                    //same field
                    $convertField = ['Sequence', 'Item', 'Price', 'Quantity', 'Note', 'TotalAmount', 'CostCenter'];
                    foreach ($convertField as $f) if (isset($row->{$f})) $detail->{$f} = $row->{$f};
                    $detail->save();

                    $row->QuantityDelivered = $row->Quantity;
                    $row->save();
                }
                //set purchaseorder
                $source->IsConvertPD = true;
                $source->Status = Status::where('Code', 'complete')->first()->Oid;
                $source->save();
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

    public function convertToPurchaseInvoice(Request $request, $source)
    {
        try {
            //validation
            $tmp = PurchaseOrderDetail::where('PurchaseOrder', $source)->pluck('Oid');
            $check = PurchaseDeliveryDetail::whereIn('PurchaseOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Purchase Order has already Purchase Deliver");
            $check = PurchaseInvoiceDetail::whereIn('PurchaseOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Purchase Order has already Purchase Invoice");
            $check = CashBankDetail::whereIn('PurchaseOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Purchase Order has already Cash Bank");
            $data = new PurchaseInvoice();

            DB::transaction(function () use ($request, $source, &$data) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

                // get data
                $source = PurchaseOrder::with('Details')->where('Oid', $source)->first();
                $businessPartner = BusinessPartner::with('BusinessPartnerAccountGroupObj')->where('Oid', $source->BusinessPartner)->first();

                //important field
                $data->PurchaseOrder = $source->Oid;
                $data->Code = $request->Code ? $request->Code : '<<Auto>>';
                $data->Date = $request->Date ?: Carbon::now();
                $data->CodeReff = $request->CodeReff ?: $source->Code;
                $data->Account = $source->Account ?: $businessPartner->BusinessPartnerAccountGroupObj->PurchaseInvoice;
                if (isset($source->PaymentTerm)) $data->DueDate = addPaymentTermDueDate($source->Date, $source->PaymentTerm);

                //samefield
                $convertField = ['Company', 'BusinessPartner', 'Currency', 'Quantity', 'Employee', 'Warehouse', 'PaymentTerm', 'DiscountAmount', 'SubtotalAmount', 'TotalAmount', 'Rate', 'Note'];
                foreach ($convertField as $f) if (isset($source->{$f})) $data->{$f} = $source->{$f};
                $data->save();
                $this->PurchaseInvoiceService->post($data->Oid);

                //autonumber
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'trdpurchaseinvoice');

                $totalAmount = 0;
                foreach ($source->Details as $row) {
                    $detail = new PurchaseInvoiceDetail();
                    $detail->Company = $source->Company;
                    $detail->PurchaseInvoice = $data->Oid;
                    $detail->Reference = 'PO: ' . $source->Code;
                    $detail->PurchaseOrderDetail = $row->Oid;

                    //same field
                    $convertField = ['Sequence', 'Item', 'Price', 'Quantity', 'Note', 'TotalAmount', 'CostCenter', 'SubtotalAmount'];
                    foreach ($convertField as $f) if (isset($row->{$f})) $detail->{$f} = $row->{$f};
                    $detail->save();

                    $totalAmount = $totalAmount + ($row->Quantity * $row->Price);

                    //update quantity
                    $row->QuantityInvoiced = $row->Quantity;
                    $row->save();
                }
                //update total
                $data->SubtotalAmount = $totalAmount;
                $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountAmount;
                $data->save();

                //set purchaseorder
                $source->IsConvertPI = true;
                $source->Status = Status::where('Code', 'complete')->first()->Oid;
                $source->save();

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

    private function generateRole($data, $role = null, $action = null)
    {
        if ($data instanceof PurchaseOrder) $status = $data->StatusObj->Code;
        else $status = Status::entry();
        if (!$role) $role = $this->roleService->list('PurchaseOrder');
        if (!$action) $action = $this->roleService->action('PurchaseOrder');
        if ($role) {
            return [
                'IsRead' => isset($role->IsRead) ? $role->IsRead : false,
                'IsAdd' => isset($role->IsAdd) ? $role->IsAdd : false,
                'IsEdit' => $this->roleService->isAllowDelete($status, isset($role->IsEdit) ? $role->IsEdit : false),
                'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
                'Cancel' => $this->roleService->isAllowCancel($status, $action->Cancel),
                'Entry' => $this->roleService->isAllowEntry($status, $action->Entry),
                'Post' => $this->roleService->isAllowPost($status, $action->Posted),
                'ViewJournal' => $this->roleService->isPosted($status, 1),
                'ViewStock' => $this->roleService->isPosted($status, 1),
                'Print' => $this->roleService->isPosted($status, 1),
            ];
        }
    }

    public function statusEntry(PurchaseOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'entry')->first()->Oid;
                $data->save();

                $this->publicApprovalController->formApprovalReset($data);
                $this->publicPostController->sync($data, 'PurchaseOrder');

                $user = Auth::user();
                $detail = new PurchaseOrderLog();
                $detail->Company = $data->Company;
                $detail->PurchaseOrder = $data->Oid;
                $detail->Date = now()->addHours(company_timezone())->toDateTimeString();
                $detail->User = $user->Oid;
                $detail->Type = 'Entry';
                $detail->NextUser = null;
                $detail->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusCancel(PurchaseOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'cancel')->first()->Oid;
                $data->save();
                $this->publicPostController->sync($data, 'PurchaseOrder');

                $user = Auth::user();
                $detail = new PurchaseOrderLog();
                $detail->Company = $data->Company;
                $detail->PurchaseOrder = $data->Oid;
                $detail->Date = now()->addHours(company_timezone())->toDateTimeString();
                $detail->User = $user->Oid;
                $detail->Type = 'Canceled';
                $detail->NextUser = null;
                $detail->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listPurchaseHistory(Request $request)
    {
        try {

            $user = Auth::user();
            $item = $request->input('Item');
            $company = $request->input('Company');
            $costcenter = $request->input('CostCenter');
            $dataLastPrice = DB::table('trdpurchaseorderdetail as data');
            $dataLastPrice = $dataLastPrice->leftJoin('trdpurchaseorder AS po', 'po.Oid', '=', 'data.PurchaseOrder');
            $dataLastPrice = $dataLastPrice->leftJoin('mstbusinesspartner AS bp1', 'bp1.Oid', '=', 'po.Supplier1');
            $dataLastPrice = $dataLastPrice->leftJoin('mstbusinesspartner AS bp2', 'bp2.Oid', '=', 'po.Supplier2');
            $dataLastPrice = $dataLastPrice->leftJoin('mstbusinesspartner AS bp3', 'bp3.Oid', '=', 'po.Supplier3');
            $dataLastPrice = $dataLastPrice->leftJoin('mstcostcenter AS cc', 'cc.Oid', '=', 'data.CostCenter');
            $dataLastPrice = $dataLastPrice->leftJoin('company AS co', 'co.Oid', '=', 'po.Company');
            $dataLastPrice = $dataLastPrice->where('data.Company', $company);
            $dataLastPrice = $dataLastPrice->where('data.Item', $item);
            $dataLastPrice = $dataLastPrice->orderBy('po.Date');
            $dataLastPrice = $dataLastPrice->select('data.Oid', 'po.Code', 'po.Date', 'cc.Name AS CostCenter', 'data.Quantity', 'bp1.Name AS Supplier1', 'data.Price1', 'bp2.Name AS Supplier2', 'data.Price2', 'bp3.Name AS Supplier3', 'data.Price3')->limit(20)->get();

            $dataBySupplier = DB::table('trdpurchaseorderdetail as data');
            $dataBySupplier = $dataBySupplier->leftJoin('trdpurchaseorder AS po', 'po.Oid', '=', 'data.PurchaseOrder');
            $dataBySupplier = $dataBySupplier->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'po.BusinessPartner');
            $dataBySupplier = $dataBySupplier->leftJoin('mstcostcenter AS cc', 'cc.Oid', '=', 'data.CostCenter');
            $dataBySupplier = $dataBySupplier->leftJoin('company AS co', 'co.Oid', '=', 'po.Company');
            $dataBySupplier = $dataBySupplier->where('data.Company', $company);
            $dataBySupplier = $dataBySupplier->where('data.Item', $item);
            $dataBySupplier = $dataBySupplier->orderBy('po.Date');
            $dataBySupplier = $dataBySupplier->select('data.Oid', 'po.Code', 'po.Date', 'cc.Name AS CostCenter', 'data.Quantity', 'bp.Name AS BusinessPartner', 'data.Price')->limit(20)->get();

            $dataByCostCenter = DB::table('trdpurchaseorderdetail as data');
            $dataByCostCenter = $dataByCostCenter->leftJoin('trdpurchaseorder AS po', 'po.Oid', '=', 'data.PurchaseOrder');
            $dataByCostCenter = $dataByCostCenter->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'po.BusinessPartner');
            $dataByCostCenter = $dataByCostCenter->leftJoin('mstcostcenter AS cc', 'cc.Oid', '=', 'data.CostCenter');
            $dataByCostCenter = $dataByCostCenter->leftJoin('company AS co', 'co.Oid', '=', 'po.Company');
            $dataByCostCenter = $dataByCostCenter->where('data.Company', $company);
            $dataByCostCenter = $dataByCostCenter->where('data.Item', $item);
            $dataByCostCenter = $dataByCostCenter->where('data.CostCenter', $costcenter);
            $dataByCostCenter = $dataByCostCenter->orderBy('po.Date');
            $dataByCostCenter = $dataByCostCenter->select('data.Oid', 'po.Code', 'po.Date', 'cc.Oid AS CC', 'cc.Name AS CostCenter', 'data.Quantity', 'bp.Name AS BusinessPartner', 'data.Price', 'data.Note')->limit(20)->get();

            return [
                'LastPrice' => $dataLastPrice,
                'LastPricebySupplier' => $dataBySupplier,
                'byCostCenter' => $dataByCostCenter,
            ];
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function reorderPurchaseOrderDetail(Request $request) {
            $query = "SELECT po.Oid Parent, pod.Oid, i.Name, IFNULL(pod.Sequence,-1) AS Sequence 
            from trdpurchaseorder po
              LEFT OUTER JOIN trdpurchaseorderdetail pod ON po.Oid = pod.PurchaseOrder
              LEFT OUTER JOIN mstitem i ON pod.Item = i.Oid
              WHERE po.Oid = '{$request->input('Oid')}'
              ORDER BY pod.Sequence";
        return DB::select($query);
    }

    public function reorderPurchaseOrderDetailUpdate(Request $request) {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        foreach ($request as $row) {
            $query = "UPDATE trdpurchaseorderdetail pod SET pod.Sequence = '{$row->Sequence}' WHERE pod.Oid = '{$row->Oid}'";
            DB::update($query);
        }
        return $request;
    }  
}
