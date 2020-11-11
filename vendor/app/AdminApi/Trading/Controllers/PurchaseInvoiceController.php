<?php

namespace App\AdminApi\Trading\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\StockTransfer;
use App\Core\POS\Entities\ETicket;
use App\Core\Internal\Entities\Status;
use App\Core\Trading\Entities\StockTransferDetail;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Accounting\Services\PurchaseInvoiceService;
use App\Core\Internal\Services\ExportExcelService;
use App\Core\Trading\Entities\PurchaseOrderDetail;
use App\Core\Trading\Entities\PurchaseDeliveryDetail;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Trading\Entities\PurchaseInvoiceDetail;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Accounting\Entities\Account;
use App\AdminApi\Travel\Controllers\TravelAPIController;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Base\Services\TravelAPIService;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Development\Controllers\CRUDLinkController;

class PurchaseInvoiceController extends Controller
{
    protected $roleService;
    protected $PurchaseInvoiceService;
    protected $excelImportService;
    protected $excelExportService;
    private $travelAPIService;
    private $autoNumberService;
    private $module;
    private $crudController;
    private $linkController;

    public function __construct(
        PurchaseInvoiceService $PurchaseInvoiceService,
        RoleModuleService $roleService,
        Excel $excelImportService,
        ExportExcelService $excelExportService,
        TravelAPIService $travelAPIService,
        AutoNumberService $autoNumberService
    ) {
        $this->purchaseInvoiceService = $PurchaseInvoiceService;
        $this->roleService = $roleService;
        $this->excelImportService = $excelImportService;
        $this->excelExportService = $excelExportService;
        $this->travelAPIService = $travelAPIService;
        $this->autoNumberService = $autoNumberService;
        $this->module = 'trdpurchaseinvoice';
        $this->crudController = new CRUDDevelopmentController();
        $this->linkController = new CRUDLinkController();
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
                'fieldToSave' => 'Company',
                'type' => 'combobox',
                'column' => '1/6',
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
                'fieldToSave' => 'Account',
                'type' => 'autocomplete',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => null,
                'source' => [],
                'store' => 'autocomplete/account',
                'hiddenField' => 'AccountName',
                'params' => [
                    'type' => 'combo',
                    'form' => 'cashbank',
                    'term' => ''
                ]
            ],
            [
                'fieldToSave' => 'DateFrom',
                'type' => 'inputdate',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateTo',
                'type' => 'inputdate',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                "fieldToSave" => "Status",
                'hiddenField' => 'StatusName',
                "type" => "combobox",
                "column" => "1/6",
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
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module . ' as data');

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

            //SECURITY FILTER COMPANY
            if ($user->CompanyAccess) {
                $data = $data->leftJoin('company AS CompanySecurity', 'CompanySecurity.Oid', '=', 'data.Company');
                $tmp = json_decode($user->CompanyAccess);
                $data = $data->whereIn('CompanySecurity.Code', $tmp);
            }

            $data = $this->crudController->list($this->module, $data, $request);
            foreach ($data->data as $row) {
                $tmp = PurchaseInvoice::where('Oid', $row->Oid)->first();
                $row->Action = $this->action($tmp);
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

        $query = "SELECT  i.Oid AS Item, i.Code, i.Name, COUNT(*) AS Stock, et.CostPrice,DATE_FORMAT(et.DateExpiry, '%Y-%m-%d') AS DateExpiry, DATE_FORMAT(et.DateValidFrom, '%Y-%m-%d') AS DateValidFrom
            FROM poseticket et 
            INNER JOIN mstitem i ON i.Oid = et.Item
            WHERE et.PointOfSale IS NULL AND et.PurchaseInvoice ='{$data->Oid}'
            GROUP BY et.CostPrice,et.DateExpiry, i.Name, i.Code, i.Oid, et.DateValidFrom
            HAVING COUNT(*) > 0
            ORDER BY i.Name";
        $eticket = DB::select($query);
        // $role = $this->roleService->list('PurchaseInvoice'); //rolepermission
        // $data->Role = $this->generateRole($data, $role);

        $data->Action = $this->action($data);
        $data->EticketSummary = $eticket;
        foreach ($data->Details as $row) {
            $row->Action = $this->actionDetail($row);
        }
        return $data;
    }

