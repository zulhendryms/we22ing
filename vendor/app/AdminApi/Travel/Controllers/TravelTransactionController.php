<?php

namespace App\AdminApi\Travel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Currency;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\POSETicketLog;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Trading\Entities\SalesInvoiceDetail;
use App\Core\POS\Resources\PointOfSaleResource;
use App\Core\Internal\Entities\PointOfSaleType;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\Item;
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
use App\Core\Travel\Entities\TravelTransaction;
use App\Core\Travel\Entities\TravelTransactionFlight;
use App\Core\Travel\Entities\TravelTemplateNote;
use App\Core\Travel\Entities\TravelTransactionPassenger;
use App\Core\Travel\Entities\TravelTransactionItinerary;
use App\Core\Travel\Entities\TravelItemTourPackageItinerary;
use App\Core\POS\Entities\ETicket;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Travel\Entities\TravelType;
use Carbon\Carbon;
use App\Core\Internal\Services\AuditService;
use App\Core\Base\Services\TravelAPIService;
use App\Core\Internal\Services\AutoNumberService;
use Validator;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TravelTransactionController extends Controller
{
    protected $posETicketService;
    protected $posStatusService;
    protected $roleService;
    protected $salesPosService;
    protected $salesPosSessionService;
    protected $httpService;
    private $auditService;
    private $autoNumberService;
    private $travelAPIService;
    private $crudController;

    public function __construct(
        POSStatusService $posStatusService,
        POSETicketService $posETicketService,
        RoleModuleService $roleService,
        SalesPOSService $salesPosService,
        SalesPOSSessionService $salesPosSessionService,
        HttpService $httpService, AuditService $auditService,
        TravelAPIService $travelAPIService,
        AutoNumberService $autoNumberService
    ) {
        $this->posStatusService = $posStatusService;
        $this->travelAPIService = $travelAPIService;
        $this->posETicketService = $posETicketService;
        $this->roleService = $roleService;
        $this->salesPosService = $salesPosService;
        $this->salesPosSessionService = $salesPosSessionService;
        $this->auditService = $auditService;
        $this->httpService = $httpService;
        $this->autoNumberService = $autoNumberService;
        $this->crudController = new CRUDDevelopmentController();
        $this->httpService
            // ->baseUrl(config('services.ezbmodule.url'))
            ->baseUrl('http://ezbpostest.ezbooking.co:888')
            ->json();
    }
    public function fields($ttype)
    {
        $fields = []; //f = 'FIELD, t = TITLE
        // $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w' => 0, 'n' => 'Oid', 'f'=>'data.Oid'];
        $fields[] = ['w' => 100, 'n' => 'Company', 'f'=>'Company.Code'];
        $fields[] = ['w' => 150, 'n' => 'Code', 'f'=>'data.Code'];
        $fields[] = ['w' => 120, 'n' => 'Date', 'f'=>'data.Date'];
        $fields[] = ['w' => 250, 'n' => 'Customer', 'f' => 'bp.Name'];
        $fields[] = ['w' => 70, 'n' => 'Currency', 'f' => 'c.Code', 'ol' => 'Cur'];
        $fields[] = ['w' => 120, 'n' => 'TotalAmount'];
        $fields[] = ['w' => 0, 'h'=>1, 'f' => 'tt.AmountTourFareTotal', 'n' => 'TourFare'];
        // if ($ttype == 'web') $fields[] = ['w' => 120, 'n' => 'TotalAmount'];
        // else $fields[] = ['w' => 120, 'f' => 'tt.AmountTourFareTotal', 'n' => 'TourFare'];
        $fields[] = serverSideConfigField('Status');
        $fields[] = ['w' => 150, 'n' => 'User', 'f' => 'u.UserName'];
        return $fields;
    }

    public function config(Request $request)
    {
        if ($request->input('form') == 'traveltransactiongit') $ttype = 'git';
        elseif ($request->input('form') == 'traveltransactionfit') $ttype = 'fit';
        elseif ($request->input('form') == 'traveltransactionoutbound') $ttype = 'outbound';
        else $ttype = 'web';
        $fields = $this->crudController->jsonConfig($this->fields($ttype));
        foreach ($fields as &$row) { //combosource
            if ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
        };        
        $fields[0]['cellRenderer'] = 'actionCell';
        $travelTypes = TravelType::whereNull('GCRecord')->get();
        $arr = [];
        foreach ($travelTypes as $t) {
            $arr[] = [
                'name' => 'New '.$t->Name,
                'icon' => 'PlusIcon',
                'type' => 'open_form',
                'url' => "traveltransaction/form?TravelTypeName=".$t->Code."&TravelType=".$t->Oid
            ];
        }
        $fields[0]['topButton'] = $arr;
        return $fields;
    }

    public function list(Request $request)
    {
        $ttype = 'Web';
        if ($request->has('form')) {
            if ($request->input('form') == 'git') $ttype = 'Git';
            elseif ($request->input('form') == 'fit') $ttype = 'Fit';
            elseif ($request->input('form') == 'outbound') $ttype = 'Outbound';
            else $ttype = 'Web';
        }
        if ($request->has('Type')) {
            if ($request->input('Type') == 'GIT') $ttype = 'GIT';
            elseif ($request->input('Type') == 'FIT') $ttype = 'FIT';
            elseif ($request->input('Type') == 'Outbound') $ttype = 'Outbound';
            else $ttype = 'Web';
        }
        $user = Auth::user();

        $fields = $this->fields($ttype);
        $data = DB::table('pospointofsale as data')
            ->leftJoin('mstcurrency AS c', 'c.Oid', '=', 'data.Currency')
            ->leftJoin('traveltransaction AS tt', 'tt.Oid', '=', 'data.Oid')
            ->leftJoin('trvtraveltype AS tty', 'tty.Oid', '=', 'tt.TravelType')
            ->leftJoin('sysstatus AS s', 's.Oid', '=', 'data.Status')
            ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'data.Customer')
            ->leftJoin('postable AS t', 't.Oid', '=', 'data.POSTable')
            ->leftJoin('mstwarehouse AS w', 'w.Oid', '=', 'data.Warehouse')
            ->leftJoin('mstemployee AS e', 'e.Oid', '=', 'data.Employee')
            ->leftJoin('user AS u', 'u.Oid', '=', 'data.User')
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->whereNull('data.GCRecord')
            ;
        if ($request->has('Company')) $data->where('data.Company', $request->input('Company'));
        if ($request->has('DateFrom') && $request->input('DateFrom') != 'null') $data->whereRaw("data.Date >= '".$request->input('DateFrom')."'");
        if ($request->has('DateTo') && $request->input('DateTo') != 'null') $data->whereRaw("data.Date <= '".$request->input('DateTo')."'");
        
        if ($ttype) $data =$data->where('tty.Code', strtoupper($ttype));
        
        // filter businesspartnergroupuser
        $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
        if ($businessPartnerGroupUser->count() > 0) $data->whereIn('bp.BusinessPartnerGroup', $businessPartnerGroupUser);
        
        $data = $this->crudController->jsonList($data, $fields, $request, 'pospointofsale', 'Date', 'Code', false);
        
        foreach ($data as $row) {
            if ($row->TourFare > 0) $row->TotalAmount = $row->TourFare;
            $row->Action = $this->action($row->Oid);
        }
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function presearch(Request $request) {        
        return [
            [
                'fieldToSave' => "Type",
                'type' => "combobox",
                'hiddenField'=> 'TypeName',
                'column' => "1/4",
                'source' => [],
                'store' => "",
                'default' => "Web",
                'source' => [
                    ['Oid' => 'Web', 'Name' => 'Web'],
                    ['Oid' => 'GIT', 'Name' => 'GIT'],
                    ['Oid' => 'FIT', 'Name' => 'FIT'],
                    ['Oid' => 'Outbound', 'Name' => 'Outbound'],
                ]
            ],
            [
                'fieldToSave' => 'Company',
                "hiddenField" => "CompanyName",
                'type' => 'combobox',
                'column' => '1/4',
                'validationParams' => 'required',
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
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = PointOfSale::whereNull('GCRecord');
            if ($type == 'list') $data->with(['CurrencyObj', 'CustomerObj', 'StatusObj', 'POSTableObj', 'UserObj']);

            if ($request->has('date')) {
                $data = $data
                    ->where('Date', '>=', Carbon::parse($request->date)->startOfMonth()->toDateString())
                    ->where('Date', '<', Carbon::parse($request->date)->startOfMonth()->addMonths(1)->toDateString());
            }
            $bp = BusinessPartnerGroup::findOrFail($user->BusinessPartnerObj->BusinessPartnerGroup);

            $role = $user->BusinessPartner ? $user->BusinessPartnerObj->BusinessPartnerGroupObj->BusinessPartnerRoleObj->Code : "Cash";
            if ($user->CompanyObj->BusinessPartner == $user->BusinessPartner) $data = $data->whereNull('GCRecord');
            elseif ($role == 'Customer' || $role == 'Agent') $data = $data->where('Customer', $user->BusinessPartner);
            elseif ($role == 'Supplier') $data = $data->where('Supplier', $user->BusinessPartner);

            $data = $data->orderBy('Date', 'Desc')->get();

            $result = [];
            $role = $this->roleService->list('POS');
            $action = $this->roleService->action('POS');
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'Date' => Carbon::parse($row->Date)->format('Y-m-d'),
                    'Source' => $row->Source,
                    'TotalAmount' => number_format($row->TotalAmount, $row->CurrencyObj->Decimal),
                    'CurrencyName' => $row->CurrencyObj ? $row->CurrencyObj->Code : null,
                    'CustomerName' => $row->CustomerObj ? $row->CustomerObj->Name . ' - ' . $row->CustomerObj->Code : null,
                    'TableName' => $row->POSTableObj ? $row->POSTableObj->Name . ' - ' . $row->POSTableObj->Code : null,
                    'StatusName' => $row->StatusObj ? $row->StatusObj->Name : null,
                    // 'Role' => $this->generateRole($row, $role, $action)
                ];
            }
            return $result;
            // return (new PointOfSaleCollection($data))->type($type);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSubDetail($data, $orderType) {
        $result = TravelTransactionDetail::with(['ItemObj','BusinessPartnerObj'])
            ->where('TravelTransaction', $data->Oid)
            ->whereIn('OrderType', $orderType)
            ->get();
        $result = dataReorder($result, 'CreatedAt');
        if ($result) {
            foreach ($result as $row) {
                $row->ItemName = $row->ItemObj ? $row->ItemObj->Name : null;
                $row->ItemContentName = $row->ItemContentObj ? $row->ItemContentObj->Name : null;
                $row->BusinessPartnerName = $row->BusinessPartnerObj ? $row->BusinessPartnerObj->Name : null;
                $row->TravelHotelRoomTypeName = $row->TravelHotelRoomTypeObj ? $row->TravelHotelRoomTypeObj->Name : null;
                $row->StatusName = $row->StatusObj ? $row->StatusObj->Name : null;

                $found = false;
                if (in_array("Hotel",$orderType)) $found = true;
                if (in_array("Transport",$orderType)) $found = true;
                if (in_array("Restaurant",$orderType)) $found = true;
                if ($found) {
                    $row->Action = [
                        [
                            "name"=> "GenerateQTY",
                            "icon"=> "UploadIcon",
                            "type"=> "confirm",
                            "post"=> "travelapi/generate/qty/".$row->Oid
                         ],
                         [
                            "name"=> "GenerateMerchant",
                            "icon"=> "UploadIcon",
                            "type"=> "confirm",
                            "post"=> "travelapi/generate/merchant/".$row->Oid
                         ],
                         [
                            "name"=> "EmailUser",
                            "icon"=> "UploadIcon",
                            "type"=> "confirm",
                            "post"=> "travelapi/send/user/".$row->Oid
                         ],
                         [
                            "name"=> "EmailVendor",
                            "icon"=> "UploadIcon",
                            "type"=> "confirm",
                            "post"=> "travelapi/send/vendor/".$row->Oid
                         ]
                    ];
                } elseif (in_array("Expense",$orderType)) {
                    $row->Action = [
                        [
                            'name' => 'Exchange Order',
                            'icon' => 'PrinterIcon',
                            'type' => 'open_report',
                            'get' => 'prereport/traveltransaction?oid={Oid}&report=exchangeorder',
                        ]
                    ];
                } elseif (in_array("Attraction",$orderType)) {
                    $row->Action = [
                        [
                          "name" => "Manual Allocate",
                          "icon" => "SearchIcon",
                          "type" => "open_form",
                          "url" => "traveltransaction/stock?Item=".$row->Oid."&DateStart=2010-01-01&DateUntil=2030-01-01&CostPrice"
                        ],
                        [
                          "name" => "ViewEticket",
                          "icon" => "UploadIcon",
                          "type" => "open_grid",
                          "get" => "traveltransaction/eticket/list/".$row->Oid
                        ],
                        [
                          "name" => "GenerateQTY",
                          "icon" => "UploadIcon",
                          "type" => "confirm",
                          "post" => "travelapi/generate/qty/".$row->Oid
                        ],
                        [
                          "name" => "GenerateMerchant",
                          "icon" => "UploadIcon",
                          "type" => "confirm",
                          "post" => "travelapi/generate/merchant/".$row->Oid
                        ],
                        [
                          "name" => "EmailUser",
                          "icon" => "UploadIcon",
                          "type" => "confirm",
                          "post" => "travelapi/send/user/".$row->Oid
                        ],
                        [
                          "name" => "EmailVendor",
                          "icon" => "UploadIcon",
                          "type" => "confirm",
                          "post" => "travelapi/send/vendor/".$row->Oid
                        ],
                        [
                          "name" => "ManualProcess",
                          "icon" => "UploadIcon",
                          "type" => "global_form",
                          "showModal" => false,
                          "post" => "traveltransaction/eticketmanualprocess?traveltransaction=".$row->Oid,
                          "form" => [
                            [
                              "fieldToSave" => "eticketlist",
                              "overrideLabel" => "Ticket Number",
                              "type" => "inputarea"
                            ]
                          ]
                        ]
                    ];
                }

                unset($row->StatusObj);
                unset($row->TravelHotelRoomTypeObj);
                unset($row->ItemContentObj);
                unset($row->ItemObj);
                unset($row->BusinessPartnerObj);
                if (!$row->ItemObj) continue;
                if ($row->ItemObj->ItemTypeObj->Code = 'Attraction') {
                    $stock = ETicket::where('TravelTransactionDetail',$row->Oid)->get();
                    if ($stock) $row->StockWithdrawed = $stock->count();
                }
            }
            return $result;
        } else return null;
    }

    public function show(PointOfSale $data)
    {
        try {
            $data = PointOfSale::with('Details', 'TravelDetails', 'Logs')->with([
                'CompanyObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'SupplierObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'CustomerObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'Details.ItemObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'TravelDetails.ItemObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PointOfSaleTypeObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'POSTableObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'EmployeeObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'Employee2Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'ProjectObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'UserObj' => function ($query) { $query->addSelect('Oid', 'UserName', 'Name'); },
                'CurrencyObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'POSSessionObj' => function ($query) { $query->addSelect('Oid'); },
                'StatusObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PaymentMethodObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PaymentCurrencyObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PaymentMethod2Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PaymentMethod3Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PaymentMethod4Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'PaymentMethod5Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
            ])->findOrFail($data->Oid);
            $data->CompanyName = $data->CompanyObj ? $data->CompanyObj->Code : null;
            // $data->Role = $this->generateRole($data);

            //etickets
            $query = "SELECT * FROM poseticket p WHERE p.PointOfSale = '{$data->Oid}' AND (p.IsInvoice = FALSE OR p.IsInvoice IS NULL) ORDER BY p.Code";
            $etickets = DB::select($query);
            $data->ETickets = [];
            if ($etickets) $data->ETickets = $etickets;
            
            //etickets
            $data->SalesInvoices = SalesInvoice::with('CurrencyObj')->where('PointOfSale', $data->Oid)->get();
            foreach($data->SalesInvoices as $row) {
                $row->Action = [
                    [
                        "name"=> "Edit",
                        "icon"=> "PencilIcon",
                        "type"=> "open_form",
                        "url"=> "salesinvoice/form?item=".$row->Oid,
                        "newTab"=> true
                      ],
                      [
                        "name"=> "Change to ENTRY",
                        "icon"=> "UploadIcon",
                        "type"=> "confirm",
                        "post"=> "salesinvoice/".$row->Oid."/unpost"
                      ],
                      [
                        "name"=> "Change to POSTED",
                        "icon"=> "CheckIcon",
                        "type"=> "confirm",
                        "post"=> "salesinvoice/".$row->Oid."/post"
                      ],
                      [
                        "name"=> "Print (Ace Tours)",
                        "icon"=> "PrinterIcon",
                        "type"=> "confirm",
                        "get"=> "prereport/invoice/".$row->Oid."?report=taxinvoice1"
                      ]
                ];
            }

            if ($data->TravelDetails) { // OrderType      
                $data->TravelIncomeExpenses = $this->showSubDetail($data,['Income','Expense']);
                $data->TravelAttractionDetails = $this->showSubDetail($data,['Attraction']);
                $data->TravelOutboundDetails = $this->showSubDetail($data,['Outbound']);
                $data->TravelTransportDetails = $this->showSubDetail($data,['Transport']);
                $data->TravelRestaurantDetails = $this->showSubDetail($data,['Restaurant']);
                $data->TravelHotelDetails = $this->showSubDetail($data,['Hotel']);
            }
            
            // $data = (new PointOfSaleResource($data))->type('detail');
            if ($data->POSSession) {
                $session = POSSession::with('UserObj')->findOrFail($data->POSSession);
                $data->POSSessionObj->Name = Carbon::parse($session->Date)->format('Y-m-d') . ' ' . $session->UserObj->UserName;
            }

            $travelTransaction = TravelTransaction::with('Flights', 'Passengers', 'Itineraries')->with([
                'TravelItemTourPackageObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'TravelGuide1Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'TravelGuide2Obj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
                'UserProcessObj' => function ($query) { $query->addSelect('Oid', 'Code', 'Name'); },
            ])->findOrFail($data->Oid);
            $travelTransaction->TravelTypeName = $travelTransaction->TravelTypeObj->Code;
            unset($travelTransaction->TravelTypeObj);
            unset($travelTransaction->Code);

            foreach($travelTransaction->Flights as $row) {
                $row->TravelFlightNumberName = $row->TravelFlightNumberObj ? $row->TravelFlightNumberObj->Name : null;
            }
            foreach($travelTransaction->Passengers as $row) $row->NationalityName = $row->NationalityObj ? $row->NationalityObj->Name : null;

            if ($travelTransaction->Itineraries) {
                foreach($travelTransaction->Itineraries as $row) $row->BusinessPartnerHotelName = $row->BusinessPartnerHotelObj ? $row->BusinessPartnerHotelObj->Name : null;
                $tmp = dataReorder($travelTransaction->Itineraries, 'Date');
                unset($travelTransaction->Itineraries);
                $travelTransaction->Itineraries = $tmp;
            }
            
            if ($travelTransaction->TravelTypeObj->Code == 'Outbound') { //salesinvoice
                $salesInvoice = SalesInvoice::with('Details','Details.Itemobj')->where('PointOfSale',$travelTransaction->Oid)->first();
                if ($salesInvoice) {
                    $travelTransaction->SalesInvoiceOid = $salesInvoice->Oid;
                    $travelTransaction->SalesInvoiceCode = $salesInvoice->Code;
                    $travelTransaction->SalesInvoiceDate = $salesInvoice->Date;
                    $travelTransaction->SalesInvoiceDetails = $salesInvoice->Details;
                    foreach ($travelTransaction->SalesInvoiceDetails as $row) {
                        $row->ItemName = $row->ItemObj ? $row->ItemObj->Name : '';
                    }
                }
            }

            $data1 = collect($data);
            $data2 = collect($travelTransaction);
            $data = $data1->merge($data2);
            $data['Action'] = $this->action($travelTransaction->Oid);

            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function action($data) {
        $data = TravelTransaction::where('Oid',$data)->first();
        $actionOpen = [
            'name' => 'Open',
            'icon' => 'ViewIcon',
            'type' => 'open_form',
            'url' => 'traveltransaction/form?item={Oid}',
        ];
        return [
            $actionOpen
        ];
    }

    public function save(Request $request, $Oid = null)
    {
        $ttype = null;
        if ($request->has('form')) {
            if ($request->input('form') == 'git') $ttype = 'Git';
            elseif ($request->input('form') == 'fit') $ttype = 'Fit';
            elseif ($request->input('form') == 'outbound') $ttype = 'Outbound';
            else $ttype = 'Web';
        }
        $data;
        try {
            DB::transaction(function () use ($request, $Oid, &$data, &$ttype) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                if (!$Oid) $data = new PointOfSale();
                else $data = PointOfSale::findOrFail($Oid);
                if (!$ttype) {
                    if (!$Oid) $ttype = TravelType::where('Oid', $request->TravelType)->first();
                    else $ttype = TravelType::where('Oid', $data->TravelTransactionObj->TravelType)->first();
                    $ttype = $ttype->Code;
                }
                $enabled = $this->httpService->get('/portal/api/development/table/getfield/pospointofsale');
                $company = Auth::user()->CompanyObj;
                if (!$Oid) {
                    if (!isset($request->Company)) $request->Company = $company->Oid;
                    if (!isset($request->Code)) $request->Code = '<<Auto>>';
                    if (!isset($request->Date)) $request->Date = now();
                    if (!isset($request->PointOfSaleType)) $request->PointOfSaleType = PointOfSaleType::where('Code', 'attraction')->first()->Oid;                    
                    if (!isset($request->Source)) $request->Source = 'Backend';
                    if (!isset($request->DateExpiry)) {
                        if ($request->Source == 'Backend') $request->DateExpiry = now()->addYear(10)->toDateTimeString();
                        else $request->DateExpiry = now()->addHour(10)->toDateTimeString();
                    }
                    if (!isset($request->Customer)) { //kalo tdk ada customer
                        if (!isset($request->User)) $request->Customer = $company->CustomerCash; //isi dari company
                        else $request->Customer = User::findOrFail($request->User)->BusinessPartner; //isi dari user
                    }
                    $customer = BusinessPartner::findOrFail($request->Customer);
                    if (!isset($request->User)) { //kalo tdk ada user
                        if (!isset($request->Customer)) $request->User = Auth::user()->Oid; //isi dari login
                        else { 
                            $bpuser = User::where('BusinessPartner', $request->Customer)->first(); //isi dari customer
                            $request->User = $bpuser ? $bpuser->Oid : null;
                        }                    
                    }
                    if (!isset($request->Currency)) $request->Currency = $customer->SalesCurrency;
                    $cur = Currency::findOrFail($request->Currency);
                    if (!isset($request->Warehouse)) $request->Warehouse = $company->Warehouse;
                    if (!isset($request->Status)) $request->Status = Status::entry()->first()->Oid;
                    if (!isset($request->RateAmount)) $request->RateAmount = $cur->getRate($request->Date) ? $cur->getRate($request->Date)->MidRate : 1;
                }

                if (isset($request->PaymentMethod)) $data->PaymentMethod = $request->PaymentMethod;
                if (isset($request->PaymentCurrency)) $data->PaymentCurrency = $request->PaymentCurrency;
                
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $cur = Currency::findOrFail($data->Currency);
                $customer = BusinessPartner::findOrFail($data->Customer);
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'pospointofsale');
                if (!$data->Status) $data->Status = Status::entry()->first()->Oid;
                if (!$data->Source) $data->Source = 'Backend';
                if (!$data->RateAmount) $data->RateAmount = 1;
                $data->save();

                if (!$Oid) $travelTransaction = new TravelTransaction();
                else $travelTransaction = TravelTransaction::findOrFail($Oid);
                $enabled = $this->httpService->get('/portal/api/development/table/getfield/traveltransaction');
                $travelTransaction->Oid = $data->Oid;
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $travelTransaction->{$field} = $request->{$field};
                }
                if (isset($request->TravelTemplateNoteGuide)) $travelTransaction->TravelTemplateNoteGuide = null;                
                if (isset($request->TravelTemplateNote)) $travelTransaction->TravelTemplateNote = null;
                $travelType = TravelType::where('Code', strtoupper($ttype))->first();
                $travelTransaction->TravelType = $travelType->Oid;
                $travelTransaction->save();
                
                $this->calcTotal($data, $travelTransaction);
                           
                if ($ttype == 'Outbound') {
                    $salesInvoice = SalesInvoice::where('PointOfSale',$data->Oid)->first();
                    if (!$salesInvoice) $salesInvoice = new SalesInvoice();
                    $salesInvoice->Company = $data->Company;
                    $salesInvoice->Code = isset($request->SalesInvoiceCode) ? $request->SalesInvoiceCode : '<<Auto>>';
                    $salesInvoice->Date = isset($request->SalesInvoiceDate) ? $request->SalesInvoiceDate : now();
                    $salesInvoice->PointOfSale = $data->Oid;
                    $salesInvoice->BusinessPartner = $data->Customer;
                    $salesInvoice->Currency = $data->Currency;
                    $salesInvoice->Rate = $data->Rate;
                    $salesInvoice->Status = Status::where('Code','entry')->first()->Oid;
                    $salesInvoice->save();
                    $salesInvoice->Code = $this->autoNumberService->generate($salesInvoice, 'trdsalesinvoice');
                    $salesInvoice->save();
                }
            });            

            // $role = $this->generateRole($data);
            $data = $this->show($data);
            
            // $data = new PointOfSaleResource($data);
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

    public function createInvoice(Request $request) {  
        $data;
        try {
            DB::transaction(function () use ($request, &$data) {
            $data = new SalesInvoice();
            $travelTransaction = PointOfSale::findOrFail($request->input('traveltransaction'));
            $data->Company = $travelTransaction->Company;
            $data->Code = '<<Auto>>';
            $data->Date = now();
            $data->BusinessPartner = $travelTransaction->Customer;
            $bpag = $travelTransaction->CustomerObj->BusinessPartnerAccountGroupObj ? $travelTransaction->CustomerObj->BusinessPartnerAccountGroupObj : $travelTransaction->CustomerObj->BusinessPartnerGroupObj->BusinessPartnerAccountGroupObj;
            $data->Account = $bpag->SalesInvoice;
            $data->Currency = $travelTransaction->Currency;
            $data->PointOfSale = $travelTransaction->Oid;
            $data->Status = Status::where('Code','entry')->first()->Oid;
            $data->save();
            $data->Code = $this->autoNumberService->generate($data, 'trdsalesinvoice');
            $data->save();
        });
        
        // $data = new PointOfSaleResource($data);
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

    public function listDetailTransaction(Request $request)
    {
        try {
            $pos = $request->input('pos');
            $itemtype = $request->input('itemtype');
            $data = TravelTransactionDetail::where('TravelTransaction', $pos)->where('OrderType', $itemtype);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function actionChangeItinerary(Request $request) {        
        if ($request->input('form') == 'traveltransactiongit') $ttype = 'git';
        elseif ($request->input('form') == 'traveltransactionfit') $ttype = 'fit';
        elseif ($request->input('form') == 'traveltransactionoutbound') $ttype = 'outbound';
        else $ttype = 'web';

        $data = TravelTransaction::with('Itineraries')->findOrFail($request->input('oid'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        $itineraries = TravelItemTourPackageItinerary::where('TravelItemTourPackage',$request->TravelItemTourPackage)->get();
        foreach($data->Itineraries as $row) $row->delete();
        foreach($itineraries as $row) {
            $tmp = new TravelTransactionItinerary();
            $tmp->Company = $data->Company;
            $tmp->TravelTransaction = $data->Oid;
            $date = isset($data->DateFrom) ? $data->DateFrom : $data->Date;
            $tmp->Date = Carbon::parse($date)->addDays($row->Sequence)->toDateString();
            $tmp->DescriptionEN = $row->Description;
            $tmp->save();
        }
        return $data;
    }

    private function getType($request) {
        if ($request->input('form') == 'traveltransactiongit') return 'git';
        elseif ($request->input('form') == 'traveltransactionfit') return 'fit';
        elseif ($request->input('form') == 'traveltransactionoutbound') return 'outbound';
        else return 'web';
    }

    public function saveRowDetail(Request $request) {
        try {
            $detail = null;
            DB::transaction(function () use ($request, &$detail) {
                $data = PointOfSale::findOrFail($request->input('ParentOid'));
                if ($data) $ttype = TravelType::where('Oid', $data->TravelTransactionObj->TravelType)->first();
                $ttype = $ttype->Code;
                $module = $request->module;
                $flag = false;
                $Oid = $request->has('Oid') ? $request->input('Oid') : null;
                if ($request->has('Oid')) if (!in_array($request->input('Oid'),['null','undefined'])) $flag = true;
                
                $table = "trvtransactiondetail";
                switch ($module) {
                    case 'flight': {
                        if ($flag == true) $detail = TravelTransactionFlight::findOrFail($request->input('Oid'));
                        if (!isset($detail)) $detail = new TravelTransactionFlight();
                        
                        $table = "trvtransactionflight";
                        $detail->Company = $data->Company;
                        $detail->TravelTransaction = $data->Oid;
                        break;
                    }
                    case 'itinerary': {
                        if ($flag == true) $detail = TravelTransactionItinerary::findOrFail($request->input('Oid'));
                        if (!isset($detail)) $detail = new TravelTransactionItinerary();
                        $table = "trvtransactionitinerary";
                        $detail->Company = $data->Company;
                        $detail->TravelTransaction = $data->Oid;
                        break;
                    }
                    case 'passenger': {
                        if ($flag == true) $detail = TravelTransactionPassenger::findOrFail($request->input('Oid'));
                        if (!isset($detail)) $detail = new TravelTransactionPassenger();
                        $table = "trvtransactionpassenger";
                        $detail->Company = $data->Company;
                        $detail->TravelTransaction = $data->Oid;
                        break;
                    }
                    case 'salesinvoicedetail': {
                        $salesInvoice = SalesInvoice::with('Details')->where('PointOfSale',$data->Oid)->first();
                        if ($flag == true) $detail = SalesInvoiceDetail::findOrFail($request->input('Oid'));
                        if (!isset($detail)) $detail = new SalesInvoiceDetail();
                        $table = "trdsalesinvoicedetail";
                        $detail->Company = $salesInvoice->Company;
                        $detail->SalesInvoice = $salesInvoice->Oid;                        
                        break;
                    }                
                    default: {
                        $table = "trvtransactiondetail";
                        if ($flag == true) $detail = TravelTransactionDetail::findOrFail($request->input('Oid'));
                        if (!isset($detail)) $detail = new TravelTransactionDetail();
                        $detail->Company = $data->Company;
                        $detail->TravelTransaction = $data->Oid;
                        break;
                    }
                }
                
                switch ($module) {
                    case 'incomeexpense': {
                        $detail->Qty = 1;
                        $detail->OrderType = $detail->OrderType ?: 'Income';
                        break;
                    }
                    case 'transport': {
                        $detail->OrderType = 'Transport';
                        break;
                    }
                    case 'restaurant': {
                        $detail->OrderType = 'Restaurant';
                        break;
                    }
                    case 'hotel': {
                        $detail->OrderType = 'Hotel';
                        $detail->Status = isset($detail->Status) && $detail->Status !='' ? $detail->Status : Status::where('Code','entry')->first()->Oid;
                    }
                }

                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
                if ($module == 'itinerary') $detail = $this->crudController->save($table, $detail, $request);
                elseif ($module != 'salesinvoicedetail') {
                    $detail = $this->crudController->save($table, $detail, $request, $data);
                } else {
                    $detail = $this->crudController->save($table, $detail, $request, $salesInvoice);
                }

                if ($module == 'salesinvoicedetail') {                    
                    if (!$detail->Sequence) {
                        $sequence = null;                         
                        try {
                            $sequence = ($detail::where('SalesInvoice',$salesInvoice->Oid)->max('Sequence') ?: 0 + 1);
                        } 
                        catch (\Exception $ex) {  $err = true; }
                        $detail->Sequence = $sequence;
                        $detail->save();
                    }
                }
                
                if (!in_array($module, ['flight','itinerary','passenger','salesinvoicedetail'])) {
                    $this->savepurchaseTotal($detail);
                    if (!$detail->Sequence) {
                        $sequence = null;                         
                        try {
                            $sequence = ($detail::where('TravelTransaction',$data->Oid)->max('Sequence') ?: 0 + 1);
                        } 
                        catch (\Exception $ex) {  $err = true; }
                        $detail->Sequence = $sequence;
                        $detail->save();
                    }                    
                }
            });
            if (in_array($request->module, ['transport','restaurant','hotel','incomeexpense'])) {
                $detail = TravelTransactionDetail::with(['ItemObj','BusinessPartnerObj'])
                    ->where('Oid', $detail->Oid)
                    // ->where('OrderType', $detail->OrderType)
                    ->first();
                if ($detail) {
                    $detail->FlightTypeName = $detail->FlightTypeName == 0 ? 'Arrival' : 'Departure';
                    $detail->ItemName = $detail->ItemObj ? $detail->ItemObj->Name : null;
                    $detail->ItemContentName = $detail->ItemContentObj ? $detail->ItemContentObj->Name : null;
                    $detail->BusinessPartnerName = $detail->BusinessPartnerObj ? $detail->BusinessPartnerObj->Name : null;
                    $detail->TravelHotelRoomTypeName = $detail->TravelHotelRoomTypeObj ? $detail->TravelHotelRoomTypeObj->Name : null;
                    $detail->StatusName = $detail->StatusObj ? $detail->StatusObj->Name : null;
                    unset($detail->TravelHotelRoomTypeObj);
                    unset($detail->ItemContentObj);
                    unset($detail->ItemObj);
                    unset($detail->StatusObj);
                    unset($detail->BusinessPartnerObj);
                }
            } elseif ($request->module=='flight') {
                $detail = TravelTransactionFlight::where('Oid', $detail->Oid)->first();
                $detail->TravelFlightNumberName = $detail->TravelFlightNumberObj ? $detail->TravelFlightNumberObj->Name : null;            
            } elseif ($request->module=='passenger') {
                $detail = TravelTransactionPassenger::where('Oid', $detail->Oid)->first();
                $detail->NationalityName = $detail->NationalityObj ? $detail->NationalityObj->Name : null;                
            }
            return $detail;
        } catch (\Exception $e) {
            return response()->json(errjson($e), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function deleteRowDetail(Request $request) {     
        $data = PointOfSale::findOrFail($request->input('ParentOid'));   
        $module = $request->input('module');
        switch ($module) {
            case 'flight': {
                $detail = TravelTransactionFlight::where('Oid', $request->input('Oid'))->firstOrFail();
                break;
            }
            case 'itinerary': {
                $detail = TravelTransactionItinerary::where('Oid', $request->input('Oid'))->firstOrFail();
                break;
            }
            case 'salesinvoice': {
                $detail = SalesInvoice::where('Oid', $request->input('Oid'))->firstOrFail();
                break;
            }
            case 'salesinvoicedetail': {
                $detail = SalesInvoiceDetail::where('Oid', $request->input('Oid'))->firstOrFail();
                break;
            }
            case 'passenger': {
                $detail = TravelTransactionPassenger::where('Oid', $request->input('Oid'))->firstOrFail();
                break;
            }
            default: {
                $detail = TravelTransactionDetail::where('Oid', $request->input('Oid'))->firstOrFail();
                break;
            }
        }
        $detail->delete();
        $data = $this->show($data);
    }

    public function saveAttraction(Request $request) {
        try {
            $data = PointOfSale::findOrFail($request->input('ParentOid'));
            $detail;
            $input = $request;
            
            DB::transaction(function () use ($request, $input, $data, &$detail) {
                // $ttype = $this->getType($request);                
                if ($data) $ttype = TravelType::where('Oid', $data->TravelTransactionObj->TravelType)->first();
                $ttype = $ttype->Code;
                
                if ($request->has('Oid')) if (!in_array($request->input('Oid'),['null','undefined'])) $detail = TravelTransactionDetail::findOrFail($request->input('Oid'));
                if (!isset($detail)) $detail = new TravelTransactionDetail();
                
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

                $disabled = array_merge(disabledFieldsForEdit(), ['ItemName','BusinessPartnerName']);
                $detail->Company = $data->Company;
                $detail->TravelTransaction = $data->Oid;
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $detail->{$field} = $request->{$field};
                }
                $detail->OrderType = 'Attraction';
                $item = Item::with('ItemContentObj')->findOrFail($detail->Item);
                $item->IsParent = $item->ItemContentObj->IsUsingContentFromParent == true && $item->ItemContentObj->ItemContentSource != null;
                $item->ParentObj = !$item->IsParent ? $item->ItemContentObj : $item->ItemContentObj->SourceObj;
                $detail->Name = $item->Subtitle;
                $detail->Title = $item->ParentObj->Name;
                $detail->BusinessPartner = $item->ParentObj->PurchaseBusinessPartner;
                $detail->SalesDescription = substr($item->ParentObj->DescriptionEN, 0, 200);
                if ($detail->APIType == 'auto_stock') {
                    $this->travelAPIService->setToken($input->bearerToken());
                    $price = $this->travelAPIService->getapi("/api/travel/v1/adminapi/item?system=".$data->Company."&user=".$data->User."&detail=1&pos=".$data->Oid."&itemcontent=".$detail->ItemContent
                    ."&businesspartner=".$detail->BusinessPartner."&item=".$detail->Item);
                    if($price) {
                        $price = $price[0];
                        $detail->PurchaseAdult = $price->PurchaseAdult;
                        $detail->PurchaseChild = $price->PurchaseChild;
                        $detail->PurchaseInfant = $price->PurchaseInfant;
                        $detail->PurchaseSenior = $price->PurchaseSenior;
                    }
                }
                $detail->Image = $item->ParentObj->Image1;
                $detail->Date = Carbon::now()->addHours(company_timezone())->toDateTimeString();
                $detail->DateFrom = Carbon::now()->addHours(company_timezone())->toDateTimeString();
                $detail->DateUntil = Carbon::now()->addHours(company_timezone())->toDateTimeString();
                $detail->save();
                $this->saveDetailSameField($detail, $item, $data);
                $this->calculateDetailDateQty($detail);
                $this->calculateDetailAmount($detail);
                $detail->save();
            });

            $travelTransaction = TravelTransaction::findOrFail($request->input('ParentOid'));
            $tmp = $this->calcTotal($data,$travelTransaction);
            
            // $detail = TravelTransactionDetail::with(['ItemObj','BusinessPartnerObj'])
            //     ->where('Oid', $detail->Oid)
            //     ->first();
            if ($detail) {
                $detail->ItemName = $detail->ItemObj ? $detail->ItemObj->Name : null;
                $detail->ItemContentName = $detail->ItemContentObj ? $detail->ItemContentObj->Name : null;
                $detail->BusinessPartnerName = $detail->BusinessPartnerObj ? $detail->BusinessPartnerObj->Name : null;
                unset($detail->ItemContentObj);
                unset($detail->ItemObj);
                unset($detail->BusinessPartnerObj);
            }
            $detail->ParentObj = [
                'AmountTourFareTotal' => $tmp->AmountTourFareTotal,
                'AmountAgentCommission' => $tmp->AmountAgentCommission,
                'OptionalTour1AmountTicket' => $tmp->OptionalTour1AmountTicket,
                'OptionalTour1TicketAdultTotal' => $tmp->OptionalTour1TicketAdultTotal,
                'OptionalTour1TicketChildTotal' => $tmp->OptionalTour1TicketChildTotal,
                'OptionalTour1TicketSeniorTotal' => $tmp->OptionalTour1TicketSeniorTotal,
                'OptionalTour1AmountTourBalanceTotal' => $tmp->OptionalTour1AmountTourBalanceTotal,
                'OptionalTour2AmountTicket' => $tmp->OptionalTour2AmountTicket,
                'OptionalTour2TicketAdultTotal' => $tmp->OptionalTour2TicketAdultTotal,
                'OptionalTour2TicketChildTotal' => $tmp->OptionalTour2TicketChildTotal,
                'OptionalTour2TicketSeniorTotal' => $tmp->OptionalTour2TicketSeniorTotal,
                'OptionalTour2AmountTourBalanceTotal' => $tmp->OptionalTour2AmountTourBalanceTotal,
                'IncomeOther' => $tmp->IncomeOther,
                'IncomeBalanceToCompany' => $tmp->IncomeBalanceToCompany,
                'IncomeExchangeRate' => $tmp->IncomeExchangeRate,
                'ExpenseOther' => $tmp->ExpenseOther,
                'ExpenseBalanceToGuide' => $tmp->ExpenseBalanceToGuide,
                'ExpenseWater' => $tmp->ExpenseWater,
                'ExpenseTourGuideFee' => $tmp->ExpenseTourGuideFee,
            ];

            if ($detail->APIType == 'auto_stock') {
                $this->travelAPIService->setToken($input->bearerToken());
                $this->travelAPIService->postapi("/api/travel/v1/adminapi/link/stock/".$data->Oid."?system=".$data->Company."&user=".$data->User);
            }
            
            return response()->json($detail, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(errjson($e), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
    
    private function calcTotal(PointOfSale $data, TravelTransaction $travelTransaction)
    {
        $cur = Currency::findOrFail($data->Currency);

        $tt = $travelTransaction;
        $pos = $data;

        //main
        $tt->QtyTotalPax = $tt->QtyAdult + $tt->QtyCWB + $tt->QtyCNB + $tt->QtyInfant + $tt->QtyTL + $tt->QtyExBed;
        // $tt->AmountTourFarePerPax = user input
        // $tt->AmountTourFareTotal = $tt->QtyTotalPax * $tt->AmountTourFarePerPax;
        $tt->AmountAgentCommission = $tt->QtyTotalPax * $tt->AmountAgentCommissionPerHead;
        //$tt->AmountTourFareNett

        //optional 1
        // $tmp = TravelTransactionDetail::where('TravelTransaction',$tt->Oid)->where('SalesIncludeOptional','Optional1')->where('OrderType','Attraction')->get();
        $tmp = TravelTransactionDetail::where('TravelTransaction',$tt->Oid)->where('SalesIncludeOptional','Optional1')->where('OrderType','Attraction')->get();
        $tmpAmount = 0;
        foreach($tmp as $row) $tmpAmount += $row->PurchaseAdult * $row->QtyAdult;
        $tt->OptionalTour1AmountTicket = $tmpAmount;
        $tt->OptionalTour1TicketAdultTotal = $tt->OptionalTour1TicketAdult * $tt->OptionalTour1TicketAdultAmount;
        $tt->OptionalTour1TicketChildTotal = $tt->OptionalTour1TicketChild * $tt->OptionalTour1TicketChildAmount;
        $tt->OptionalTour1TicketSeniorTotal = $tt->OptionalTour1TicketSenior * $tt->OptionalTour1TicketSeniorAmount;        
        $tt->OptionalTour1AmountTourBalanceTotal = $tt->OptionalTour1AmountTicket + $tt->OptionalTour1AmountTourBalance;
        
        //optional 2
        $tmp = TravelTransactionDetail::where('TravelTransaction',$tt->Oid)->where('SalesIncludeOptional','Optional2')->where('OrderType','Attraction')->get();
        $tmpAmount = 0;
        foreach($tmp as $row) $tmpAmount += $row->PurchaseAdult * $row->QtyAdult;
        $tt->OptionalTour2AmountTicket = $tmpAmount;
        $tt->OptionalTour2TicketAdultTotal = $tt->OptionalTour2TicketAdult * $tt->OptionalTour2TicketAdultAmount;
        $tt->OptionalTour2TicketChildTotal = $tt->OptionalTour2TicketChild * $tt->OptionalTour2TicketChildAmount;
        $tt->OptionalTour2TicketSeniorTotal = $tt->OptionalTour2TicketSenior * $tt->OptionalTour2TicketSeniorAmount;
        $tt->OptionalTour2AmountTourBalanceTotal = $tt->OptionalTour2AmountTicket + $tt->OptionalTour2AmountTourBalance;
        
        //Income
        $tmp = TravelTransactionDetail::where('TravelTransaction',$tt->Oid)->where('OrderType','Income')->get();
        $tmpAmount = 0;
        foreach($tmp as $row) $tmpAmount += $row->PurchaseAmount;
        $tt->IncomeOther = $tmpAmount;
        $tt->IncomeBalanceToCompany = $tt->IncomeToCompany + $tt->IncomeTipsToCompany + $tt->IncomeOther;
        // revision 20200612
        // $tt->IncomeBalanceToCompany = $tt->IncomeTourLeader + $tt->IncomeTourGuide + $tt->IncomeToCompany + 
        //     $tt->IncomeExchangeRate + $tt->IncomeSerdiz + $tt->IncomeTipsToCompany + $tt->IncomeOther;
            
        //Expense
        $tmp = TravelTransactionDetail::where('TravelTransaction',$tt->Oid)->where('OrderType','Expense')->get();
        $tmpAmount = 0;
        foreach($tmp as $row) $tmpAmount += $row->PurchaseAmount;
        $tt->ExpenseOther = $tmpAmount;
        $tt->ExpenseBalanceToGuide = $tt->ExpenseDriver + $tt->ExpensePorter + $tt->ExpenseLuggage + 
            $tt->ExpenseWater + $tt->ExpenseTaxi + $tt->ExpenseCombiCoach + $tt->ExpenseTourGuideTips + 
            $tt->ExpenseTourGuideFee + $data->ExpenseOther;
        $tt->save();

        //pos
        $pos->TotalAmount = $pos->SubtotalAmount + $pos->AdditionalAmount - $pos->DiscountPercentageAmount - $pos->DiscountAmount + $pos->ConvenienceAmount + $pos->AdmissionAmount;
        $pos->SubtotalAmountBase = $cur->toBaseAmount($pos->SubtotalAmount, $pos->RateAmount);
        $pos->TotalAmount = $pos->SubtotalAmount + $pos->AdditionalAmount - $pos->DiscountPercentageAmount - $pos->DiscountAmount + $pos->ConvenienceAmount + $pos->AdmissionAmount;
        $pos->TotalAmountBase = $cur->toBaseAmount($pos->TotalAmount, $pos->RateAmount);
        $rate = $pos->RateAmount;
        $pos->DiscountAmountBase = $cur->toBaseAmount($pos->DiscountAmount, $rate) ?: 0;
        $pos->ConvenienceAmountBase = $cur->toBaseAmount($pos->ConvenienceAmount, $rate) ?: 0;
        $pos->AdditionalAmountBase = $cur->toBaseAmount($pos->AdditionalAmount, $rate) ?: 0;
        $pos->AdmissionAmountBase = $cur->toBaseAmount($pos->AdmissionAmount, $rate) ?: 0;
        $pos->TotalAmountBase = $cur->toBaseAmount($pos->TotalAmount, $rate) ?: 0;
        $pos->save();
        $pos->fresh();
        return $tt;
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

            $data = PointOfSale::with(['Details','ETickets','TravelDetails','Logs'])->findOrFail($Oid);
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

    // private function generateRole(PointOfSale $data, $role = null, $action = null)
    // {
    //     if ($data instanceof PointOfSale) $status = $data->StatusObj;
    //     else $status = $data->Status;
    //     if (!$role) $role = $this->roleService->list('TravelTransactionFIT');
    //     if (!$action) $action = $this->roleService->action('TravelTransactionFIT');

    //     return [
    //         'IsRead' => $role->IsRead,
    //         'IsAdd' => $role->IsAdd,
    //         'IsEdit' => $this->roleService->isAllowDelete($data->StatusObj, $role->IsEdit),
    //         'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
    //         'Cancel' => $this->roleService->isAllowCancel($data->StatusObj, $action->Cancel),
    //         'Complete' => $this->roleService->isAllowComplete($data->StatusObj, $action->Complete),
    //         'Entry' => $this->roleService->isAllowEntry($data->StatusObj, $action->Entry),
    //         'Paid' => $this->roleService->isAllowPaid($data->StatusObj, $action->Paid),
    //         // 'Post' => $this->roleService->isAllowPost($data->StatusObj, $action->Posted),
    //         'ViewJournal' => $this->roleService->isPosted($data->StatusObj, 1),
    //         'ViewStock' => $this->roleService->isPosted($data->StatusObj, 1),
    //         'Print' => $this->roleService->isPosted($data->StatusObj, 1),
    //     ];
    // }

    public function calculateDetailDateQty(&$detail)
    {
        $item = $detail->ItemObj;
        $itemType = $item->ItemTypeObj->Code;

        if ($detail->Type == 5) {
            $detail->Quantity = 1;
        } else if ($detail->Type == 0) {
            $detail->Quantity = 1;
        } else {
            $detail->QtyWeekday = 0;
            $detail->QtyWeekend = 0;
            if ($itemType != 'Transport') {
                $detail->QtyDay = 0;
                if (isset($detail->DateFrom) && isset($detail->DateUntil)) {
                    $start = Carbon::parse($detail->DateFrom);
                    $end = Carbon::parse($detail->DateUntil);
                    while ($start->lt($end)) {
                        if ($start->isWeekday()) $detail->QtyWeekday++;
                        if ($start->isWeekend()) $detail->QtyWeekend++;
                        $start->addDay(1);
                        $detail->QtyDay++;
                    }
                }
            }
            if ($itemType == 'Ferry') $detail->QtyDay = 0;
            $detail->Quantity = ($detail->Qty * ($detail->QtyDay == 0 ? 1 : $detail->QtyDay)) + $detail->QtyAdult + $detail->QtyChild + $detail->QtyInfant +
                $detail->QtySGL + $detail->QtyTWN + $detail->QtyQuad + $detail->QtyQuint + $detail->QtyCHT + $detail->QtyCWB + $detail->QtyCNB;
        }
        $detail->save();
    }

    public function calculateDetailAmount(&$detail)
    {
        if (!is_null($detail->Item)) {
            // $currency = $detail->PointOfSaleObj->CurrencyObj;
            // $rate = $currency->getRate();
            $rate = $detail->SalesRate;
            if (empty($rate)) {
                $rate = $detail->PointOfSaleObj->CurrencyObj->getRate()->MidRate;
                $detail->SalesRate = $rate;
            }

            $item = $detail->ItemObj;
            $itemType = $item->ItemType ? $item->ItemTypeObj->Code : $item->ItemGroupObj->ItemTypeObj->Code;
            if ($itemType == 'Ferry') {
                if ($detail->Passengers->count() > 0) {
                    $detail->SalesAmount = 0;
                    $detail->PurchaseAmount = 0;
                    foreach ($detail->Passengers as $passenger) {
                        $detail->SalesAmount = $detail->SalesAmount + $passenger->FerryDeparture;
                        $detail->PurchaseAmount = $detail->PurchaseAmount + $passenger->FerryDepartureCost;
                    }
                } else $detail->SalesAmount = $detail->Qty * $detail->SalesAmount;
                $detail->Qty = 1;
            }
            $curSales = Currency::findOrFail($detail->SalesCurrency);
            $curPurchase = Currency::findOrFail($detail->PurchaseCurrency);
            //BY WILLIAM SER 20191010 SEMENTARA TIDAK PAKE QTYWKEND QTYWKDAY SALESWKEND SALESWKDAY
            // $detail->SalesSubtotal = ($detail->SalesWeekday * ($detail->QtyWeekday * $detail->Qty)) + 
            // ($detail->SalesWeekend * ($detail->QtyWeekend * $detail->Qty)) + 
            // ($detail->SalesAdult * $detail->QtyAdult) + 
            // ($detail->SalesChild * $detail->QtyChild) + 
            // ($detail->SalesInfant * $detail->QtyInfant) + 
            // ($detail->SalesAmount * $detail->Qty);

            if ($itemType == 'Hotel' || $itemType == 'ApitudeH') { //($detail->QtyTRP * $detail->SalesTRP) +
                // $detail->SalesSubtotal = ($detail->SalesAmount) + ($detail->SalesAdult * $detail->QtyAdult) + ($detail->SalesChild * $detail->QtyChild) + ($detail->SalesInfant * $detail->QtyInfant) + ($detail->SalesSenior * $detail->QtySenior);
                $detail->SalesSubtotal = $detail->SalesSubtotal + ($detail->QtySGL * $detail->SalesSGL) + ($detail->QtyTWN * $detail->SalesTWN) +  ($detail->QtyQuad * $detail->SalesQuad) + ($detail->QtyQuint * $detail->SalesQuint) + ($detail->QtyCHT * $detail->SalesCHT) + ($detail->QtyCWB * $detail->SalesCWB) + ($detail->QtyCNB * $detail->SalesCNB);
                $detail->SalesTotal = $detail->SalesSubtotal;
                $detail->SalesTotalBase = $curSales->ToBaseAmount($detail->SalesTotal, $rate);
                // $detail->PurchaseSubtotal = ($detail->PurchaseAmount) + ($detail->PurchaseAdult * $detail->QtyAdult) + ($detail->PurchaseChild * $detail->QtyChild) + ($detail->PurchaseInfant * $detail->QtyInfant) + ($detail->PurchaseSenior * $detail->QtySenior);
                $detail->PurchaseSubtotal = $detail->PurchaseSubtotal + ($detail->QtySGL * $detail->PurchaseSGL) + ($detail->QtyTWN * $detail->PurchaseTWN) +  ($detail->QtyQuad * $detail->PurchaseQuad) + ($detail->QtyQuint * $detail->PurchaseQuint) + ($detail->QtyCHT * $detail->PurchaseCHT) + ($detail->QtyCWB * $detail->PurchaseCWB) + ($detail->QtyCNB * $detail->PurchaseCNB);
                $detail->PurchaseTotal = $detail->PurchaseSubtotal;
                $detail->PurchaseTotalBase = $curPurchase->ToBaseAmount($detail->PurchaseTotal, $rate);
            } else {
                $detail->SalesSubtotal = ($detail->SalesAmount * (($detail->QtyDay ?: 1) * $detail->Qty)) + ($detail->SalesAdult * $detail->QtyAdult) + ($detail->SalesChild * $detail->QtyChild) + ($detail->SalesInfant * $detail->QtyInfant) + ($detail->SalesSenior * $detail->QtySenior);
                $detail->SalesSubtotal = $detail->SalesSubtotal + ($detail->QtySGL * $detail->SalesSGL) + ($detail->QtyTWN * $detail->SalesTWN) + ($detail->QtyTRP * $detail->SalesTRP) + ($detail->QtyQuad * $detail->SalesQuad) + ($detail->QtyQuint * $detail->SalesQuint) + ($detail->QtyCHT * $detail->SalesCHT) + ($detail->QtyCWB * $detail->SalesCWB) + ($detail->QtyCNB * $detail->SalesCNB);

                $detail->SalesTotal = $detail->SalesSubtotal;
                $detail->SalesTotalBase = $curSales->ToBaseAmount($detail->SalesTotal, $rate);
            }

            $rate = $detail->PurchaseRate;
            if (empty($rate)) {
                $rate = $detail->ItemObj->PurchaseCurrencyObj->getRate()->MidRate;
                $detail->PurchaseRate = $rate;
            }

            $detail->PurchaseSubtotal = ($detail->PurchaseWeekday * ($detail->QtyWeekday * $detail->Qty)) + ($detail->PurchaseWeekend * ($detail->QtyWeekend * $detail->Qty)) + ($detail->PurchaseAdult * $detail->QtyAdult) + ($detail->PurchaseChild * $detail->QtyChild) + ($detail->PurchaseInfant * $detail->QtyInfant) + ($detail->PurchaseSenior * $detail->QtySenior) + ($detail->PurchaseAmount * $detail->Qty);
            $detail->PurchaseTotal = $detail->PurchaseSubtotal;
            $detail->PurchaseTotalBase = $curPurchase->ToBaseAmount($detail->PurchaseTotal, $rate);

            $detail->save();
        }
    }

    public function savepurchaseTotal(&$detail){
        $curPurchase = Currency::findOrFail($detail->PurchaseCurrency ?: $detail->PointOfSaleObj->Currency);
        $rate = $curPurchase->getRate()->MidRate;
        $detail->PurchaseSubtotal = 0;
        $detail->PurchaseSubtotal = $detail->PurchaseSubtotal
            + ($detail->QtySGL*$detail->NightSGL*$detail->PurchaseSGL) + ($detail->QtyDBL*$detail->NightDBL*$detail->PurchaseDBL) + ($detail->QtyTWN*$detail->NightTWN*$detail->PurchaseTWN) + ($detail->QtyExBed*$detail->NightExBed*$detail->PurchaseExBed) + ($detail->QtySC*$detail->NightSC*$detail->PurchaseSC) +  ($detail->QtyQuad*$detail->NightQuad*$detail->PurchaseQuad) + ($detail->QtyQuint*$detail->NightQuint*$detail->PurchaseQuint) + ($detail->QtyCHT*$detail->NightCHT*$detail->PurchaseCHT) + ($detail->QtyCWB*$detail->NightCWB*$detail->PurchaseCWB) + ($detail->QtyCNB*$detail->NightCNB*$detail->PurchaseCNB) - ($detail->QtyFOC*$detail->NightFOC*$detail->PurchaseFOC)
            + ($detail->Qty * $detail->PurchaseAmount)
            + ($detail->QtyAdult*$detail->PurchaseAdult) + ($detail->QtyChild*$detail->PurchaseChild);
        $detail->PurchaseTotal = $detail->PurchaseSubtotal;
        $detail->PurchaseTotalBase = $curPurchase->ToBaseAmount($detail->PurchaseTotal, $rate);
        $detail->save();
    }

    private function saveDetailSameField($detail, $item, $pos)
    {
        $currency = $pos->CurrencyObj;
        $rate = $currency->getRate();
        $detail->ItemGroup = $item->ItemGroup;
        $detail->ItemType = $item->ItemGroupObj->ItemType;
        $detail->ItemContent = $item->ItemContent;
        $detail->ItemContentParent = $item->ItemContent ? $item->ItemContentObj->ItemContentParent : $item->ItemContent;
        $detail->ItemContentSource = $item->ItemContent ? $item->ItemContentObj->ItemContentSource : $item->ItemContent;
        $detail->SalesCurrency = $pos->Currency;
        $detail->Status = Status::entry()->value('Oid');
        $detail->SalesRate = $rate ? $rate->MidRate : 1;
        $detail->PurchaseCurrency = $item->ParentObj->PurchaseCurrency;
        $detail->PurchaseDate = now()->toDateString();
        $detail->PurchaseCurrency = isset($item->ItemContent) ? $item->ParentObj->PurchaseCurrency : $item->PurchaseCurrency;
        $purchaseRate = $item->ParentObj->PurchaseCurrencyObj->getRate();
        $detail->PurchaseRate = $purchaseRate ? $item->ParentObj->PurchaseCurrencyObj->getRate()->MidRate : 1;
        // $detail->APIType = isset($item->ItemContent) ? $item->ParentObj->APIType : $item->APIType;
        $detail->save();
    }   

    public function statusInhousePosted(Request $request){
        $data = PointOfSale::where('Oid',$request->input('traveltransaction'))->first();
        $user = Auth::user();
        try {
            DB::transaction(function () use (&$data) {
                $data->Status = Status::where('Code','posted')->first()->Oid;
                $data->save();
        });
        $this->auditService->create($data, [
            'Module' => 'Status',
            'Description' => 'Change status to be Posted',
            'Message' => 'Change status to be Posted',
            'User' => $user
        ]);

        return response()->json(
            $this->show($data),
            Response::HTTP_CREATED
        );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusInhouseEntry(Request $request){
        $data = PointOfSale::where('Oid',$request->input('traveltransaction'))->first();
        try {
            DB::transaction(function () use (&$data) {
                $data->Status = Status::where('Code','entry')->first()->Oid;
                $data->save();
        });
        $user = Auth::user();
        $this->auditService->create($data, [
            'Module' => 'Status',
            'Description' => 'Change status to be Entry',
            'Message' => 'Change status to be Entry',
            'User' => $user
        ]);

        return response()->json(
            $this->show($data),
            Response::HTTP_CREATED
        );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function eticketAttractionList($Oid) {
        $data = ETicket::with('ItemObj')->select(['Code','Item','DateValidFrom','DateExpiry','URL'])->where('TravelTransactionDetail',$Oid)->get();
        foreach($data as $row) {
            $action = $this->roleService->action('PurchaseInvoice');
            $row->ItemName = $row->ItemObj ? $row->ItemObj->Name : null;
            $row->Action = [
                [
                    'name' => 'Open',
                    'icon' => 'ViewIcon',
                    'type' => 'open_report',
                    'hide' => true,
                    'post' => $row->URL,
                ]
            ];
            unset($row->ItemObj);
            unset($row->URL);
        }
        $return = actionCheckCompany($this->module, $return);
        return $data;
    }

    public function eticketmanualtype(Request $request)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data) {
                $detail = TravelTransactionDetail::findOrFail($request->input('traveltransaction'));
                $req = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
                $etickets = []; // textarea multirow
                $eticketscount = 0;
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $req->eticketlist) as $line) {
                    $etickets = array_merge($etickets, [$line]);
                    $eticketscount += 1;
                }

                $tmp = ETicket::where('PointOfSale',$detail->TravelTransaction)->where('TravelTransactionDetail',$detail->Oid)->where('Item',$detail->Item)->get();
                if ($detail->QtyAdult - $tmp->count() < $eticketscount) throw new \Exception('Too many tickets allocated');

                $data = ETicket::whereIn('Code',$etickets)->whereNull('PointOfSale')->whereNull('TravelTransactionDetail')->where('Item',$detail->Item)->get();
                foreach ($data as $row) {
                    $row->PointOfSale = $detail->TravelTransaction;
                    $row->TravelTransactionDetail = $detail->Oid;
                    $row->save();

                    $new = new POSETicketLog();
                    $new->Company = $row->Company;
                    $new->POSEticket = $row->Oid;
                    $new->PointOfSale = $detail->TravelTransaction;
                    $new->CostPrice = $row->CostPrice;
                    $new->DateValidFrom = Carbon::parse($row->DateValidFrom)->format('Y-m-d');
                    $new->DateExpiry = Carbon::parse($row->DateExpiry)->format('Y-m-d');
                    $new->Description = 'Manual Ticket Number Withdraw';
                    $new->save();
                }
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
    public function eticketmanualallocate(Request $request)
    {
        // UPDATE poseticket p SET p.PointOfSale = NULL, p.TravelTransactionDetail=NULL WHERE p.PointOfSale = 'a54e9db5-cd52-4ecd-b695-e8417f7fb29e';
        // SELECT Oid FROM poseticket WHERE PointOfSale = 'a54e9db5-cd52-4ecd-b695-e8417f7fb29e';
        // SELECT Oid,Item,t.QtyAdult FROM trvtransactiondetail t WHERE t.TravelTransaction = 'a54e9db5-cd52-4ecd-b695-e8417f7fb29e';
        // SELECT OId,Company FROM poseticket WHERE PointOfSale IS NULL AND TravelTransactionDetail IS NULL AND Item = '371ac386-fe9b-4f2d-8958-0d64aee8437e' LIMIT 10;
        try {
            $data;
            DB::transaction(function () use ($request, &$data) {
                $detail = TravelTransactionDetail::findOrFail($request->input('oid'));
                $req = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
                $etickets = []; // textarea multirow
                $eticketscount = 0;
                $check = ETicket::where('PointOfSale',$detail->TravelTransaction)->where('TravelTransactionDetail',$detail->Oid)->where('Item',$detail->Item)->get();   
                // if (isset($req->SelectAll) && !isset($req->Details)) {
                if (isset($req->SelectAll)) {
                    $data = Eticket::whereNull('GCRecord')
                        ->where('Item',$detail->Item)
                        ->whereNull('PointOfSale')
                        ->whereNull('TravelTransactionDetail')
                        ->limit($detail->QtyAdult - (isset($check) ? $check->count() : 0) );
                    if (isset($req->DateStart)) $data = $data->whereRaw("DateValidFrom >= '".$req->DateStart."'");
                    if (isset($req->DateUntil)) $data = $data->whereRaw("DateExpiry <= '".$req->DateUntil."'");
                    if (isset($req->CostPrice)) $data = $data->where('CostPrice', $req->CostPrice);
                    $data = $data->get();
                    $eticketscount = $data->count();
                } else {
                    $etic = "";
                    foreach($req->Details as $row) {
                        $etickets = array_merge($etickets, [$row]);
                        $etic = ($etic=="" ? "" : $etic.",")."'".$row."'";
                        $eticketscount += 1;
                    }
                    if ($detail->QtyAdult - $check->count() < $eticketscount) throw new \Exception('Too many tickets allocated');
                    $data = ETicket::whereNull('PointOfSale')
                        ->whereNull('TravelTransactionDetail')
                        ->where('Item',$detail->Item)
                        ->whereIn('Oid',$etickets)
                        ->limit($eticketscount)
                        // ->whereRaw("Oid IN (".$etic.")")
                        ->get();
                }
                foreach ($data as $row) {
                    $row->PointOfSale = $detail->TravelTransaction;
                    $row->TravelTransactionDetail = $detail->Oid;
                    $row->save();

                    $new = new POSETicketLog();
                    $new->Company = $row->Company;
                    $new->POSEticket = $row->Oid;
                    $new->PointOfSale = $detail->TravelTransaction;
                    $new->CostPrice = $row->CostPrice;
                    $new->DateValidFrom = Carbon::parse($row->DateValidFrom)->format('Y-m-d');
                    $new->DateExpiry = Carbon::parse($row->DateExpiry)->format('Y-m-d');
                    $new->Description = 'Manual Allocation Withdraw';
                    $new->save();
                }
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

    public function testProcess() {
        $limit = 10000;
        //ambil nilai invoice
        $data = TravelTransaction::whereHas('TravelTypeObj', function ($query) {
            $query->whereNotIn('Code', ['Web']);
        })->limit($limit)->get();
        foreach($data as $row) {
            logger('Oid '.$row->Oid.'; Code '.$row->Code);
            $inv = SalesInvoice::where('PointOfSale')->get();
            if (!$inv) continue;
            $amount = 0;
            foreach($inv as $i) $amount = $amount + $i->TotalAmount;
            $row->AmountTourFareTotal = $amount;
            $row->Save();
        }

        //ulang hitung balance to ace
        // $data = TravelTransaction::whereHas('TravelTypeObj', function ($query) {
        //     $query->whereNotIn('Code', ['Web']);
        // })->limit($limit)->whereNull('GCRecord')->get();
        // foreach($data as $row) {
        //     $pos = PointOfSale::where('Oid', $row->Oid)->first();        
        //     $travelTransaction = $row;
        //     if (!$pos) $this->calcTotal($pos, $travelTransaction);
        // }

        //ulang hitung total hotel
        // $data = TravelTransactionDetail::where('OrderType', 'Hotel')->limit($limit)->get();
        // foreach($data as $row) {
        //     $this->savepurchaseTotal($row);
        // }

        //sequence trvtransactiondetail
        // $parent = TravelTransaction::with('Details')->limit($limit)->get();
        // foreach($parent as $p) {
        //     logger($p->Oid);
        //     $details = $p->Details->sortBy('CreatedAt');
        //     $i = 0;
        //     foreach($details as $d) {
        //         $i = $i + 1;
        //         if (!$d->Sequence) {
        //             $d->Sequence = $i;
        //             $d->save();
        //         }
        //     }
        // }

        //sequence salesinvoice
        // $parent = SalesInvoice::with('DetailTravels')->limit($limit)->get();
        // foreach($parent as $p) {
        //     logger($p->Oid);
        //     $details = $p->DetailTravels->sortBy('CreatedAt');
        //     $i = 0;
        //     foreach($details as $d) {
        //         $i = $i + 1;
        //         if (!$d->Sequence) {
        //             $d->Sequence = $i;
        //             $d->save();
        //         }
        //     }
        // }

        return 'SUCCESS';
    }

}
