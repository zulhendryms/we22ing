<?php

namespace App\AdminApi\POS\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\PointOfSaleDetail;
use App\Core\POS\Entities\PointOfSaleLog;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\POS\Resources\PointOfSaleResource;
use App\Core\POS\Resources\PointOfSaleCollection;
use App\Core\Internal\Entities\PointOfSaleType;
use App\Core\Internal\Entities\Status;
use App\Core\POS\Services\POSStatusService;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\POS\Entities\POSETicketUpload;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\POS\Services\POSETicketService;
use App\Core\Accounting\Services\SalesPOSService;
use App\Core\Accounting\Services\SalesPOSSessionService;
use App\Core\POS\Entities\POSSession;
use App\Core\Security\Entities\User;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class PointOfSaleController extends Controller
{
    protected $posETicketService;
    protected $roleService;
    protected $posStatusService;
    protected $salesPosService;
    protected $salesPosSessionService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService,
        POSStatusService $posStatusService,
        POSETicketService $posETicketService,
        SalesPOSService $salesPosService,
        SalesPOSSessionService $salesPosSessionService
    ) {
        $this->roleService = $roleService;
        $this->posStatusService = $posStatusService;
        $this->posETicketService = $posETicketService;
        $this->salesPosService = $salesPosService;
        $this->salesPosSessionService = $salesPosSessionService;
        $this->module = 'pospointofsale';
        $this->crudController = new CRUDDevelopmentController();
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
            $user = Auth::user();
            $data = DB::table($this->module.' as data');
        
            // filter businesspartnergroupuser
            $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
            if ($businessPartnerGroupUser->count() > 0) {
                $data->whereIn('Customer.BusinessPartnerGroup', $businessPartnerGroupUser);
            }

            $data = $this->crudController->list('pospointofsale', $data, $request);
            
            $role = $this->roleService->list('POS'); //rolepermission
            // foreach($data as $row) $row->Role = $this->roleService->generateRole($row, $role, $action);
            foreach ($data->data as $row) {
                $tmp = PointOfSale::findOrFail($row->Oid);
                $row->Action = $this->action($tmp);
                $row->Role = $this->roleService->generateRole($row, $role);
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
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        return $data;
    }

    public function show(PointOfSale $data)
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
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                $totalAmount = 0;
                foreach ($data->Details as $row) {
                    $totalAmount += ($row->Quantity ?: 0) * ($row->Amount ?: 0);
                }
                $data->SubtotalAmount = $totalAmount;
                $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount + $data->AdmissionAmount + $data->ConvenienceAmount - $data->DiscountPercentageAmount - $data->DiscountAmount;
                $data->save();
                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $role = $this->roleService->list('POS'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Action = $this->roleService->generateActionMaster($role);
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

    public function destroy(PointOfSale $data)
    {
        try {
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
                
                $delete = PointOfSaleLog::where('PointOfSale', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PointOfSaleDetail::where('PointOfSale', $data->Oid)->get();
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

    public function listDetailTransaction(Request $request)
    {
        try {
            $pos = $request->input('pos');
            $data = TravelTransactionDetail::where('TravelTransaction', $pos);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function listDetailPOS(Request $request)
    {
        try {
            $pos = $request->input('pos');
            $data = PointOfSaleDetail::where('PointOfSale', $pos);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }


    public function saveDetail(Request $request)
    {
        $pos = $request->input('pos');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

        try {
            $data = PointOfSale::where('Oid', $pos)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $cur = Currency::findOrFail($data->Currency);
                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $rowdb) {
                        $found = false;
                        foreach ($request->Details as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            $detail = PointOfSaleDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }

                $totalAmount = 0;
                if ($request->Details) {
                    $details = [];
                    foreach ($request->Details as $row) {
                        if ($row->DiscountPercentage ?: 0 > 0) {
                            $row->DiscountPercentageAmount = (($row->Quantity * $row->Amount) * ($row->DiscountPercentage ?: 0)) / 100;
                        } else {
                            $row->DiscountPercentageAmount = 0;
                        }
                        if (isset($row->Oid)) {
                            $detail = PointOfSaleDetail::findOrFail($row->Oid);
                            $detail->Company = $data->Company;
                            $detail->PointOfSale = $data->Oid;
                            $detail->Item = $row->Item;
                            $detail->Quantity = $row->Quantity;
                            $detail->Amount = $row->Amount;
                            $detail->AmountBase = $cur->toBaseAmount($row->Amount, $data->RateAmount);
                            $detail->AmountCost = $row->Amount;
                            $detail->DiscountAmount = $row->DiscountAmount;
                            $detail->DiscountPercentage = $row->DiscountPercentage;
                            $detail->DiscountPercentageAmount = $row->DiscountPercentageAmount;
                            // $detail->DescriptionSummary = $row->DescriptionSummary;
                            $detail->save();
                        } else {
                            $details[] = new PointOfSaleDetail([
                                'Company' => $data->Company,
                                'PointOfSale' => $data->Oid,
                                'Item' => $row->Item,
                                'Quantity' => $row->Quantity,
                                'Amount' => $row->Amount,
                                'AmountBase' => $row->Amount * $data->RateAmount,
                                'AmountCost' => $row->Amount,
                                'DiscountAmount' => $row->DiscountAmount ?: 0,
                                'DiscountPercentage' => $row->DiscountPercentage ?: 0,
                                'DiscountPercentageAmount' => $row->DiscountPercentageAmount,
                                // 'DescriptionSummary' => $row->DescriptionSummary
                            ]);
                        }
                        $totalAmount += ($row->Quantity * $row->Amount) - $row->DiscountPercentageAmount - $row->DiscountAmount;
                    }
                    $data->Details()->saveMany($details);
                    $data->load('Details');
                    $data->fresh();
                }

                if ($data->TravelDetails()->count() != 0) {
                    logger(5);
                    foreach ($data->TravelDetails as $rowdb) {
                        $found = false;
                        logger(6);
                        foreach ($request->TravelDetails as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            logger(7);
                            $detail = TravelTransactionDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }

                if ($request->TravelDetails) {
                    $details = [];
                    foreach ($request->TravelDetails as $row) {
                        // if ($row->DiscountPercentage ?: 0 > 0) $row->DiscountPercentageAmount = (($row->Quantity * $row->Amount) * ($row->DiscountPercentage ?: 0)) / 100;
                        // else $row->DiscountPercentageAmount = 0;
                        if (isset($row->Oid)) {
                            logger(1);
                            $detail = TravelTransactionDetail::findOrFail($row->Oid);
                            $detail->Company = $data->Company;
                            $detail->TravelTransaction = $data->Oid;
                            $detail->Item = $row->Item;
                            $detail->Quantity = $row->Quantity;
                            $detail->SalesAmount = $row->SalesAmount;
                            // $detail->AmountBase = $row->Amount * $data->RateAmount;
                            // $detail->AmountCost = $row->Amount;
                            // $detail->DiscountAmount = $row->DiscountAmount;
                            // $detail->DiscountPercentage = $row->DiscountPercentage;
                            // $detail->DiscountPercentageAmount = $row->DiscountPercentageAmount;
                            // $detail->DescriptionSummary = $row->DescriptionSummary;
                            $detail->save();
                        } else {
                            logger(2);
                            $details[] = new TravelTransactionDetail([
                                'Company' => $data->Company,
                                'TravelTransaction' => $data->Oid,
                                'Item' => $row->Item,
                                'Quantity' => $row->Quantity,
                                'SalesAmount' => $row->SalesAmount,
                                // 'AmountBase' => $row->Amount * $data->RateAmount,
                                // 'AmountCost' => $row->Amount,
                                // 'DiscountAmount' => $row->DiscountAmount ?: 0,
                                // 'DiscountPercentage' => $row->DiscountPercentage ?: 0,
                                // 'DiscountPercentageAmount' => $row->DiscountPercentageAmount,
                                // 'DescriptionSummary' => $row->DescriptionSummary
                            ]);
                        }
                        $totalAmount += ($row->Quantity * $row->SalesAmount); // - $row->DiscountPercentageAmount - $row->DiscountAmount;
                    }
                    logger(7);
                    $data->TravelDetails()->saveMany($details);
                    $data->load('TravelDetails');
                }
                $data->SubtotalAmount = $totalAmount;
                $data->save();
                $this->calcTotal($data);
            });
 
            return $data;
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

    private function calcTotal(PointOfSale $data)
    {
        $cur = Currency::findOrFail($data->Currency);
        $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountPercentageAmount - $data->DiscountAmount + $data->ConvenienceAmount + $data->AdmissionAmount;
        $data->SubtotalAmountBase = $cur->toBaseAmount($data->SubtotalAmount, $data->RateAmount);
        $data->TotalAmount = $data->SubtotalAmount + $data->AdditionalAmount - $data->DiscountPercentageAmount - $data->DiscountAmount + $data->ConvenienceAmount + $data->AdmissionAmount;
        $data->TotalAmountBase = $cur->toBaseAmount($data->TotalAmount, $data->RateAmount);
        $rate = $data->RateAmount;
        $data->DiscountAmountBase = $cur->toBaseAmount($data->DiscountAmount, $rate) ?: 0;
        $data->ConvenienceAmountBase = $cur->toBaseAmount($data->ConvenienceAmount, $rate) ?: 0;
        $data->AdditionalAmountBase = $cur->toBaseAmount($data->AdditionalAmount, $rate) ?: 0;
        $data->AdmissionAmountBase = $cur->toBaseAmount($data->AdmissionAmount, $rate) ?: 0;
        $data->TotalAmountBase = $cur->toBaseAmount($data->TotalAmount, $rate) ?: 0;
        $data->save();
        $data->fresh();
    }

    public function action(PointOfSale $data)
    {
        $url = 'pointofsale';
        $actionEntry = [
            'name' => 'Change to ENTRY',
            'icon' => 'UnlockIcon',
            'type' => 'confirm',
            'post' => $url.'/{Oid}/unpost',
        ];
        $actionPaid = [
            'name' => 'Change to PAID',
            'icon' => 'DollarSignIcon',
            'type' => 'confirm',
            'post' => $url.'/{Oid}/paid',
        ];
        $actionCancelled = [
            'name' => 'Change to Cancelled',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url.'/{Oid}/cancelled',
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPaid;
                break;
            case "entry":
                $return[] = $actionPaid;
                // $return[] = $actionAddPartialPurchaseOrder;
                break;
            case "posted":
                $return[] = $actionEntry;
                $return[] = $actionCancelled;
                break;
            case "paid":
                $return[] = $actionEntry;
                $return[] = $actionCancelled;
                break;
        }
        return $return;
    }

    public function entry(PointOfSale $data)
    {
        try {
            $this->posStatusService->setEntry($data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function paid(PointOfSale $data)
    {
        try {
            $this->posStatusService->setPaid($data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function completed(PointOfSale $data)
    {
        try {
            $this->posStatusService->setCompleted($data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function cancelled(PointOfSale $data)
    {
        try {
            $this->posStatusService->setCancelled($data);
            $data->CancelledDate = Carbon::now();
            $data->save();
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function upload(Request $request, $Oid = null)
    {
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        try {
            DB::transaction(function () use ($input, $request, &$data, $Oid) {

                // $request->file('POSEticketFile')->storeAs(
                //     '', $data->FileName);

                $files = $request->file('POSEticketFile');
                foreach ($files as $key => $value) {
                    $eticket = $this->posETicketService->create($value, [
                        'PointOfSale' => $Oid,
                        'Item' => null,
                        'CostPrice' => null,
                        'DateExpiry' => null,
                    ]);
                    $result[] = $eticket->Oid;
                }
            });

            $data = PointOfSale::with('Details')->with('ETickets')->with('TravelDetails')->with('Logs')->findOrFail($Oid);
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

    public function deleteEticket(Request $request, $Oid = null)
    {
        try {
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = POSETicketUpload::findOrFail($Oid);
                $data->delete();
            });

            return response()->json(
                null,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function generateRole(PointOfSale $data, $role = null, $action = null)
    {
        if ($data instanceof PointOfSale) {
            $status = $data->StatusObj;
        } else {
            $status = $data->Status;
        }
        if (!$role) {
            $role = $this->roleService->list('PointOfSale');
        }
        if (!$action) {
            $action = $this->roleService->action('PointOfSale');
        }

        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->roleService->isAllowDelete($data->StatusObj, $role->IsEdit),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Cancel' => $this->roleService->isAllowCancel($data->StatusObj, $action->Cancel),
            'Complete' => $this->roleService->isAllowComplete($data->StatusObj, $action->Complete),
            'Entry' => $this->roleService->isAllowEntry($data->StatusObj, $action->Entry),
            'Paid' => $this->roleService->isAllowPaid($data->StatusObj, $action->Paid),
            // 'Post' => $this->roleService->isAllowPost($data->StatusObj, $action->Posted),
            'ViewJournal' => $this->roleService->isPosted($data->StatusObj, 1),
            'ViewStock' => $this->roleService->isPosted($data->StatusObj, 1),
            'Print' => $this->roleService->isPosted($data->StatusObj, 1),
        ];
    }

    public function repostPerDate(Request $request)
    {
        try {
            $user = Auth::user();
            $company = $user->CompanyObj;
            $i = 1;

            $criteriaPOS = "WHERE Company ='{$company->Oid}' AND PointOfSale IS NOT NULL AND Date >='{$request->input('datefrom')}' AND Date <='{$request->input('dateto')}'";
            $criteriaPOSSession = "WHERE Company ='{$company->Oid}' AND POSSession IS NOT NULL AND Date >='{$request->input('datefrom')}' AND Date <='{$request->input('dateto')}'";
            DB::delete("DELETE FROM accjournal " . $criteriaPOS);
            DB::delete("DELETE FROM accjournal " . $criteriaPOSSession);
            DB::delete("DELETE FROM trdtransactionstock " . $criteriaPOS);
            DB::delete("DELETE FROM trdtransactionstock " . $criteriaPOSSession);

            $dateFrom = Carbon::parse($request->datefrom)->toDateString();
            $dateUntil = Carbon::parse($request->dateto)->addDays(1)->toDateString();
            if ($company->IsUsingPOSEnterprise) {
                $data = PointOfSale::where('Date', '>=', $dateFrom)->where('Date', '<=', $dateUntil)
                    ->whereNull('GCRecord')->get();
                logger($data->count());
                foreach ($data as $row) {
                    logger($i . ' ' . $row->Code . ' ' . $row->Oid);
                    $this->salesPosService->post($row->Oid);
                    $i = $i + 1;
                }
            } else {
                $data = POSSession::where('Date', '>=', $dateFrom)->where('Date', '<=', $dateUntil)
                    ->whereNull('GCRecord')->get();
                logger($data->count());
                foreach ($data as $row) {
                    logger($i . ' ' . $row->Code . ' ' . $row->Oid);
                    $this->salesPosSessionService->post($row->Oid);
                    $i = $i + 1;
                }
            }

            return response()->json(
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function searchSession(Request $request)
    {
        $result = [];
        $data = POSSession::where('Date', $request->input('date'))->whereNull('Ended')->get();
        foreach ($data as $row) {
            $result[] = [
                'Session' => $row->Oid,
                'UserName' => $row->UserObj ? $row->UserObj->UserName : null,
                'WarehouseName' => $row->WarehouseObj ? $row->WarehouseObj->Code : null,
                'DateCreated' => Carbon::parse($row->CreatedAt)->format('Y-m-d'),
                'DateEnded' => Carbon::parse($row->Ended)->format('Y-m-d')
            ];
        }
        return $result;
    }

    public function changeSession(Request $request)
    {
        $possession = POSSession::findOrFail($request->input('possession'));
        $warehouse = $possession->Warehouse;

        $pos = PointOfSale::findOrFail($request->input('pos'));
        $possessionNow = $pos->POSSession;
        $pos->POSSession = $possession->Oid;
        $pos->POSSessionPrevious = $possessionNow;
        $pos->Warehouse = $warehouse;
        $pos->save();

        return response()->json(
            $pos,
            Response::HTTP_OK
        );
    }

    public function fieldsReturn()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w' => 200, 'h' => 0, 'dis' => 1, 'n' => 'Code'];
        $fields[] = ['w' => 200, 'h' => 0, 'dis' => 1, 'n' => 'Date'];
        $fields[] = ['w' => 200, 'h' => 0, 'dis' => 1, 'n' => 'CustomerName'];
        $fields[] = ['w' => 200, 'h' => 0, 'hideInput' => 1, 'n' => 'TableName'];
        $fields[] = ['w' => 200, 'h' => 0, 'hideInput' => 1, 'n' => 'ProjectName'];
        $fields[] = ['w' => 200, 'h' => 0, 'dis' => 1, 'n' => 'CurrencyName'];
        $fields[] = ['w' => 200, 'h' => 0, 'dis' => 1, 'n' => 'TotalAmount'];
        $fields[] = ['w' => 200, 'h' => 1, 'hideInput' => 1, 'n' => 'StatusName'];
        $fields[] = ['w' => 200, 't' => 'textarea', 'n' => 'Note'];
        $fields[] = ['w' => 200, 't' => 'text', 'n' => 'Username'];
        $fields[] = ['w' => 200, 't' => 'password', 'n' => 'Password'];
        $fields[] = serverSideConfigField('Status');
        return $fields;
    }

    public function configReturn(Request $request)
    {
        return jsonConfig($this->fieldsReturn());
    }
    public function posreturnsearch(Request $request)
    {
        try {
            $user = Auth::user();
            $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first();
            if (!$session) {
                return null;
            }
            if ($request->has('datefrom')) {
                $datefrom = $request->input('datefrom');
            }
            if ($request->has('dateuntil')) {
                $dateuntil = $request->input('dateuntil');
            }
            $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first()->Oid;
            if (!$session) {
                throw new \Exception('Failed to retrieve session');
            }
            $data = PointOfSale::with(['CurrencyObj', 'CustomerObj', 'StatusObj', 'POSTableObj', 'UserObj', 'EmployeeObj', 'ProjectObj'])
                ->whereHas('StatusObj', function ($query) {
                    $query->whereIn('Code', ['paid', 'complete']);
                })->whereNull('GCRecord');

            if ($request->has('code')) {
                $data->where('Code', 'LIKE', '%' . $request->input('code') . '%');
            }
            if ($datefrom == $dateuntil) {
                $data = $data->where('Date', '>=', Carbon::parse($datefrom)->toDateString());
                $data = $data->where('Date', '<=', Carbon::parse($dateuntil)->addDay(1)->toDateString());
            } else {
                $data = $data->where('Date', '>=', Carbon::parse($datefrom)->toDateString());
                $data = $data->where('Date', '<=', Carbon::parse($dateuntil)->toDateString());
            }
            if ($request->has('user')) {
                $data->where('User', $request->input('user'));
            }
            if ($request->has('customer')) {
                $data->where('Customer', $request->input('customer'));
            }
            if ($request->has('employee')) {
                $data->where('Employee', $request->input('employee'));
            }
            if ($request->has('postable')) {
                $data->where('POSTable', $request->input('postable'));
            }
            if ($request->has('project')) {
                $data->where('Project', $request->input('project'));
            }
            if ($request->has('currency')) {
                $data->where('Currency', $request->input('currency'));
            }
            if ($request->has('amount')) {
                $data->where('TotalAmount', $request->input('amount'));
            }
            if ($request->has('status')) {
                $data->where('Status', $request->input('status'));
            }

            $data = $data->whereNull('PointOfSaleReturn')->orderBy('Date', 'Desc')->get();

            $result = [];
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'Date' => Carbon::parse($row->Date)->format('Y-m-d'),
                    'CustomerName' => $row->CustomerObj ? $row->CustomerObj->Name . ' - ' . $row->CustomerObj->Code : null,
                    'EmployeeName' => $row->EmployeeObj ? $row->EmployeeObj->Name . ' - ' . $row->EmployeeObj->Code : null,
                    'TableName' => $row->POSTableObj ? $row->POSTableObj->Name . ' - ' . $row->POSTableObj->Code : null,
                    'ProjectName' => $row->ProjectObj ? $row->ProjectObj->Name . ' - ' . $row->ProjectObj->Code : null,
                    'CurrencyName' => $row->CurrencyObj ? $row->CurrencyObj->Code : null,
                    'TotalAmount' => number_format($row->TotalAmount, $row->CurrencyObj->Decimal),
                    'StatusName' => $row->StatusObj ? $row->StatusObj->Name : null,
                    'Role' => [
                        'IsReturn' => 1
                    ]
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function posreturn(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        $userAdmin = User::where('UserName', $request->UserName)->first();
        if ($userAdmin == null) {
            return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        }
        $password = $request->Password;
        $content = $this->httpService->post(
            '/portal/api/auth/login',
            [
                'UserName' => $userAdmin->UserName,
                'Password' => $password,
                'Company' => $userAdmin->Company,
            ]
        );
        if (!$content) {
            return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        }
        $query = "SELECT rmc.Oid, rm.Role, rmc.Modules, rmc.Action, rmc.IsEnable
                FROM userusers_roleroles ur
                LEFT OUTER JOIN rolemodules rm ON rm.Role = ur.Roles
                LEFT OUTER JOIN rolemodulescustom rmc ON rmc.Role = ur.Roles AND rm.Modules = rmc.Modules
                LEFT OUTER JOIN user u ON u.Oid = ur.Users
                WHERE rm.Oid IS NOT NULL AND u.UserName = '" . $userAdmin->UserName . "' AND rmc.Modules='POS' AND rmc.Action='Cancel'
                GROUP BY rmc.Oid, rm.Role, rmc.Modules, rmc.Action";
        $check = DB::select($query);
        if ($check == null) {
            return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        }
        if (!$check[0]->IsEnable) {
            return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        }

        $data = PointOfSale::findOrFail($request->Oid);
        $checkReturn = PointOfSale::where('PointOfSaleReturn', $data->Oid)->first();
        if ($checkReturn) {
            return response()->json('Data already return', Response::HTTP_NOT_FOUND);
        }
        $pos = new PointOfSale();
        try {
            DB::transaction(function () use ($request, $pos, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($data->getAttributes() as $field => $key) {
                    if (in_array($field, $disabled)) {
                        continue;
                    }
                    $pos->{$field} = $data->{$field};
                }
                $user = Auth::user();
                $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first();
                $pos->Code = $data->Code . '-ret';
                $pos->Note = $request->Note;
                $pos->Date = Carbon::now();
                $pos->PointOfSaleType = PointOfSaleType::where('Code', 'SRETURN')->first()->Oid;
                $pos->PointOfSaleReturn = $data->Oid;
                $pos->Status = Status::posted()->first()->Oid;
                $pos->POSSession = $session->Oid;
                $pos->save();


                $details = [];
                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $row) {
                        $details[] = new PointOfSaleDetail([
                            'Item' => $row->Item,
                            'ItemUnit' => $row->ItemUnit,
                            'Quantity' => $row->Quantity,
                            'Amount' => $row->Amount,
                            'AmountBase' => $row->AmountBase,
                            'AmountCost' => $row->Amount,
                            'DiscountAmount' => $row->DiscountAmount,
                            'DiscountPercentage' => $row->DiscountPercentage,
                            'DiscountPercentageAmount' => $row->DiscountPercentageAmount
                        ]);
                    }
                    $pos->Details()->saveMany($details);
                }

                if (!$pos) {
                    throw new \Exception('Data is failed to be saved');
                }

                $this->salesPosService->postReturn($pos->Oid);
            });


            return response()->json(
                $pos,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function autocomplete(Request $request)
    {
        $type = $request->input('type') ?: 'combo';
        $term = $request->term;
        $user = Auth::user();
        
        $data = PointOfSale::whereNull('GCRecord');
        if ($request->has('businesspartner')) {
            $data->where('Customer', $request->input('businesspartner'));
        }
        if ($request->has('company')) {
            $data->where('Company', $request->input('company'));
        }
        if ($request->has('status')) {
            $data->whereHas('StatusObj', function ($query) use ($request) {
                $query->where('Code', $request->input('status'));
            });
        }
        $data->where(function ($query) use ($term) {
            $query->where('Code', 'LIKE', '%'.$term.'%')
            ->orWhere('Date', 'LIKE', '%'.$term.'%');
        });
        if ($user->BusinessPartner) {
            $data = $data->where('Oid', $user->BusinessPartner);
        }
        $data = $data->orderBy('Code')->take(10)->get();

        $result = [];
        foreach ($data as $row) {
            $result[] = [
                "Oid" => $row->Oid,
                "Name" => $row->Code.' - '.Carbon::parse($row->Date)->format('Y-m-d'),
            ];
        }

        return $result;
    }
}
