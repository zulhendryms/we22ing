<?php

namespace App\AdminApi\POS\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Currency;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\PointOfSaleDetail;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Internal\Entities\PointOfSaleType;
use App\Core\Internal\Entities\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\POS\Services\POSStatusService;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\POS\Services\POSETicketService;
use App\Core\Accounting\Services\SalesPOSService;
use App\Core\Accounting\Services\SalesPOSSessionService;
use App\Core\POS\Entities\POSSession;
use App\Core\Security\Entities\User;
use App\Core\Base\Services\HttpService;
use Carbon\Carbon;
use Validator;

class POSController extends Controller
{
    protected $posETicketService;
    protected $posStatusService;
    protected $roleService;
    protected $salesPosService;
    protected $salesPosSessionService;
    protected $httpService;

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
    }
    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = serverSideConfigField('Code');
        $fields[] = serverSideConfigField('Date');
        $fields[] = ['w'=> 200, 'n'=>'Customer', 'f'=>'bp.Name'];
        $fields[] = serverSideConfigField('Currency');
        $fields[] = ['w'=> 200, 'n'=>'TotalAmount'];
        $fields[] = ['w'=> 200, 'n'=>'POSTable', 'f'=>'t.Name'];
        $fields[] = ['w'=> 200, 'n'=>'Warehouse', 'f'=>'w.Name'];
        $fields[] = ['w'=> 200, 'n'=>'User', 'f'=>'u.UserName'];
        $fields[] = ['w'=> 200, 'n'=>'Employee', 'f'=>'e.Name'];
        $fields[] = serverSideConfigField('Status');
        return $fields;
    }

    public function config(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields());
        foreach ($fields as &$row) { //combosource
            if ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
        }
        return $fields;
    }
    public function list(Request $request) {
        $user = Auth::user();  

        $fields = $this->fields();
        $data = DB::table('pospointofsale as data')
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->leftJoin('mstcurrency AS c', 'c.Oid', '=', 'data.Currency')
            ->leftJoin('sysstatus AS s', 's.Oid', '=', 'data.Status')
            ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'data.Customer')
            ->leftJoin('postable AS t', 't.Oid', '=', 'data.POSTable')
            ->leftJoin('mstwarehouse AS w', 'w.Oid', '=', 'data.Warehouse')
            ->leftJoin('mstemployee AS e', 'e.Oid', '=', 'data.Employee')
            ->leftJoin('user AS u', 'u.Oid', '=', 'data.User')
            ;

        // filter businesspartnergroupuser
        $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
        if ($businessPartnerGroupUser->count() > 0) $data->whereIn('bp.BusinessPartnerGroup', $businessPartnerGroupUser);

        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'pospointofsale','Date');
        $role = $this->roleService->list('POS');
        $action = $this->roleService->action('POS');
        foreach($data as $row) $row->Role = $this->roleService->generateRole($row, $role, $action);
        return $this->crudController->jsonListReturn($data, $fields);
    }
    
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = PointOfSale::whereNull('GCRecord');
            if ($type == 'list') $data->with(['CurrencyObj','CustomerObj','StatusObj','POSTableObj','UserObj']);
            
            if ($request->has('date')) {
                $data = $data
                    ->where('Date','>=', Carbon::parse($request->date)->startOfMonth()->toDateString())
                    ->where('Date','<', Carbon::parse($request->date)->startOfMonth()->addMonths(1)->toDateString());
            }
            $bp = BusinessPartnerGroup::findOrFail($user->BusinessPartnerObj->BusinessPartnerGroup);

            $role = $user->BusinessPartner ? $user->BusinessPartnerObj->BusinessPartnerGroupObj->BusinessPartnerRoleObj->Code : "Cash";
            if ($user->CompanyObj->BusinessPartner == $user->BusinessPartner) $data = $data->whereNull('GCRecord');
            elseif ($role == 'Customer' || $role == 'Agent') $data = $data->where('Customer', $user->BusinessPartner);
            elseif ($role == 'Supplier') $data = $data->where('Supplier', $user->BusinessPartner);

            $data = $data->orderBy('Date','Desc')->get();

            $result = [];
            $role = $this->roleService->list('POS');
            $action = $this->roleService->action('POS');
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'Date' => Carbon::parse($row->Date)->format('Y-m-d'),
                    'Source' => $row->Source,
                    'TotalAmount' => number_format($row->TotalAmount,$row->CurrencyObj->Decimal),
                    'CurrencyName' => $row->CurrencyObj ? $row->CurrencyObj->Code : null,
                    'CustomerName' => $row->CustomerObj ? $row->CustomerObj->Name.' - '.$row->CustomerObj->Code : null,
                    'TableName' => $row->POSTableObj ? $row->POSTableObj->Name.' - '.$row->POSTableObj->Code : null,
                    'StatusName' => $row->StatusObj ? $row->StatusObj->Name : null,
                    'Role' => $this->generateRole($row, $role, $action)
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
    
    public function show(PointOfSale $data)
    { 
        try {            
            $data = PointOfSale::with('Details','ETickets','TravelDetails','Logs')->with([
                'SupplierObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'CustomerObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'Details.ItemObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'TravelDetails.ItemObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'PointOfSaleTypeObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'POSTableObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'EmployeeObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'Employee2Obj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'ProjectObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'UserObj' => function ($query) {$query->addSelect('Oid', 'UserName','Name');},
                'CurrencyObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'POSSessionObj' => function ($query) {$query->addSelect('Oid');},
                'StatusObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'PaymentMethodObj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'PaymentMethod2Obj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'PaymentMethod3Obj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'PaymentMethod4Obj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                'PaymentMethod5Obj' => function ($query) {$query->addSelect('Oid', 'Code','Name');},
                ])->findOrFail($data->Oid);
            $data->Role = $this->generateRole($data);
            // $data = (new PointOfSaleResource($data))->type('detail');
            if ($data->POSSession) {
                $session = POSSession::with('UserObj')->findOrFail($data->POSSession);
                $data->POSSessionObj->Name = Carbon::parse($session->Date)->format('Y-m-d').' '.$session->UserObj->UserName;
            }
            if (!$data->SupplierObj && $data->Supplier) {
                $tmp = DB::select("SELECT Oid, Code, Name FROM mstbusinesspartner WHERE Oid='{$data->Supplier}'");
                if ($tmp) $data->SupplierObj = $tmp[0];
            }
            if (!$data->CustomerObj && $data->Customer) {
                $tmp = DB::select("SELECT Oid, Code, Name FROM mstbusinesspartner WHERE Oid='{$data->Customer}'");
                if ($tmp) $data->CustomerObj = $tmp[0];
            }
            if (!$data->POSSessionObj && $data->POSSession) {
                $tmp = DB::select("SELECT Oid, Code, Date FROM possession WHERE Oid='{$data->POSSession}'");
                if ($tmp) $data->POSSessionObj = $tmp[0];
            }
            if (!$data->PaymentMethodObj && $data->PaymentMethod) {
                $tmp = DB::select("SELECT Oid, Code, Name FROM mstpaymentmethod WHERE Oid='{$data->PaymentMethod}'");
                if ($tmp) $data->PaymentMethodObj = $tmp[0];
            }
            return $data;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {      
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        // $dataArray = object_to_array($request);
        // return response()->json(
        //     $request, Response::HTTP_OK
        // );
        // if ($request->AdditionalAmount < 0 ) throw new \Exception("Additional amount cannot below 0");
        // if ($request->DiscountAmount < 0 ) throw new \Exception("Discount amount cannot below 0");
        // if ($request->PrepaidAmount < 0 ) throw new \Exception("Prepaid amount cannot below 0");
        // if ($request->RateAmount < 0 ) throw new \Exception("Rate amount cannot below 0");
        // if ($request->TransferAmount < 0 ) throw new \Exception("Transfer amount cannot below 0");        
        // checkperiod
        // $messsages = array(
        //     'Code.required'=>__('_.Code').__('error.required'),
        //     'Code.max'=>__('_.Code').__('error.max'),
        //     'Date.required'=>__('_.Date').__('error.required'),
        //     'Date.date'=>__('_.Date').__('error.date'),
        //     'Currency.required'=>__('_.Currency').__('error.required'),
        //     'Currency.exists'=>__('_.Currency').__('error.exists'),
        //     'Status.required'=>__('_.Status').__('error.required'),
        //     'Status.exists'=>__('_.Status').__('error.exists'),
        //     'Customer.required'=>__('_.Customer').__('error.required'),
        //     'Customer.exists'=>__('_.Customer').__('error.exists'),

        // );
        // $rules = array(
        //     'Code' => 'required|max:255',
        //     'Date' => 'required|date',
        //     'Currency' => 'required|exists:mstcurrency,Oid',
        //     'Status' => 'required|exists:sysstatus,Oid',
        //     'Customer' => 'required|exists:mstbusinesspartner,Oid',
        // );
        
        // $validator = Validator::make($dataArray, $rules,$messsages);

        // if ($validator->fails()) {
        //     return response()->json(
        //         $validator->messages(),
        //         Response::HTTP_UNPROCESSABLE_ENTITY
        //     );
        // }

        try {            
            DB::transaction(function () use ($request, $Oid, &$data) {
                if (!$Oid) $data = new PointOfSale();
                else $data = PointOfSale::findOrFail($Oid);
                $disabled = disabledFieldsForEdit();
                $company = Auth::user()->CompanyObj;
                if (!$Oid) {
                    if (!isset($request->Code)) $request->Code = '<<Auto>>';
                    if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                    if (!isset($request->Date)) $request->Date = now();
                    if (!isset($request->Source)) $request->Source = 'Backend';
                    if (!isset($request->Currency)) $request->Currency = $company->Currency;
                    if (!isset($request->Customer)) $request->Customer = $company->CustomerCash;
                    if (!isset($request->Warehouse)) $request->Warehouse = $company->Warehouse;
                    if (!isset($request->Status)) $request->Status = Status::entry()->first()->Oid;
                    if (!isset($request->User)) $request->User = Auth::user()->Oid;
                    $cur = Currency::findOrFail($request->Currency);
                    if (!isset($request->RateAmount)) $request->RateAmount = $cur->getRate($request->Date)->MidRate;
                }
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $cur = Currency::findOrFail($data->Currency);
                $customer = BusinessPartner::findOrFail($data->Customer);
                $data->save();
                $this->calcTotal($data);
                
                $data->CustomerName = $customer->Name;
                $data->CurrencyName = $cur->Name;
                $data->Role = $this->generateRole($data);
            });
            // $data = new PointOfSaleResource($data);
            return response()->json(
                $data, Response::HTTP_CREATED
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
            $data = TravelTransactionDetail::where('TravelTransaction',$pos);
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
            $data = PointOfSaleDetail::where('PointOfSale',$pos);
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
            $data = PointOfSale::where('Oid',$pos)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $cur = Currency::findOrFail($data->Currency);
                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $rowdb) {
                        $found = false;               
                        foreach ($request->Details as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = PointOfSaleDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }

                $totalAmount = 0;
                if($request->Details) {
                    $details = [];
                    foreach ($request->Details as $row) {
                        if ($row->DiscountPercentage ?: 0 > 0) $row->DiscountPercentageAmount = (($row->Quantity * $row->Amount) * ($row->DiscountPercentage ?: 0)) / 100;
                        else $row->DiscountPercentageAmount = 0;
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
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            logger(7);
                            $detail = TravelTransactionDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }

                if($request->TravelDetails) {
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
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function calcTotal(PointOfSale $data) {
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


    public function destroy(PointOfSale $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                // $data->Details()->delete();
                $data->ETickets()->delete();
                // $data->delete();
                $gcrecord = now()->format('ymdHi');
                $data->GCRecord = $gcrecord;
                $data->save();
                foreach ($data->Details as $row) {
                    $row->GCRecord = $gcrecord;
                    $row->save();
                }
            });
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function upload(Request $request, $Oid = null)
    {        
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        
        try {            
            DB::transaction(function () use ( $input, $request, &$data, $Oid) {
                
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
                $data, Response::HTTP_CREATED
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
            DB::transaction(function () use ( $request, &$data, $Oid) {                
                $data = POSETicketUpload::findOrFail($Oid);
                $data->delete();
            });

            return response()->json(
                null, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function generateRole(PointOfSale $data, $role = null, $action = null) {
        if ($data instanceof PointOfSale) $status = $data->StatusObj; else $status = $data->Status;
        if (!$role) $role = $this->roleService->list('POS');
        if (!$action) $action = $this->roleService->action('POS');

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
            DB::delete("DELETE FROM accjournal ". $criteriaPOS);
            DB::delete("DELETE FROM accjournal ". $criteriaPOSSession);
            DB::delete("DELETE FROM trdtransactionstock ". $criteriaPOS);
            DB::delete("DELETE FROM trdtransactionstock ". $criteriaPOSSession);
           
            $dateFrom = Carbon::parse($request->datefrom)->toDateString();
            $dateUntil = Carbon::parse($request->dateto)->addDays(1)->toDateString();
            if($company->IsUsingPOSEnterprise){
                $data = PointOfSale::where('Date','>=', $dateFrom)->where('Date','<=', $dateUntil)
                    ->whereNull('GCRecord')->get();
                logger($data->count());
                foreach($data as $row) {
                    logger($i.' '.$row->Code.' '.$row->Oid);
                    $this->salesPosService->post($row->Oid);
                    $i = $i + 1;
                }
            } else {
                $data = POSSession::where('Date','>=', $dateFrom)->where('Date','<=', $dateUntil)
                    ->whereNull('GCRecord')->get();
                logger($data->count());
                foreach($data as $row) {
                    logger($i.' '.$row->Code.' '.$row->Oid);
                    $this->salesPosSessionService->post($row->Oid);
                    $i = $i + 1;
                }
            }

            return response()->json(
                $data, Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function searchSession(Request $request){
        $result = [];
        $data = POSSession::where('Date',$request->input('date'))->whereNull('Ended')->get();
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

    public function changeSession(Request $request){
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

    public function fieldsReturn() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 200, 'h'=>0, 'dis'=>1, 'n'=>'Code'];
        $fields[] = ['w'=> 200, 'h'=>0, 'dis'=>1, 'n'=>'Date'];
        $fields[] = ['w'=> 200, 'h'=>0, 'dis'=>1, 'n'=>'CustomerName'];
        $fields[] = ['w'=> 200, 'h'=>0, 'hideInput'=>1, 'n'=>'TableName'];
        $fields[] = ['w'=> 200, 'h'=>0, 'hideInput'=>1, 'n'=>'ProjectName'];
        $fields[] = ['w'=> 200, 'h'=>0, 'dis'=>1, 'n'=>'CurrencyName'];
        $fields[] = ['w'=> 200, 'h'=>0, 'dis'=>1, 'n'=>'TotalAmount'];
        $fields[] = ['w'=> 200, 'h'=>1, 'hideInput'=>1, 'n'=>'StatusName'];
        $fields[] = ['w'=> 200, 't'=>'textarea', 'n'=>'Note'];
        $fields[] = ['w'=> 200, 't'=>'text', 'n'=>'Username'];
        $fields[] = ['w'=> 200, 't'=>'password', 'n'=>'Password'];
        $fields[] = serverSideConfigField('Status');
        return $fields;
    }

    public function configReturn(Request $request) {
        return $this->crudController->jsonConfig($this->fieldsReturn());
    }
    public function posreturnsearch(Request $request)
    {
        try {
            $user = Auth::user();
            $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first();
            if (!$session) return null;
            if ($request->has('datefrom')) $datefrom = $request->input('datefrom');
            if ($request->has('dateuntil')) $dateuntil = $request->input('dateuntil');
            $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first()->Oid;
            if (!$session) throw new \Exception('Failed to retrieve session');
            $data = PointOfSale::with(['CurrencyObj','CustomerObj','StatusObj','POSTableObj','UserObj','EmployeeObj','ProjectObj'])
            ->whereHas('StatusObj', function ($query) {
                $query->whereIn('Code', ['paid','complete']);
            })->whereNull('GCRecord');

            if ($request->has('code')) $data->where('Code','LIKE','%'.$request->input('code').'%');
            if ($datefrom == $dateuntil){
                $data = $data->where('Date','>=', Carbon::parse($datefrom)->toDateString());
                $data = $data->where('Date','<=', Carbon::parse($dateuntil)->addDay(1)->toDateString());
            }else{
                $data = $data->where('Date','>=', Carbon::parse($datefrom)->toDateString());
                $data = $data->where('Date','<=', Carbon::parse($dateuntil)->toDateString());
            }
            if ($request->has('user')) $data->where('User', $request->input('user'));
            if ($request->has('customer')) $data->where('Customer', $request->input('customer'));
            if ($request->has('employee')) $data->where('Employee', $request->input('employee'));
            if ($request->has('postable')) $data->where('POSTable', $request->input('postable'));
            if ($request->has('project')) $data->where('Project', $request->input('project'));
            if ($request->has('currency')) $data->where('Currency', $request->input('currency'));
            if ($request->has('amount')) $data->where('TotalAmount', $request->input('amount'));
            if ($request->has('status')) $data->where('Status', $request->input('status'));

            $data = $data->whereNull('PointOfSaleReturn')->orderBy('Date','Desc')->get();

            $result = [];
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'Date' => Carbon::parse($row->Date)->format('Y-m-d'),
                    'CustomerName' => $row->CustomerObj ? $row->CustomerObj->Name.' - '.$row->CustomerObj->Code : null,
                    'EmployeeName' => $row->EmployeeObj ? $row->EmployeeObj->Name.' - '.$row->EmployeeObj->Code : null,
                    'TableName' => $row->POSTableObj ? $row->POSTableObj->Name.' - '.$row->POSTableObj->Code : null,
                    'ProjectName' => $row->ProjectObj ? $row->ProjectObj->Name.' - '.$row->ProjectObj->Code : null,
                    'CurrencyName' => $row->CurrencyObj ? $row->CurrencyObj->Code : null,
                    'TotalAmount' => number_format($row->TotalAmount,$row->CurrencyObj->Decimal),
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
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        $userAdmin = User::where('UserName',$request->UserName)->first();
        if ($userAdmin == null) return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        $password = $request->Password;
        $content = $this->httpService->post(
            '/portal/api/auth/login',
            [
                'UserName' => $userAdmin->UserName,
                'Password' => $password,
                'Company' => $userAdmin->Company,
            ]
        );
        if (!$content) return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        $query="SELECT rmc.Oid, rm.Role, rmc.Modules, rmc.Action, rmc.IsEnable
                FROM userusers_roleroles ur
                LEFT OUTER JOIN rolemodules rm ON rm.Role = ur.Roles
                LEFT OUTER JOIN rolemodulescustom rmc ON rmc.Role = ur.Roles AND rm.Modules = rmc.Modules
                LEFT OUTER JOIN user u ON u.Oid = ur.Users
                WHERE rm.Oid IS NOT NULL AND u.UserName = '".$userAdmin->UserName."' AND rmc.Modules='POS' AND rmc.Action='Cancel'
                GROUP BY rmc.Oid, rm.Role, rmc.Modules, rmc.Action";
        $check = DB::select($query);
        if ($check == null) return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
        if (!$check[0]->IsEnable) return response()->json('User is invalid', Response::HTTP_NOT_FOUND);
    
        $data = PointOfSale::findOrFail($request->Oid);
        $checkReturn = PointOfSale::where('PointOfSaleReturn',$data->Oid)->first();
        if($checkReturn) return response()->json('Data already return', Response::HTTP_NOT_FOUND);
        $pos = new PointOfSale();
        try {            
            DB::transaction(function () use ($request,$pos, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($data->getAttributes() as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $pos->{$field} = $data->{$field};
                }
                $user = Auth::user();
                $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first();
                $pos->Code = $data->Code.'-ret';
                $pos->Note = $request->Note;
                $pos->Date = Carbon::now();
                $pos->PointOfSaleType = PointOfSaleType::where('Code','SRETURN')->first()->Oid;
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

                if(!$pos) throw new \Exception('Data is failed to be saved');

                $this->salesPosService->postReturn($pos->Oid);
            });

            
            return response()->json(
                $pos, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
