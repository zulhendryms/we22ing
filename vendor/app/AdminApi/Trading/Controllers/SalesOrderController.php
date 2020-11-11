<?php

namespace App\AdminApi\Trading\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\SalesOrder;
use App\Core\Trading\Entities\SalesOrderDetail;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Internal\Entities\Status;
use App\Core\Trading\Entities\SalesDelivery;
use App\Core\Trading\Entities\SalesDeliveryDetail;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Trading\Entities\SalesInvoiceDetail;
use App\Core\Master\Entities\Department;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\AdminApi\Pub\Controllers\PublicApprovalController;
use App\AdminApi\Pub\Controllers\PublicPostController;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;
use App\Core\Base\Services\HttpService;
use App\Core\Accounting\Services\SalesInvoiceService;
use App\Core\Accounting\Services\JournalService;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class SalesOrderController extends Controller
{
    protected $roleService;
    private $publicPostController;
    private $publicApprovalController;
    private $autoNumberService;
    private $module;
    private $crudController;
    protected $SalesInvoiceService;
    public function __construct(
        RoleModuleService $roleService,
        AutoNumberService $autoNumberService
    ) {
        $this->roleService = $roleService;
        $this->autoNumberService = $autoNumberService;
        $this->publicPostController = new PublicPostController(new RoleModuleService(new HttpService), new HttpService);
        $this->publicApprovalController = new PublicApprovalController();
        $this->module = 'trdsalesorder';
        $this->crudController = new CRUDDevelopmentController();
        $this->SalesInvoiceService = new SalesInvoiceService(new JournalService);
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
            errjson($e);
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

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module . ' as data');

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
            $role = $this->roleService->list('SalesOrder'); //rolepermission
            foreach ($data->data as $row) {
                $tmp = SalesOrder::findOrFail($row->Oid);
                $row->Action = $this->action($tmp);
                $row->Role = $this->generateRole($row, $role);
            }
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
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
        return $data;
    }

    public function show(SalesOrder $data)
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

                //LOGIC
                if ($data->SalesQuotationCode == '<<Auto>>') $data->SalesQuotationCode = $this->autoNumberService->generate($data, 'trdsalesorder', 'SalesQuotationCode');
                if (isset($data->PaymentTerm)) $data->DueDate = addPaymentTermDueDate($data->Date, $data->PaymentTerm);
                $data->save();

                //PUBLIC POST & APPROVAL
                $this->publicPostController->sync($data, 'SalesOrder');
                if (isset($data->Department) && !in_array($data->StatusObj->Code, ['entry']))
                    $this->publicApprovalController->formCreate($data, 'SalesOrder');

                //DETAIL & TOTAL
                if (isset($data->Details)) {
                    foreach ($data->Details as $detail) {
                        $detail = $this->crudController->saveTotal($detail);
                    }
                }

                // $data = $this->crudController->saveTotal($data);
                $this->calculateTotalAmount($data);
                if (!$data) throw new \Exception('Data is failed to be saved');
            });
            $role = $this->roleService->list('SalesOrder'); //rolepermission
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

    public function destroy(SalesOrder $data)
    {
        try {
            //pengecekan
            $tmp = SalesOrderDetail::where('SalesOrder', $data->Oid)->pluck('Oid');

            $check = SalesDeliveryDetail::whereIn('SalesOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Sales Order has already Sales Delivery!");
            $check = SalesInvoiceDetail::whereIn('SalesOrderDetail', $tmp)->get();
            if ($check->count() > 0) throw new \Exception("Sales Order has already Sales Invoice!");

            DB::transaction(function () use ($data) {
                //delete
                $delete = PublicApproval::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = Image::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicComment::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicFile::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = PublicPost::where('Oid', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

                $delete = SalesOrderDetail::where('SalesOrder', $data->Oid)->get();
                foreach ($delete as $row) $row->delete();

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

    public function action(SalesOrder $data)
    {
        $url = 'salesorder';
        $actionOpen = [
            'name' => 'Open',
            'icon' => 'ViewIcon',
            'type' => 'open_url',
            'url' => $url . '/form?item={Oid}&type=' . ($data->Type ?: 'SalesOrder'),
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
        $actionConvertToSalesDelivery = [
            'name' => 'Convert to SalesDelivery',
            'icon' => 'ZapIcon',
            'type' => 'global_form',
            'showModal' => false,
            'post' => $url . '/{Oid}/convertsd',
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
        $actionConvertToSalesInvoice = [
            'name' => 'Convert to SalesInvoice',
            'icon' => 'ZapIcon',
            'type' => 'global_form',
            'post' => $url . '/{Oid}/convertsi',
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
        $actionSubmit = $this->publicApprovalController->formAction($data, 'SalesOrder', 'submit');
        $actionRequest = $this->publicApprovalController->formAction($data, 'SalesOrder', 'request');
        $actionCancel = [
            'name' => 'Cancel',
            'icon' => 'ArrowUpCircleIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancel',
            'afterRequest' => 'apply'
        ];
        $actionEditCodeNote = [
            "name" => "Edit Code & Note",
            "icon" => "ActivityIcon",
            "type" => "global_form",
            "showModal" => false,
            "get" => "salesorder/{Oid}",
            "post" => "salesorder/{Oid}",
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
        $openSalesDelivery = [
            'name' => 'Open Sales Delivery',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'salesorder/relatedsalesdelivery/{Oid}'
        ];
        $openSalesInvoice = [
            'name' => 'Open Sales Invoice',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'salesorder/relatedsalesinvoice/{Oid}'
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                $return[] = $actionOpen;
                break;
            case "request":
                $return[] = $actionEntry;
                $return[] = $actionSubmit;
                break;
            case "entry":
                $return[] = $actionRequest;
                $return[] = $actionSubmit;
                $return[] = $actionCancel;
                break;
            case "submit":
                $return = $this->publicApprovalController->formAction($data, 'SalesOrder', 'approval');
                $return[] = $actionEntry;
                break;
            case "posted":
                $return[] = $actionEditCodeNote;
                $return[] = $actionEntry;
                $return[] = $actionConvertToSalesDelivery;
                $return[] = $actionConvertToSalesInvoice;
                $return[] = $openSalesDelivery;
                $return[] = $openSalesInvoice;
                break;
            case "complete":
                $return[] = $actionEditCodeNote;
                $return[] = $openSalesDelivery;
                $return[] = $openSalesInvoice;
                break;
        }
        return $return;
    }

    public function relatedSalesDelivery(SalesOrder $data) {
        $tmp = SalesOrderDetail::where('SalesOrder', $data)->pluck('Oid');
        $tmp = SalesDeliveryDetail::whereNotNull('GCRecord')
            ->whereIn('SalesOrderDetail', $tmp)->pluck('SalesDelivery');
        $data = SalesDelivery::whereIn('Oid',$tmp)->get();
        $results = [];
        foreach($data as $row) {
            $result[] = [
                'Oid' => $data->Oid,
                'Code' => $data->Code,
                'Date' => $data->Date,
                'BusinessPartner' => $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null,
                'Status' => $data->StatusObj ? $data->StatusObj->Code : null,
                'Action' => [
                    'name' => 'Open Sales Delivery',
                    'icon' => 'ListIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=SalesDelivery",
                    'get' => 'salesdelivery/{Oid}'    
                ]
            ];
        }
        return $results;
    }

    public function relatedSalesInvoice(SalesOrder $data) {
        $tmp = SalesOrderDetail::where('SalesOrder', $data)->pluck('Oid');
        $tmp = SalesInvoiceDetail::whereNotNull('GCRecord')
            ->whereIn('SalesOrderDetail', $tmp)->pluck('SalesInvoice');
        $data = SalesInvoice::whereIn('Oid',$tmp)->get();
        $results = [];
        foreach($data as $row) {
            $result[] = [
                'Oid' => $data->Oid,
                'Code' => $data->Code,
                'Date' => $data->Date,
                'BusinessPartner' => $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null,
                'Status' => $data->StatusObj ? $data->StatusObj->Code : null,
                'Action' => [
                    'name' => 'Open Sales Invoice',
                    'icon' => 'ListIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=SalesInvoice",
                    'get' => 'salesinvoice/{Oid}'    
                ]
            ];
        }
        return $results;
    }

    private function calculateTotalAmount(SalesOrder $data)
    {
        $totalAmount = 0;
        foreach ($data->Details as $row) $totalAmount += ($row->Quantity ?: 0) * ($row->Price ?: 0);
        $data->SubtotalAmount = $totalAmount;
        $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountAmount;
        $data->TotalAmountWording = convert_number_to_words($data->TotalAmount);
        $data->save();
    }

    public function statusEntry(SalesOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'entry')->first()->Oid;
                $data->save();

                $this->publicApprovalController->formApprovalReset($data);
                $this->publicPostController->sync($data, 'SalesOrder');
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

    public function statusCancel(SalesOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'cancel')->first()->Oid;
                $data->save();
                $this->publicPostController->sync($data, 'SalesOrder');

                $user = Auth::user();
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

    public function statusUnpost(SalesOrder $data)
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

    public function statusPost(SalesOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Posted')->first()->Oid;
                $data->Type = 'SalesOrder';
                $data->Code = $this->autoNumberService->generate($data, 'trdsalesorder');
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

    public function cancelled(SalesOrder $data)
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

    public function convertToSalesDelivery(Request $request, $source)
    {
        $tmp = SalesOrderDetail::where('SalesOrder', $source)->pluck('Oid');
        $check = SalesDeliveryDetail::whereIn('SalesOrderDetail', $tmp)->get();
        if ($check->count() > 0) throw new \Exception("Sales Order has already Sales Deliver");
        $check = SalesInvoiceDetail::whereIn('SalesOrderDetail', $tmp)->get();
        if ($check->count() > 0) throw new \Exception("Sales Order has already Sales Invoice");
        $check = CashBankDetail::whereIn('SalesOrderDetail', $tmp)->get();
        if ($check->count() > 0) throw new \Exception("Sales Order has already Cash Bank");

        $data = new SalesDelivery();
        try {
            DB::transaction(function () use ($request, $source, &$data) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

                // get data
                $source = SalesOrder::with('Details')->where('Oid', $source)->first();
                $businessPartner = BusinessPartner::with('BusinessPartnerAccountGroupObj')->where('Oid', $source->BusinessPartner)->first();

                //important field
                $data->SalesOrder = $source->Oid;
                $data->Code = '<<Auto>>';
                $data->Date = $request->Date ?: Carbon::now();
                // $data->CodeReff = $request->CodeReff ?: $source->CodeReff; //perlu tambah codereff di SD ?
                $data->Account = $source->Account ?: $businessPartner->BusinessPartnerAccountGroupObj->SalesDelivery;
                $data->Status = Status::entry()->first()->Oid;

                //samefield
                $convertField = ['Company', 'BusinessPartner', 'Currency', 'Quantity', 'Employee', 'Warehouse', 'DiscountAmount', 'SubtotalAmount', 'TotalAmount', 'Rate', 'Note'];
                foreach ($convertField as $f) if (isset($source->{$f})) $data->{$f} = $source->{$f};
                $data->save();

                //autonumber
                $data->Code = $this->autoNumberService->generate($data, 'trdsalesdelivery');

                $data->IsConvertPD = true;
                $data->save();

                foreach ($data->Details as $row) {
                    $detail = new SalesDeliveryDetail();
                    $detail->Company = $source->Company;
                    $detail->SalesDelivery = $data->Oid;
                    $detail->Reference = 'SO: ' . $row->ParentCode;
                    $detail->SalesOrderDetail = $row->Oid;
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

    public function convertToSalesInvoice(Request $request, $source)
    {
        $tmp = SalesOrderDetail::where('SalesOrder', $source)->pluck('Oid');
        $check = SalesDeliveryDetail::whereIn('SalesOrderDetail', $tmp)->get();
        if ($check->count() > 0) throw new \Exception("Sales Order has already Sales Deliver");
        $check = SalesInvoiceDetail::whereIn('SalesOrderDetail', $tmp)->get();
        if ($check->count() > 0) throw new \Exception("Sales Order has already Sales Invoice");
        $check = CashBankDetail::whereIn('SalesOrderDetail', $tmp)->get();
        if ($check->count() > 0) throw new \Exception("Sales Order has already Cash Bank");

        $data = new SalesInvoice();
        try {
            DB::transaction(function () use ($request, $source, &$data) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

                // get data
                $source = SalesOrder::with('Details')->where('Oid', $source)->first();
                $businessPartner = BusinessPartner::with('BusinessPartnerAccountGroupObj')->where('Oid', $source->BusinessPartner)->first();

                //important field
                $data->SalesOrder = $source->Oid;
                $data->Code = $request->Code ? $request->Code : '<<Auto>>';
                $data->Date = $request->Date ?: Carbon::now();
                $data->CodeReff = $request->CodeReff ?: $source->Code;
                $data->Account = $source->Account ?: $businessPartner->BusinessPartnerAccountGroupObj->SalesInvoice;
                if (isset($source->PaymentTerm)) $data->DueDate = addPaymentTermDueDate($source->Date, $source->PaymentTerm);

                //samefield
                $convertField = ['Company', 'BusinessPartner', 'Currency', 'Quantity', 'Employee', 'Warehouse', 'PaymentTerm', 'DiscountAmount', 'SubtotalAmount', 'TotalAmount', 'Rate', 'Note'];
                foreach ($convertField as $f) if (isset($source->{$f})) $data->{$f} = $source->{$f};
                $data->save();
                $this->SalesInvoiceService->post($data->Oid);

                //autonumber
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'trdsalesinvoice');

                $totalAmount = 0;
                foreach ($source->Details as $row) {
                    $detail = new SalesInvoiceDetail();
                    $detail->Company = $source->Company;
                    $detail->SalesInvoice = $data->Oid;
                    $detail->Reference = 'SO: ' . $source->Code;
                    $detail->SalesOrderDetail = $row->Oid;

                    //same field
                    $convertField = ['Sequence', 'Item', 'Price', 'Quantity', 'Note', 'TotalAmount', 'CostCenter'];
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
        if ($data instanceof SalesOrder) $status = $data->StatusObj->Code;
        else $status = Status::entry();
        if (!$role) $role = $this->roleService->list('SalesOrder');
        if (!$action) $action = $this->roleService->action('SalesOrder');
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
}