    public function show(PurchaseInvoice $data)
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
        $token = $request->bearerToken();
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

        try {
            if (!$Oid) {
                $data = new PurchaseInvoice();
            } else {
                $data = PurchaseInvoice::findOrFail($Oid);
            }
            DB::transaction(function () use ($request, &$data, $token) {
                // $strOrder = "";
                // $strDelivery = "";
                $data = $this->crudController->save('trdpurchaseinvoice', $data, $request);
                if (isset($request->Type)) {
                    $data->Type = $request->Type;
                }
                if (isset($data->PaymentTerm)) {
                    $data->DueDate = addPaymentTermDueDate($data->Date, $data->PaymentTerm);
                }
                $data->save();

                if (isset($request->ETickets)) {
                    $this->crudController->deleteDetail($data->ETickets, $request->ETickets);
                    foreach ($request->ETickets as $row) {
                        if (isset($row->Oid)) {
                            $detail = ETicket::findOrFail($row->Oid);
                        } else {
                            $detail = new ETicket();
                        }
                        $detail->Type = $row->Type;
                        $detail = $this->crudController->save('poseticket', $detail, $row, $data);
                        $detail->save();
                    }
                    $data->load('ETickets');
                    $data->fresh();
                }
                $data = $this->crudController->saveTotal($data);
                $data->save();

                // $this->updateQtyPurchaseDelivery($strDelivery);
                // $this->updateQtyPurchaseOrder($strOrder);

                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $role = $this->roleService->list('PurchaseInvoice'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Role = $this->generateRole($role, $data);
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

    public function savedetail(Request $request)
    {
        try {
            if ($request->input('Oid')) $data = PurchaseInvoiceDetail::where('Oid', $request->input('Oid'))->first();
            if (!$data) $data = new PurchaseInvoiceDetail();
            DB::transaction(function () use ($request, &$data) {
                $purchaseInvoice = PurchaseInvoice::findOrFail($request->input('PurchaseInvoice'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

                $data = $this->crudController->save('trdpurchaseinvoicedetail', $data, $request, $purchaseInvoice);
                if ($data->Sequence == 0 || !$data->Sequence) {
                    try {
                        $sequence = (PurchaseInvoiceDetail::where('PurchaseInvoice', $purchaseInvoice->Oid)->whereNotNull('Sequence')->max('Sequence') ?: 0 + 1);
                    } catch (\Exception $ex) {
                        $err = true;
                    }
                    $data->Sequence = $sequence;
                    $data->save();
                }

                if ($data->PurchaseDeliveryDetail) $this->linkController->PurchaseDeliveryCalculateOutstanding($data->PurchaseDeliveryDetail);
                elseif ($data->PurchaseOrderDetail) $this->linkController->PurchaseOrderCalculateOutstanding($data->PurchaseOrderDetail);
                $this->crudController->saveTotal($purchaseInvoice);

                $data->ParentObj = [
                    'TotalAmount' => $purchaseInvoice->TotalAmount
                ];
                if (!$data) throw new \Exception('Data is failed to be saved');
            });
            $data = $this->crudController->detail('trdpurchaseinvoicedetail', $data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function deletedetail($Oid)
    {
        try {
            $data = PurchaseInvoiceDetail::where('Oid', $Oid)->first();
            $parent = $data->PurchaseInvoiceObj;
            if ($data->PurchaseDeliveryDetail) {
                $det = $data->PurchaseDeliveryDetail;
                $data->delete();
                $this->linkController->PurchaseDeliveryCalculateOutstanding($det);
            } elseif ($data->PurchaseOrderDetail) {
                $det = $data->PurchaseOrderDetail;
                $data->delete();
                $this->linkController->PurchaseOrderCalculateOutstanding($det);
            } else {
                $data->delete();
            }
            $this->crudController->saveTotal($parent);
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

    public function destroy(PurchaseInvoice $data)
    {
        try {
            //pengecekan
            $tmp = PurchaseInvoiceDetail::where('PurchaseInvoice', $data->Oid)->pluck('Oid');

            $check = PurchaseOrderDetail::whereIn('PurchaseInvoiceDetail', $tmp)->get();
            if ($check->count() > 0) {
                throw new \Exception("Purchase Invoice has already Purchase Order!");
            }
            $check = PurchaseDeliveryDetail::whereIn('PurchaseInvoiceDetail', $tmp)->get();
            if ($check->count() > 0) {
                throw new \Exception("Purchase Invoice has already Purchase Delivery!");
            }

            DB::transaction(function () use ($data) {
                //delete
                $delete = PublicApproval::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = Image::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PublicComment::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PublicFile::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PublicPost::where('Oid', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PurchaseInvoiceDetail::where('PurchaseInvoice', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

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

    private function generateRole($data, $role = null, $action = null)
    {
        if ($data instanceof PurchaseInvoice) {
            $status = $data->StatusObj->Code;
        } else {
            $status = Status::entry();
        }
        if (!$role) {
            $role = $this->roleService->list('PurchaseInvoice');
        }
        if (!$action) {
            $action = $this->roleService->action('PurchaseInvoice');
        }
        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->roleService->isAllowDelete($status, $role->IsEdit),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Cancel' => $this->roleService->isAllowCancel($status, $action->Cancel),
            'Entry' => $this->roleService->isAllowEntry($status, $action->Entry),
            'Post' => $this->roleService->isAllowPost($status, $action->Posted),
            'ViewJournal' => $this->roleService->isPosted($status, 1),
            'ViewStock' => $this->roleService->isPosted($status, 1),
            'Print' => $this->roleService->isPosted($status, 1),
        ];
    }

    public function action(PurchaseInvoice $data)
    {
        $url = 'purchaseinvoice';
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
        $actionconvertStockTransfer = [
            'name' => 'Convert to StockTransfer',
            'icon' => 'ZapIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/convert',
        ];
        $actionConvertToCashBank = [
            'name' => 'Convert to Payment',
            'icon' => 'ZapIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/convertcashbank',
        ];
        $actionCancelled = [
            'name' => 'Change to Cancelled',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancelled',
        ];
        $actionprintprereportpi = [
            'name' => 'Print PreReport',
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
                        ['Oid' => 'purchaseinvoice', 'Name' => 'Purchase Invoice'],
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
        $actionViewJournal = [
            'name' => 'View Journal',
            'icon' => 'BookOpenIcon',
            'type' => 'open_grid',
            'get' => 'journal?' . $url . '={Oid}',
        ];
        $actionViewStock = [
            'name' => 'View Stock',
            'icon' => 'PackageIcon',
            'type' => 'open_grid',
            'get' => 'stock?' . $url . '={Oid}',
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
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "CodeReff",
                    'type' => "inputtext"
                ],
                [
                    'fieldToSave' => "Note",
                    'type' => "inputarea"
                ],
            ]
        ];
        $openCashBank = [
            'name' => 'Open CashBank',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'purchaseinvoice/relatedcashbank/{Oid}'
        ];
        $openPurchaseInvoice = [
            'name' => 'Related Invoice',
            'icon' => 'ListIcon',
            'type' => 'open_grid',
            'get' => 'purchaseorder/relatedpurchaseinvoice/{Oid}'
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                $return[] = $actionDelete;
                break;
            case "entry":
                $return[] = $actionPosted;
                $return[] = $actionDelete;
                break;
            case "posted":
                $return[] = $actionEntry;
                $return[] = $actionCancelled;
                $return[] = $actionconvertStockTransfer;
                $return[] = $actionConvertToCashBank;
                $return[] = $actionprintprereportpi;
                $return[] = $actionViewJournal;
                $return[] = $actionViewStock;
                $return[] = $actionEditCodeNote;
                $return[] = $openCashBank;
                break;
            case "complete":
                $return[] = $actionViewJournal;
                $return[] = $actionViewStock;
                $return[] = $actionEditCodeNote;
                $return[] = $openCashBank;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }

    public function relatedCashBank(PurchaseInvoice $data)
    {
        $tmp = PurchaseInvoiceDetail::where('PurchaseInvoice', $data)->pluck('Oid');
        $tmp = CashBankDetail::whereNotNull('GCRecord')
            ->whereIn('PurchaseInvoiceDetail', $tmp)->pluck('CashBank');
        $data = CashBank::whereIn('Oid', $tmp)->get();
        $results = [];
        foreach ($data as $row) {
            $result[] = [
                'Oid' => $data->Oid,
                'Code' => $data->Code,
                'Date' => $data->Date,
                'BusinessPartner' => $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null,
                'Status' => $data->StatusObj ? $data->StatusObj->Code : null,
                'Action' => [
                    'name' => 'Open CashBank',
                    'icon' => 'ListIcon',
                    'type' => 'open_view',
                    'portalget' => "development/table/vueview?code=CashBank",
                    'get' => 'cashbank/{Oid}'
                ]
            ];
        }
        return $results;
    }

    public function actionDetail($data)
    {
        $return = [];
        $return[] = [
            'name' => 'View E-Tickets',
            'icon' => 'ListIcon',
            'type' => 'open_form',
            'newTab' => true,
            'url' => "poseticketupload/detaillist?item=" . $data->Item . "&costprice=" . $data->Price . "&purchaseinvoice=" . $data->PurchaseInvoice
        ];
        if ($data->PurchaseOrderDetailObj) {
            $return[] = [
                'name' => 'Open Purchase Order',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=PurchaseOrder",
                'get' => 'purchaseorder/' . ($data->PurchaseOrderDetailObj ? $data->PurchaseOrderDetailObj->PurchaseOrder : null)
            ];
        }
        if ($data->PurchaseDeliveryDetailObj) {
            $return[] = [
                'name' => 'Open Purchase Delivery',
                'icon' => 'ListIcon',
                'type' => 'open_view',
                'portalget' => "development/table/vueview?code=PurchaseDelivery",
                'get' => 'purchasedelivery/' . ($data->PurchaseDeliveryDetailObj ? $data->PurchaseDeliveryDetailObj->PurchaseDelivery : null)
            ];
        }
        return $return;
    }

    public function import(Request $request, PurchaseInvoice $data)
    {
        $validator = Validator::make($request->all(), ['file' => 'required|mimes:xls,xlsx']);

        if ($validator->fails()) {
            return response()->json($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$request->hasFile('file')) {
            return response()->json('No file found', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->file('file');
        $this->excelImportService->import(new PurchaseInvoiceExcelImport($data), $file);
        return response()->json(null, Response::HTTP_CREATED);
    }

    public function post(PurchaseInvoice $data)
    {
        try {
            $this->purchaseInvoiceService->post($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function unpost(PurchaseInvoice $data)
    {
        try {
            $this->purchaseInvoiceService->unpost($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function cancelled(PurchaseInvoice $data)
    {
        try {
            $this->purchaseInvoiceService->cancelled($data->Oid);
            $tmp = collect($data->Details)->pluck('PurchaseOrderDetail');
            if ($tmp) $this->linkController->PurchaseOrderCalculateOutstanding($tmp);
            $tmp = collect($data->Details)->pluck('PurchaseDeliveryDetail');
            if ($tmp) $this->linkController->PurchaseDeliveryCalculateOutstanding($tmp);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function export(Request $request)
    {
        $criteria = "";
        $query = "SELECT p.Code PurchaseInvoice, DATE_FORMAT(p.Date, '%Y-%m%-%d') Date, 
            CONCAT(bp.Name,' - ',bp.Code) BusinessPartner, CONCAT(i.Name,' - ',i.Code) Item, 
            IFNULL(d.Quantity,0) Qty, c.Code Currency, 
            IFNULL(d.Price,0) Price, 
            IFNULL(d.Quantity,0)*IFNULL(d.Price,0) TotalAmount, p.Rate
            FROM trdpurchaseinvoice p
            LEFT OUTER JOIN trdpurchaseinvoicedetail d ON p.Oid = d.PurchaseInvoice
            LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
            LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
            LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency
            WHERE p.GCRecord IS NULL ";
        $datefrom = Carbon::parse($request->input('datefrom'));
        $dateto = Carbon::parse($request->input('dateto'));
        if ($request->has('datefrom')) {
            $criteria = $criteria . " AND p.Date >= '{$datefrom->format('Y-m-d')}' ";
        }
        if ($request->has('dateto')) {
            $criteria = $criteria . " AND p.Date <= '{$dateto->format('Y-m-d')}'";
        }
        if ($request->has('businesspartner')) {
            $criteria = $criteria . " AND p.BusinessPartner = '{$request->datefrom}'";
        }
        if ($request->has('warehouse')) {
            $criteria = $criteria . " AND p.Warehouse = '{$request->warehouse}'";
        }
        if ($request->has('item')) {
            $criteria = $criteria . " AND d.Item = '{$request->item}'";
        }

        $data = DB::select($query . $criteria);
        $string = '';
        $i = 0;
        foreach ($data as $item) {
            $j = 0;
            foreach ($item  as $itemChild) {
                $string .= $itemChild;
                if ($i < count($data)) {
                    $string .= ';';
                }
                $j++;
            }
            if ($i < count($data)) {
                $string .= '\n';
            }
            $i++;
        }
        return response()->json(
            $string,
            '200'
        );
        //        return $this->excelExportService->export($data);
    }

    public function convertStockTransfer(PurchaseInvoice $data, Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        $stock = new StockTransfer();
        try {
            DB::transaction(function () use ($request, $stock, &$data) {
                $stock->Company = $data->Company;
                $stock->Code = now()->format('ymdHis') . '-' . str_random(3);
                $stock->Date = Carbon::now();
                $stock->User = Auth::user()->Oid;
                $stock->WarehouseFrom = $data->Warehouse;
                $stock->WarehouseTo = $request->WarehouseTo;
                $stock->Note = $data->Note;
                $stock->Status = Status::entry()->first()->Oid;
                if (isset($data->PaymentTerm)) {
                    $stock->DueDate = addPaymentTermDueDate($data->Date, $data->PaymentTerm);
                }
                $stock->save();

                $details = [];
                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $rowdetail) {
                        $details[] = new StockTransferDetail([
                            'Item' => $rowdetail->Item,
                            'ItemUnit' => Auth::user()->CompanyObj->ItemUnit,
                            'Quantity' => $rowdetail->Quantity
                        ]);
                    }
                    $stock->Details()->saveMany($details);
                }

                if (!$stock) {
                    throw new \Exception('Data is failed to be saved');
                }
            });


            return response()->json(
                $stock,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function convertToCashBank(Request $request, $source)
    {
        try {
            // $check = CashBankDetail::where('PurchaseInvoice', $source)->first();
            // if ($check) throw new \Exception("Purchase Invoice has already Cash Bank");

            $data = new CashBank();
            DB::transaction(function () use ($request, $source, &$data) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

                // get data
                $source = PurchaseInvoice::with('Details')->where('Oid', $source)->first();
                $account = Account::whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['CASH', 'BANK']);
                })->where('Currency', $source->Currency ?: $source->AccountObj->Currency)->first();

                $data->PurchaseInvoice = $source->Oid;
                $data->Company = $source->Company;
                $data->Type = 3;
                $data->Code = '<<Auto>>';
                $data->Date = Carbon::now();
                $data->Currency = $source->Currency;
                $data->Account = $account->Oid;
                $data->BusinessPartner = $source->BusinessPartner;
                $data->Warehouse = $source->Warehouse;
                $data->Status = $source->Status;
                $data->Rate = $source->Rate;
                $data->Note = $source->Note;
                $data->TotalAmount = $source->TotalAmount;
                $data->Status = Status::entry()->first()->Oid;
                $data->save();
                $data->Code = $this->autoNumberService->generate($data, 'acccashbank');
                $data->save();

                $source->IsConvert = true;
                $source->save();

                $details = [];
                $curCashBank = $source->CurrencyObj;
                $curInvoice = $source->CurrencyObj;
                $rate = $curCashBank->getRate($data->Date);
                $rate = $rate != null ? $rate->MidRate : 1;
                if ($curCashBank->Oid == $source->Currency) {
                    $amountCashBank = $source->OutstandingAmount;
                    $amountCashBankBase = $curCashBank->toBaseAmount($source->OutstandingAmount, $rate);
                } else {
                    $amountCashBank = $curInvoice->convertRate($curCashBank->Oid, $source->OutstandingAmount, $data->Date);
                    $amountCashBankBase = $curInvoice->toBaseAmount($amountCashBank, $rate);
                }

                $detail = new CashBankDetail();
                $detail->CashBank = $data->Oid;
                $detail->Company = $data->Company;
                $detail->Sequence = 1;
                $detail->Description = 'Invoice: ' . $source->Code . ' ' . $source->Date;
                $detail->PurchaseInvoice = $source->Oid;
                $detail->Currency = $source->Currency;
                $detail->Rate = $rate;
                $detail->Account = $source->Account;
                $detail->Description = $source->Code . ' ' . $source->CurrencyCode;
                $detail->AmountInvoice = $source->TotalAmount;
                $detail->AmountInvoiceBase = $curInvoice->toBaseAmount($source->TotalAmount, $rate);
                $detail->AmountCashBank = $amountCashBank;
                $detail->AmountCashBankBase = $amountCashBankBase;
                $detail->save();

                $source->PaidAmount = $source->TotalAmount;
                $source->Status = Status::where('Code', 'complete')->first()->Oid;
                $source->save();

                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });
            $source = PurchaseInvoice::with('Details')->where('Oid', $source)->first();
            $source->PaidAmount = $source->TotalAmount;
            $source->Status = Status::where('Code', 'complete')->first()->Oid;
            $source->save();

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

    private function updatePayment($string)
    {
        $query = "UPDATE trdpurchaseinvoice pinv
            LEFT OUTER JOIN (
                SELECT cbd.PurchaseInvoice, SUM(IFNULL(cbd.AmountInvoice,0)) AS PaidAmount 
                FROM acccashbankdetail cbd 
                WHERE cbd.PurchaseInvoice IN (" . $string . ") 
                AND cbd.GCRecord IS NULL GROUP BY cbd.PurchaseInvoice
            ) cbd ON cbd.PurchaseInvoice = pinv.Oid
            SET pinv.PaidAmount = IFNULL(cbd.PaidAmount,0)
            WHERE pinv.Oid IN (" . $string . ")";
        if ($string != '') {
            DB::Update($query);
        }
    }

    public function partialDelivery(Request $request)
    {
        $query = "SELECT pdd.Oid, pd.Code AS Code, CONCAT(i.Name,' - ',i.Code) AS Name,
            c.Oid AS Currency, (IFNULL(pdd.Quantity,0) - IFNULL(pdd.QuantityInvoiced,0)) AS Quantity, pdd.Price
            FROM trdpurchasedelivery pd
            LEFT OUTER JOIN trdpurchasedeliverydetail pdd ON pdd.PurchaseDelivery = pd.Oid
            LEFT OUTER JOIN mstitem i ON pdd.Item = i.Oid
            LEFT OUTER JOIN mstbusinesspartner bp ON pd.BusinessPartner = bp.Oid
            LEFT OUTER JOIN mstcurrency c ON pd.Currency = c.Oid
            LEFT OUTER JOIN sysstatus s ON pd.Status = s.Oid
            WHERE (IFNULL(pdd.Quantity,0) - IFNULL(pdd.QuantityInvoiced,0)) > 0
            AND pd.GCRecord IS NULL
            AND pd.Company = '{$request->input('company')}'
            AND pd.Oid NOT IN ({$request->input('exception')})
            AND pd.BusinessPartner = '{$request->input('businesspartner')}'
            AND DATE_FORMAT(pd.Date, '%Y-%m-%d') <= '{$request->input('date')}'
            AND s.Code = 'posted'";
        $data = DB::select($query);

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function partialDeliveryAdd(Request $request)
    {
        try {
            $result = [];
            DB::transaction(function () use ($request, &$result) {
                $purchaseInvoice = PurchaseInvoice::findOrFail($request->input('oid'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $string = "";
                foreach ($request as $row) {
                    $string = ($string ? $string . "," : null) . "'" . $row . "'";
                }
                // $param = collect($request)->pluck('Oid');
                $query = "SELECT pdd.*, (IFNULL(pdd.Quantity,0) - IFNULL(pdd.QuantityInvoiced,0)) AS OutstandingQuantity,
                    i.Name AS ItemName, pd.Code AS ParentCode
                    FROM trdpurchasedelivery pd
                    LEFT OUTER JOIN trdpurchasedeliverydetail pdd ON pdd.PurchaseDelivery = pd.Oid
                    LEFT OUTER JOIN sysstatus s ON pd.Status = s.Oid
                    LEFT OUTER JOIN mstitem i ON i.Oid = pdd.Item
                    WHERE (IFNULL(pdd.Quantity,0) - IFNULL(pdd.QuantityInvoiced,0)) > 0
                    AND pd.GCRecord IS NULL AND pdd.Oid IN (" . $string . ")
                    ";
                $data = DB::select($query);
                $total = 0;
                $sequence = (PurchaseInvoiceDetail::where('PurchaseInvoice', $data->Oid)->max('Sequence') ?: 0) + 1;
                foreach ($data as $row) {
                    $detail = new PurchaseInvoiceDetail();
                    $detail->PurchaseInvoice = $purchaseInvoice->Oid;
                    $detail->PurchaseDeliveryDetail = $row->Oid;
                    $detail->Company = $row->Company;
                    $detail->Sequence = $sequence;
                    $sequence = $sequence + 1;
                    $detail->Item = $row->Item;
                    $detail->Reference = 'PD: ' . $row->ParentCode;
                    $detail->Quantity = $row->OutstandingQuantity;
                    $detail->QuantityBase = $row->OutstandingQuantity;
                    $detail->ItemUnit = $row->ItemUnit;
                    $detail->Price = $row->Price;
                    $detail->DiscountAmount = $row->DiscountAmount;
                    $detail->DiscountPercentage = $row->DiscountPercentage;
                    $detail->SubtotalAmount = $row->OutstandingQuantity * $row->Price;
                    $detail->TotalAmount = $detail->SubtotalAmount - $row->DiscountAmount;
                    $detail->Note = $row->Note;
                    $detail->CostCenter = $row->CostCenter;
                    $detail->save();

                    $detail->ItemName = $row->ItemName;

                    $tmp = PurchaseDeliveryDetail::findOrFail($row->Oid);
                    $tmp->QuantityInvoiced = $tmp->Quantity;
                    $tmp->save();

                    $total += $detail->TotalAmount;
                    $result[] = $detail;
                }
                // $this->calculateTotalAmount($purchaseInvoice);
                $this->crudController->saveTotal($purchaseInvoice);
            });

            return response()->json(
                $result,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function calculateTotalAmount(PurchaseInvoice $data)
    {
        $totalAmount = 0;
        foreach ($data->Details as $row) {
            $totalAmount += ($row->Quantity ?: 0) * ($row->Price ?: 0);
        }
        $data->SubtotalAmount = $totalAmount;
        $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountAmount;
        $data->save();
    }

    public function partialOrder(Request $request)
    {
        $query = "SELECT pod.Oid, po.Code, CONCAT(i.Name,' - ',i.Code) AS Name, c.Code AS Currency, 
                (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityInvoiced,0)) AS Quantity, pod.Price
                FROM trdpurchaseorder po
                LEFT OUTER JOIN trdpurchaseorderdetail pod ON pod.PurchaseOrder = po.Oid
                LEFT OUTER JOIN mstitem i ON pod.Item = i.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON po.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstcurrency c ON po.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                WHERE (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityInvoiced,0)) > 0
                AND IFNULL(pod.QuantityDelivered,0) < 1
                AND po.GCRecord IS NULL
                AND po.Company = '{$request->input('company')}'
                AND po.Oid NOT IN ({$request->input('exception')})
                AND po.BusinessPartner = '{$request->input('businesspartner')}'
                AND DATE_FORMAT(po.Date, '%Y-%m-%d') <= '{$request->input('date')}'
                AND s.Code = 'posted'";
        $data = DB::select($query);

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function partialOrderAdd(Request $request)
    {
        try {
            $result = [];
            DB::transaction(function () use ($request, &$result) {
                $purchaseInvoice = PurchaseInvoice::findOrFail($request->input('oid'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $string = "";
                foreach ($request as $row) {
                    $string = ($string ? $string . "," : null) . "'" . $row . "'";
                }
                // $param = collect($request)->pluck('Oid');
                $query = "SELECT pod.*, (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityInvoiced,0)) AS OutstandingQuantity,
                        i.Name AS ItemName, po.Code AS ParentCode
                        FROM trdpurchaseorder po
                        LEFT OUTER JOIN trdpurchaseorderdetail pod ON pod.PurchaseOrder = po.Oid
                        LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                        LEFT OUTER JOIN mstitem i ON i.Oid = pod.Item
                        WHERE (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityInvoiced,0)) > 0
                        AND po.GCRecord IS NULL AND pod.Oid IN (" . $string . ")
                        ";
                $data = DB::select($query);
                $total = 0;
                $sequence = 1;
                foreach ($data as $row) {
                    // $sequence = (PurchaseInvoiceDetail::where('PurchaseInvoice', $row->Oid)->max('Sequence') ?: 0) + 1;
                    $detail = new PurchaseInvoiceDetail();
                    $detail->PurchaseInvoice = $purchaseInvoice->Oid;
                    $detail->PurchaseOrderDetail = $row->Oid;
                    $detail->Company = $row->Company;
                    $detail->Sequence = $sequence;
                    $detail->Reference = 'PO: ' . $row->ParentCode;
                    $sequence = $sequence;
                    $detail->Item = $row->Item;
                    $detail->Quantity = $row->OutstandingQuantity;
                    $detail->QuantityBase = $row->OutstandingQuantity;
                    $detail->ItemUnit = $row->ItemUnit;
                    $detail->Price = $row->Price;
                    $detail->DiscountAmount = $row->DiscountAmount;
                    $detail->DiscountPercentage = $row->DiscountPercentage;
                    $detail->SubtotalAmount = $row->OutstandingQuantity * $row->Price;
                    $detail->TotalAmount = $detail->SubtotalAmount - $row->DiscountAmount;
                    $detail->Note = $row->Note;
                    $detail->CostCenter = $row->CostCenter;
                    $detail->save();

                    $detail->ItemName = $row->ItemName;

                    $tmp = PurchaseOrderDetail::findOrFail($row->Oid);
                    $tmp->QuantityInvoiced = $tmp->Quantity;
                    $tmp->save();
                    $sequence++;
                    $total += $detail->TotalAmount;
                    $result[] = $detail;
                }
                $this->linkController->PurchaseOrderCalculateOutstanding($request);
                $this->crudController->saveTotal($purchaseInvoice);
            });
            $purchaseInvoice = PurchaseInvoice::findOrFail($request->input('oid'));
            $result[0]->ParentObj = [
                'TotalAmount' => $purchaseInvoice->TotalAmount
            ];

            return response()->json(
                $result,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function reorderPurchaseInvoiceDetail(Request $request)
    {
        $query = "SELECT pi.Oid, pid.Oid Detail,  IFNULL(pid.Sequence,-1) AS Sequence 
        from trdpurchaseinvoice po
          LEFT OUTER JOIN trdpurchaseinvoicederail pid ON pi.Oid = pid.PurchaseInvoice
          WHERE pi.Oid = '{$request->input('Oid')}'
          ORDER BY pid.Sequence";
        return DB::select($query);
    }

    public function reorderPurchaseInvoiceUpdate(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        foreach ($request as $row) {
            $query = "UPDATE trdpurchaseinvoicedetail pid SET pid.Sequence = '{$row->Sequence}' WHERE pid.Oid = '{$row->Oid}'";
            DB::update($query);
        }
        return $request;
    }

    public function testProcess(Request $request)
    {
        $limit = 10000;
        $status = Status::whereIn('Code', ['posted', 'complete'])->pluck('Oid');
        $data = PurchaseInvoice::whereIn('Status', $status)->limit($limit)->pluck('Oid');
        foreach ($data as $r) $this->purchaseInvoiceService->post($r);
        if ($limit == 1) return $data;
        else return "SUCESS";
    }
}
