<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\CostCenter;
use App\Core\Master\Entities\ItemGroup;
use App\Core\POS\Entities\POSETicketUpload;
use App\Core\Master\Entities\ItemAccountGroup;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Internal\Entities\ItemType;
use App\Core\POS\Entities\ItemService;
use App\Core\Travel\Entities\TravelItemHotel;
use App\Core\Travel\Entities\TravelItemOutbound;
use App\Core\Travel\Entities\TravelItemDate;
use App\Core\Travel\Entities\TravelItemTransport;
use App\Core\Master\Entities\ItemDetailLink;
use App\Core\POS\Entities\FeatureInfo;
use App\Core\POS\Entities\FeatureInfoItem;
use App\Core\Production\Entities\ProductionItem;
use App\Core\Production\Entities\ProductionItemGlass;
use App\Core\Production\Entities\ProductionItemProcess;
use App\Core\Master\Entities\ItemECommerce;
use App\Core\Travel\Entities\TravelItemHotelPrice;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Internal\Entities\Status;
use App\Core\Internal\Entities\PriceMethod;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\ETicket;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\POS\Services\POSETicketService;
use App\Core\Master\Entities\Currency;
use App\Core\Internal\Entities\JournalType;
use App\Core\Travel\Entities\TravelItemPriceBusinessPartner;
use App\Core\Base\Services\CoreService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Core\Internal\Services\FileCloudService;
use Validator;
use App\Core\Master\Services\ItemPriceOutboundService;
use App\Core\Master\Services\ItemPriceRestaurantService;
use App\Core\Master\Services\ItemPriceAttractionService;
use App\Core\Internal\Services\AutoNumberService;

use Maatwebsite\Excel\Excel;
use App\AdminApi\Master\Services\ItemExcelImport;
use App\Core\Security\Services\RoleModuleService;

class ItemController extends Controller
{
    protected $roleService;
    protected $fileCloudService;
    private $excelService;
    protected $posETicketService;
    private $coreService;
    protected $priceOutboundService;
    protected $priceAttractionService;
    protected $priceRestaurantService;
    private $autoNumberService;

    
     public function __construct(
         FileCloudService $fileCloudService, 
         RoleModuleService $roleService, 
         ItemPriceOutboundService $priceOutboundService,
         ItemPriceAttractionService $priceAttractionService,
         ItemPriceRestaurantService $priceRestaurantService,
         Excel $excelService, 
         POSETicketService $posETicketService,
         CoreService $coreService,
         AutoNumberService $autoNumberService
         )
     {
        $this->priceOutboundService = $priceOutboundService;
        $this->priceAttractionService = $priceAttractionService;
        $this->priceRestaurantService = $priceRestaurantService;
        $this->fileCloudService = $fileCloudService;
        $this->excelService = $excelService;
        $this->posETicketService = $posETicketService;
        $this->roleService = $roleService;
        $this->coreService = $coreService;
        $this->autoNumberService = $autoNumberService;
     }

    //  public function fields() {    
    //      $fields = []; //f = 'FIELD, t = TITLE
    //      $fields[] = serverSideConfigField('Oid');
    //      $fields[] = serverSideConfigField('Code');
    //      $fields[] = serverSideConfigField('Name');
    //     //  $fields[] = serverSideConfigField('BusinessPartner');
    //      $fields[] = serverSideConfigField('IsActive');
    //      $fields[] = ['w'=> 0,   'f'=>'c.Code',         'n'=>'Currency'];
    //      $fields[] = ['w'=> 0,   'f'=>'c.Code',         'n'=>'ItemContent'];
    //      $fields[] = ['w'=> 0,   'f'=>'ig.Name',         'n'=>'ItemGroup'];
    //      $fields[] = ['w'=> 0,   'f'=>'ity.Name',         'n'=>'ItemType'];
    //      $fields[] = serverSideConfigField('IsActive');
    //      $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Barcode',];
    //      $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'SalesAmount',];
    //      return $fields;
    //  }
 
    //  public function config(Request $request) {
    //      $fields = serverSideFields($this->fields());
    //     foreach ($fields as &$row) { //combosource
    //         if ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
    //     }
    //     return $fields;
    //  }
    //  public function list(Request $request) {
    //      $user = Auth::user();
    //      $fields = $this->fields();
    //      $data = DB::table('mstitem as data')
    //      ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
    //      ->leftJoin('mstcurrency AS c', 'c.Oid', '=', 'data.SalesCurrency')
    //      ->leftJoin('mstitemgroup AS ig', 'ig.Oid', '=', 'data.ItemGroup')
    //      ->leftJoin('sysitemtype AS ity', 'ity.Oid', '=', 'data.ItemType')
    //     //  ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'data.BusinessPartner')
    //      ->leftJoin('mstitemcontent AS ic', 'ic.Oid', '=', 'data.ItemContent')
    //      ;
    //      if ($request->has('itemtype')) $data = $data->where('ig.ItemType', $request->input('itemtype'));

    //      $itemGroupUser = ItemGroupUser::select('Oid')->where('User', $user->Oid)->pluck('Oid');
    //      if ($itemGroupUser->count() > 0) $data->whereIn('ig.Oid', $itemGroupUser);

    //      $data = serverSideQuery($data, $fields, $request, 'mstitem');
    //      $role = $this->roleService->list('ItemContent'); //rolepermission
    //      foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
    //      return serverSideReturn($data, $fields);
    //  }

    // public function index(Request $request)
    // {
    //     try {        
    //         $user = Auth::user();    
    //         $type = $request->input('type') ?: 'combo';
    //         $data = Item::with(['PurchaseBusinessPartnerObj','ItemTypeObj','ProductionItemObj'])->whereNull('GCRecord');
    //         if ($request->has('purchasebusinesspartner')) $data->where('PurchaseBusinessPartner', $request->input('purchasebusinesspartner'));
    //         if ($request->has('itemgroup')) $data->where('ItemGroup', $request->input('itemgroup'));
    //         if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
    //         if ($request->has('city')) $data->where('City', $request->input('city'));
    //         if ($request->has('purchasecurrency')) $data->where('PurchaseCurrency', $request->input('purchasecurrency'));
    //         if ($request->has('salescurrency')) $data->where('SalesCurrency', $request->input('salescurrency'));
    //         if ($request->has('stockupload')) $data->where('APIType', 'AutoStock');
    //         if ($request->has('ecommerce')) {
    //             $input = $request->input('ecommerce');
    //             $data->whereHas('ItemECommerces', function ($query) use ($input) {
    //                 $query->where('ECommerce', $input)->where('IsActive', 1);
    //             });
    //         }
    //         if ($request->has('itemtypecode')) {
    //             $itemtype = ItemType::where('Code',$request->input('itemtypecode'))->first();
    //             $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
    //                 $query->where('ItemType', $itemtype->Oid);
    //             });
    //         }
    //         if ($request->has('itemtype')) {
    //             $itemtype = $request->input('itemtype');
    //             $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
    //                 $query->where('ItemType', $itemtype);
    //             });
    //         }

    //         if ($request->has('businesspartnergroup')) {
    //             $businesspartnergroup = $request->input('businesspartnergroup');
    //             $data->whereHas('PurchaseBusinessPartnerObj', function ($query) use ($businesspartnergroup) {
    //                 $query->where('BusinessPartnerGroup', $businesspartnergroup);
    //             });
    //         }
    //         if ($request->has('itemcontent')) $data->where('ItemContent', $request->input('itemcontent'));
    //         if ($type != 'combo') $data->with(['ItemGroupObj','PurchaseBusinessPartnerObj','SalesCurrencyObj']);
    //         if (!$request->has('parent') && !$request->has('detail')) $data->where('IsDetail',0)->get();
    //         if ($request->has('parent')) $data->where('IsParent', $request->input('parent'));
    //         if ($request->has('detail')) $data->where('IsDetail', $request->input('detail') == 1 ? true : false);
    //         if ($request->has('isstock')) $data->where('IsStock', $request->input('isstock')->whereNull('ItemStockReplacement'));
    //         if ($request->has('auto_stock')) $data->where('APIType', 'auto_stock');
    //         if ($request->has('issales')) $data->where('IsSales', $request->input('issales'));
    //         if ($request->has('ispurchase')) $data->where('IsPurchase', $request->input('ispurchase'));
    //         if ($request->input('pospriceage') == 1) 
    //             $data->whereHas('ItemTypeObj', function ($query) {
    //                 $query->whereIn('Code', ['Travel']);
    //             });
    //         if ($request->input('pospriceday') == 1) 
    //             $data->whereHas('ItemTypeObj', function ($query) {
    //                 $query->whereIn('Code', ['Hotel','Transport']);
    //         });
    //         if ($request->input('poseticketupload') == 1) 
    //         $data->whereHas('ItemTypeObj', function ($query) {
    //             $query->whereIn('Code', ['Travel','Transport','Hotel']);
    //         });
    //         if ($request->input('transport') == 1) 
    //         $data->whereHas('ItemTypeObj', function ($query) {
    //             $query->whereIn('Code', ['Transport']);
    //         });
    //         if ($request->input('hotel') == 1) {
    //             $data->whereHas('ItemGroupObj', function ($query) {
    //                 $itemtype = ItemType::where('Code','hotel')->first();
    //                 $query->where('ItemType', $itemtype->Oid);
    //             });
    //         }
    //         if ($request->input('product') == 1) 
    //         $data->whereHas('ItemTypeObj', function ($query) {
    //             $query->whereIn('Code', ['Product']);
    //         });
    //         if ($request->input('production') == 1) 
    //         $data->whereHas('ItemTypeObj', function ($query) {
    //             $query->whereIn('Code', ['Production']);
    //         });
    //         if ($request->input('glass') == 1) 
    //         $data->whereHas('ItemTypeObj', function ($query) {
    //             $query->whereIn('Code', ['Glass']);
    //         });
    //         if ($user->BusinessPartner) $data = $data->where('PurchaseBusinessPartner', $user->BusinessPartner);
    //         $data = $data->orderBy('Name')->get();
            
    //         return $data;
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_NOT_FOUND
    //         );
    //     }
    // }

    public function getPriceForItem(Request $request) {
        if (!$request->has('item')) return 0;
        $item = Item::with('SalesCurrencyObj','PurchaseCurrencyObj')->findOrFail($request->input('item'));
        $currency = $request->input('currency') ?: company()->Currency;
        $typed = $request->input('typed') ?: 'sales';
        
        $businessPartner = null;
        if ($request->has('businesspartner')) $businessPartner = $request->input('businesspartner');
        if (!$businessPartner) {
            if ($typed == 'sales') return $item->SalesCurrencyObj->toBaseAmount($item->SalesAmount);
            else return $item->PurchaseCurrencyObj->toBaseAmount($item->PurchaseAmount);
        } elseif ($typed == 'sales') {
            $check = DB::select("SELECT p.Currency, d.Price
                FROM trdsalesinvoice p LEFT OUTER JOIN trdsalesinvoicedetail d ON d.SalesInvoice = p.Oid
                WHERE p.BusinessPartner = '{$businessPartner}'
                ORDER BY p.Date DESC LIMIT 1");
            if ($check) {
                $check = $check[0];
                $cur = Currency::findOrFail($check->Currency);
                return $cur->toBaseAmount($item->Price);
            }
            return 0;
        } else {
            $check = DB::select("SELECT p.Currency, d.Price
                FROM trdpurchaseorder p LEFT OUTER JOIN trdpurchaserequestorder d ON d.PurchaseRequest = p.Oid
                WHERE p.Supplier = '{$businessPartner}'
                ORDER BY p.Date DESC LIMIT 1");
            if ($check) {
                $check = $check[0];
                $cur = Currency::findOrFail($check->Currency);
                return $cur->toBaseAmount($item->Price);
            }
            return 0;
        }
        return 0;
    }
    
    public function show($data)
    {
        try {
            $itemType = ItemType::where('Code','Attraction')->first();
            $data = Item::with(['POSItemServiceObj','ProductionItemObj','ProductionItemGlassObj','ProductionItemGlassObj.ProductionThicknessObj',
            'FeatureInfos','ItemProcess','ItemProcess.ProductionProcessObj','ItemProcess.ProductionPriceObj','PurchaseBusinessPartnerObj','ItemStockReplacementObj'])->where('Oid',$data)->first();
            
            $details = Item::with(['Details.TravelItemHotelObj','Details.TravelItemTransportObj','Details.TravelItemObj'])
                ->where('ParentOid', $data->Oid)->where('IsDetail', 1)
                ->whereHas('ItemTypeObj', function ($query) {
                    $query->where('Code', '!=', 'Attraction')
                        ->where('Code', '!=', 'ApitudeH')
                        ->where('Code', '!=', 'Ferry')
                        ->where('Code', '!=', 'Globaltix');
                })->orderBy('Subtitle')->get();
                logger($details);
            $data->Details = $details;
            if ($data->ProductionItemObj) {
                $data->FeetConverted = $data->ProductionItemObj->FeetConverted ?: null;
                $data->RequireNext  = $data->ProductionItemObj->RequireNext ?: null;
            }
            if ($data->ProductionItemGlassObj) {
                $data->ProductionThickness = $data->ProductionItemGlassObj->ProductionThickness ?: null;
                $data->ProductionThicknessObj = $data->ProductionItemGlassObj->ProductionThicknessObj ?: null;
                $data->ProductionThicknessName  = $data->ProductionItemGlassObj->ProductionThicknessObj->Name ?: null;
            }

            foreach($data->FeatureInfos as $row) {
                $row->FeatureInfoName = $row->FeatureInfoObj->Name;
            }

            // return $data;
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function viewSalesAmount(Request $request)
    {
        try {         
            $user = Auth::user()->Oid;

            $itemTypeHotel = ItemType::where('Code','Hotel')->firstOrFail();
            $itemTypeTransport = ItemType::where('Code','Transport')->firstOrFail();
            $itemTypeTravel = ItemType::where('Code','Travel')->firstOrFail();

            $field = ['Oid', 'Code', 'Name','SalesAmount'];
            $data = Item::where('Oid', $request->input('item'))->firstOrFail();
            $type = $data->ItemType;

            if ($request->has('salesinvoice')) {
                $salesInvoice = SalesInvoice::where('Oid', $request->input('salesinvoice'))->firstOrFail();
                $date = $salesInvoice->Date;
            }else{
                $date = Carbon::now();
            }
            if ($request->has('pos')) {
                $pos = PointOfSale::where('Oid', $request->input('pos'))->firstOrFail();
                $date = $pos->Date;
            }else{
                $date = Carbon::now();
            }
            $age = 20;
            if($type == $itemTypeHotel->Oid || $type == $itemTypeTransport->Oid){
                $data = Item::where('Oid', $request->input('item'))->get();
                foreach($data as $row){
                    $row->setVisible($field);
                    $row->SalesAmount = $row->getSalesAmountByDay($date,$user,$type='FIT');  
                }
            }else if($type == $itemTypeTravel->Oid){
                $data = Item::where('Oid', $request->input('item'))->get();
                foreach($data as $row){
                    $row->setVisible($field);
                    $row->SalesAmount = $row->getSalesAmountByAge($age,$user,$type='FIT');  
                }
            }else {
                $data = Item::where('Oid', $request->input('item'))->get();
                foreach($data as $row){
                    $row->setVisible($field);
                    $row->SalesAmount = $row->getSalesAmount($user);  
                }
            }
            
            return $data;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function viewPurchaseAmount(Request $request)
    {
        try {         
            $user = Auth::user()->Oid;

            $itemTypeHotel = ItemType::where('Code','Hotel')->firstOrFail();
            $itemTypeTransport = ItemType::where('Code','Transport')->firstOrFail();
            $itemTypeTravel = ItemType::where('Code','Travel')->firstOrFail();

            $field = ['Oid', 'Code', 'Name','PurchaseAmount'];
            $data = Item::where('Oid', $request->input('item'))->firstOrFail();
            $type = $data->ItemType;

            if ($request->has('purchaseinvoice')) {
                $purchaseInvoice = PurchaseInvoice::where('Oid', $request->input('purchaseinvoice'))->firstOrFail();
                $date = $purchaseInvoice->Date;
            }else{
                $date = Carbon::now();
            }
            $age = 20;
            if($type == $itemTypeHotel->Oid || $type == $itemTypeTransport->Oid){
                $data = Item::where('Oid', $request->input('item'))->get();
                foreach($data as $row){
                    $row->setVisible($field);
                    $row->PurchaseAmount = $row->getPurchaseAmountByDay($date,$user);  
                }
            }else if($type == $itemTypeTravel->Oid){
                $data = Item::where('Oid', $request->input('item'))->get();
                foreach($data as $row){
                    $row->setVisible($field);
                    $row->PurchaseAmount = $row->getPurchaseAmountByAge($age,$user);  
                }
            }else {
                $data = Item::where('Oid', $request->input('item'))->get();
                foreach($data as $row){
                    $row->setVisible($field);
                    $row->PurchaseAmount = $row->getPurchaseAmount($user);  
                }
            }
            
            return $data;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function destroy(Item $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->FeatureInfos()->delete();
                // $data->Details()->delete();
                $data->POSItemServiceObj()->delete();
                $data->ProductionItemObj()->delete();
                $data->ProductionItemGlassObj()->delete();
                $data->ItemProcess()->delete();
                // $data->delete();
                $gcrecord = now()->format('ymdHi');
                $data->GCRecord = $gcrecord;
                $data->Code = substr($data->Code,0,39).' '.now()->format('ymdHi');
                $data->Name = $data->Name.' '.now()->format('ymdHi');
                $data->save();
                foreach ($data->Details as $row) {
                    $row->GCRecord = $gcrecord;
                    $row->Code = substr($row->Code,0,39).' '.now()->format('ymdHi');
                    $row->Name = $row->Name.' '.now()->format('ymdHi');
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

    public function allGenerateBarcode(Request $request){
        $query = "SELECT RIGHT(Barcode,LENGTH(Barcode)-1) AS lastBarcode FROM mstitem WHERE LENGTH(Barcode) = 7 AND LEFT(Barcode,2)='A0' ORDER BY Barcode DESC LIMIT 1";
        $dataBarcode = DB::select($query);
        if($dataBarcode) $no = is_numeric($dataBarcode[0]->lastBarcode) ? $dataBarcode[0]->lastBarcode : 1;
        else $no = 1;
        
        $data = Item::whereNull('Barcode')->whereNull('GCRecord')->get();
        foreach($data as $row) {
            $barcode = (intval($no))+1;
            $barcode = str_replace(",","",$barcode);
            $numlength = strlen($barcode);
            $length = 6 - $numlength;
            $nol = "";
            for($i=1;$i<=$length;$i++) $nol .= '0';
            $row->Code = 'A'.$nol.$no;
            $row->Barcode = 'A'.$nol.$no;
            $row->save();
            $no = $no + 1;
        }
        return response()->json(
            $data->count().' records updated', Response::HTTP_NO_CONTENT
        );
    }

    public function save(Request $request, $Oid = null)
    {
        $query = "SELECT RIGHT(Barcode,LENGTH(Barcode)-1) AS lastBarcode FROM mstitem WHERE LENGTH(Barcode) = 7 AND LEFT(Barcode,2)='A0' ORDER BY Barcode DESC LIMIT 1";
        $getBarcode = DB::select($query);
        $query = "SELECT RIGHT(Code,LENGTH(Code)-1) AS lastCode FROM mstitem WHERE LENGTH(Code) = 7 AND LEFT(Code,2)='A0' ORDER BY Code DESC LIMIT 1";
        $getCode = DB::select($query);
        
        if ($getBarcode) $getBarcode = is_numeric($getBarcode[0]->lastBarcode) ? intval($getBarcode[0]->lastBarcode) +1 : 0;
        if ($getCode) $getCode = is_numeric($getCode[0]->lastCode) ? (intval($getCode[0]->lastCode))+1 : 0;
        if($getBarcode){
            $numberBarcode = $getBarcode > $getCode ? $getBarcode : $getCode;
            $replaceNumber = str_replace(",","",$numberBarcode);

            $barcode = $replaceNumber;
            $numlength = strlen($barcode);
            $length = 6 - $numlength;
            $nol = "";
            for($i=1;$i<=$length;$i++)
            {
                $nol .= '0';
            }
            $resultBarcode = 'A'.$nol.$barcode;
        }else{
            $resultBarcode = null;
        }

        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        $messsages = array(
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Name.required'=>__('_.Name').__('error.required'),
            'Name.max'=>__('_.Name').__('error.max'),
            'ItemGroup.required'=>__('_.ItemGroup').__('error.required'),
            'ItemGroup.exists'=>__('_.ItemGroup').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',
            'ItemGroup' => 'required|exists:mstitemgroup,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            $excluded = ['Image1','Image2','Image3','Image4','Image5','Image6','Image7','Image8']; 
            $data = Item::where('Code','aaaaaaaaaaaaaaaaaaaaaaa')->first();
            DB::transaction(function () use ($request, &$data, $Oid, $excluded, $resultBarcode) {
                $company = Auth::user()->CompanyObj;
                if (!$Oid) {
                    logger('5 '.$request->ItemType);
                    $data = new Item();
                    $itemType = ItemType::findOrFail($request->ItemType)->Code;
                    $request->IsUsingPriceMethod = 1;
                    if ($company->IsAutoGenerateBarcode == true) {
                        $request->Code = $resultBarcode;
                        $request->Barcode = $resultBarcode;
                    }
                } else {
                    logger(7);
                    $data = Item::findOrFail($Oid);
                    $itemType = ItemType::findOrFail($data->ItemType)->Code;
                }
                $itemGroup = ItemGroup::findOrFail($request->ItemGroup);
                if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                dd($request->Code);
                if (company()->IsItemAutoGenerateNameFromItemGroup) $request->Name = $itemGroup ? $itemGroup->Name : null;
                if (!isset($request->Slug)) $request->Slug = $request->Name ?: null;
                if (!isset($request->Name)) $request->NameEN = $request->Name ?: null;
                if (!isset($request->Description)) $request->Description = null;
                if (!isset($request->Description)) $request->DescriptionEN = $request->Description ?: null;
                if (!isset($request->ItemAccountGroup)) $request->ItemAccountGroup = $itemGroup->ItemAccountGroup ?: null;
                $iag = ItemAccountGroup::findOrFail($request->ItemAccountGroup);
                $city = null;
                if (isset($request->PurchaseBusinessPartner)) $city = BusinessPartner::where('Oid',$request->PurchaseBusinessPartner)->first()->City;
                if (!isset($request->ItemUnit)) $request->ItemUnit = $company->ItemUnit ?: null;
                if (!isset($request->City)) $request->City = $city ?: $company->City;
                if (!isset($request->IsActive)) $request->IsActive = 1;
                if (!isset($request->PurchaseCurrency)) $request->PurchaseCurrency = $iag->PurchaseCurrency ?: $company->Currency;
                if (!isset($request->SalesCurrency)) $request->SalesCurrency = $iag->SalesCurrency ?: $company->Currency;
                if (!isset($request->IsPurchase)) $request->IsPurchase = $iag->IsPurchase ?: 1;
                if (!isset($request->IsSales)) $request->IsSales = $iag->IsSales ?: 1;
                if (!isset($request->PurchaseAmount)) $request->PurchaseAmount = 0;
                if (!isset($request->UsualAmount)) $request->UsualAmount = $request->SalesAmount ?: 0;
                if (!isset($request->SalesAmount)) $request->SalesAmount = $request->UsualAmount ?: 0;
                if (!isset($request->SalesAmount1)) $request->SalesAmount1 = $request->SalesAmount ?: 0;
                if (!isset($request->SalesAmount2)) $request->SalesAmount2 = $request->SalesAmount ?: 0;
                if (!isset($request->SalesAmount3)) $request->SalesAmount3 = $request->SalesAmount ?: 0;
                if (!isset($request->SalesAmount4)) $request->SalesAmount4 = $request->SalesAmount ?: 0;
                if (!isset($request->SalesAmount5)) $request->SalesAmount5 = $request->SalesAmount ?: 0;
                if (!isset($request->IsStock)) $request->IsStock = 1;
                $enabled = ['Code','Name','Subtitle','Barcode','Slug','Note','ItemUnit','ItemGroup','ItemAccountGroup','City','IsUsingPriceMethod',
                    'IsPurchase','PurchaseBusinessPartner','PurchaseCurrency','PurchaseAmount',
                    'IsSales','SalesCurrency','UsualAmount','SalesAmount','SalesAmount1','SalesAmount2','SalesAmount3','SalesAmount4','SalesAmount5',
                    'NameEN','NameID','NameZH','NameTH','Description','DescriptionID','DescriptionEN','DescriptionZH','DescriptionID',
                    'APIType','APICode','Sequence','IsAllotment','IsStock','QauantitySold','InternalSold','QuantityReview','InternalRating','ETicketMergeType',
                    'IsParent','IsDetail','Featured','CountReviews','LastPurchased','ItemType','Barcode','ItemStockReplacement','IsAutoGenerateBarcode','Initial','IsActive'];
                logger(15);
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                logger(16);
                if (isset($request->Image1->base64)) $data->Image1 = $this->fileCloudService->uploadImage($request->Image1, $data->Image1);
                if (isset($request->Image2->base64)) $data->Image2 = $this->fileCloudService->uploadImage($request->Image2, $data->Image2);
                if (isset($request->Image3->base64)) $data->Image3 = $this->fileCloudService->uploadImage($request->Image3, $data->Image3);
                if (isset($request->Image4->base64)) $data->Image4 = $this->fileCloudService->uploadImage($request->Image4, $data->Image4);
                if (isset($request->Image5->base64)) $data->Image5 = $this->fileCloudService->uploadImage($request->Image5, $data->Image5);
                if (isset($request->Image6->base64)) $data->Image6 = $this->fileCloudService->uploadImage($request->Image6, $data->Image6);
                if (isset($request->Image7->base64)) $data->Image7 = $this->fileCloudService->uploadImage($request->Image7, $data->Image7);
                if (isset($request->Image8->base64)) $data->Image8 = $this->fileCloudService->uploadImage($request->Image8, $data->Image8);
                logger($data->Details()->count());
                
                $data->IsParent = false;
                if ($data->Details()->count() == 0) {
                    // $itemtypeattr = ItemType::where('Code','Attraction')->first()->Oid;
                    // $itemtype = ItemGroup::findOrFail($request->ItemGroup)->ItemType;
                    // if ($itemtype == $itemtypeattr) {
                    //     $data->IsParent = true;
                    // }else{
                        $data->IsParent = false;
                    // }
                } else {                   
                    $data->IsParent = true;                    
                }
                $data->IsDetail = false;
                $data->ObjectType = 79;
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'mstitem');

                $query = "INSERT INTO mstitemecommerce (Oid, Company, Item, ECommerce, IsActive)
                    SELECT UUID(), i.Company,'".$data->Oid."', i.Oid, 0
                    FROM mstecommerce i 
                    LEFT OUTER JOIN mstitemecommerce ie ON i.Oid = ie.ECommerce AND ie.Item = '".$data->Oid."'
                    WHERE ie.Oid IS NULL";

                DB::insert($query);

                logger(17);
                
                if ($itemType == "Service") {
                    logger(18);
                    $dataService = ItemService::where('Oid',$data->Oid)->first();
                    if (!$dataService) {
                        logger(19);
                        $dataService = new ItemService();
                        $dataService->Oid = $data->Oid;
                    }
                    logger(20);
                    if (!isset($request->DateStart)) $request->DateStart = now() ?: null;
                    if (!isset($request->DateEnd)) $request->DateEnd = now() ?: null;
                    logger(21);
                    $enabled = ['DateStart','DateEnd','Phone','Address','Longitude','Latitude','Expiry','MinQuantity','CutOffDay',
                        'DescCaptionEN','DescCaptionZH','DescCaptionID','DescIncludedEN','DescIncludedZH','DescIncludedID','DescTermConditionEN','DescTermConditionZH','DescTermConditionID',
                        'DescRedemptionEN','DescRedemptionZH','DescRedemptionID','DescCancelationEN','DescCancelationZH','DescCancelationID','DescLocationEN','DescLocationZH','DescLocationID',
                        'InputDate','InputTitle1','InputTitle2','InputTitle3','InputPassenger','Stock','MaxQuantity','YoutubeURL','KeywordEN','KeywordCN',
                        'CountRating5','CountRating4','CountRating3','CountRating2','CountRating1',
                        'CountRatingClean','CountRatingLocation','CountRatingService','CountRatingFacilities','CountRatingComfort','PaxType','GSTApplicable','IsETicketGenerated',
                        'IsETicketUpload','IsRedemptionTicket','IsFloatingDeposit','LoaAmendCancel','LimitAgeChild'];               
                    foreach ($request as $field => $key) {
                        if (in_array($field, $enabled)) $dataService->{$field} = $request->{$field};
                    }

                    if (isset($request->Image1->base64)) $data->Image1 = $this->fileCloudService->uploadImage($request->Image1, $data->Image1);
                    if (isset($request->Image2->base64)) $data->Image2 = $this->fileCloudService->uploadImage($request->Image2, $data->Image2);
                    if (isset($request->Image3->base64)) $data->Image3 = $this->fileCloudService->uploadImage($request->Image3, $data->Image3);
                    if (isset($request->Image4->base64)) $data->Image4 = $this->fileCloudService->uploadImage($request->Image4, $data->Image4);
                    if (isset($request->Image5->base64)) $data->Image5 = $this->fileCloudService->uploadImage($request->Image5, $data->Image5);
                    if (isset($request->Image6->base64)) $data->Image6 = $this->fileCloudService->uploadImage($request->Image6, $data->Image6);
                    if (isset($request->Image7->base64)) $data->Image7 = $this->fileCloudService->uploadImage($request->Image7, $data->Image7);
                    if (isset($request->Image8->base64)) $data->Image8 = $this->fileCloudService->uploadImage($request->Image8, $data->Image8);
                    
                    logger(22);
                    $dataService->save();
                    logger(23);
                }

                if ($itemType == "Restaurant") {
                    logger(18);
                    $dataService = ItemService::where('Oid',$data->Oid)->first();
                    if (!$dataService) {
                        logger(19);
                        $dataService = new ItemService();
                        $dataService->Oid = $data->Oid;
                    }
                    logger(20);
                    if (!isset($request->DateStart)) $request->DateStart = now() ?: null;
                    if (!isset($request->DateEnd)) $request->DateEnd = now() ?: null;
                    logger(21);
                    $enabled = ['Phone','Address','LoaAmendCancel'];               
                    foreach ($request as $field => $key) {
                        if (in_array($field, $enabled)) $dataService->{$field} = $request->{$field};
                    }
                    
                    logger(22);
                    $dataService->save();
                    logger(23);
                }

                if ($itemType == "Flight") {
                    logger(18);
                    $dataService = ItemService::where('Oid',$data->Oid)->first();
                    if (!$dataService) {
                        logger(19);
                        $dataService = new ItemService();
                        $dataService->Oid = $data->Oid;
                    }
                    logger(20);

                    $enabled = ['FlightNumber'];               
                    foreach ($request as $field => $key) {
                        if (in_array($field, $enabled)) $dataService->{$field} = $request->{$field};
                    }
                    
                    logger(22);
                    $dataService->save();
                    logger(23);
                }

                if ($itemType == "Production") {
                    logger(218);
                    $dataProduction = ProductionItem::where('Oid',$data->Oid)->first();
                    if (!$dataProduction) {
                        logger(219);
                        $dataProduction = new ProductionItem();
                        $dataProduction->Oid = $data->Oid;
                    }
                    logger(220);
                    $enabled = ['FeetConverted','RequireNext','IsFreeForZeroPrice'];                
                    foreach ($request as $field => $key) {
                        if (in_array($field, $enabled)) $dataProduction->{$field} = $request->{$field};
                    }
                    logger(222);
                    $dataProduction->save();
                    $query = "INSERT INTO prditemprocess (Oid, Company, Item,ProductionProcess,Sequence,Note,Valid)
                        SELECT UUID(), p.Company,'".$dataProduction->Oid."', p.Oid,p.Sequence,p.Remark,0
                        FROM prdprocess p
                        WHERE NOT EXISTS (SELECT * FROM prditemprocess t WHERE t.Item = '{$dataProduction->Oid}')";
                    DB::insert($query);
                    logger(223);
                }

                if ($itemType == "Glass") {
                    logger(218);
                    $dataGlass = ProductionItemGlass::where('Oid',$data->Oid)->first();
                    if (!$dataGlass) {
                        logger(219);
                        $dataGlass = new ProductionItemGlass();
                        $dataGlass->Oid = $data->Oid;
                    }
                    logger(220);
                    $enabled = ['ProductionThickness'];                
                    foreach ($request as $field => $key) {
                        if (in_array($field, $enabled)) $dataGlass->{$field} = $request->{$field};
                    }
                    logger(222);
                    $dataGlass->save();
                    logger(223);
                }
                
                $data->FeatureInfos()->delete();
                if(isset($request->FeatureInfos)) {
                    $rows = [];  
                    foreach ($request->FeatureInfos as $row) {
                        // $row->POSFeatureInfo = FeatureInfo::findOrFail($row->POSFeatureInfo)->Oid;
                        $rows[] = new FeatureInfoItem([
                            'POSFeatureInfo' => $row->POSFeatureInfo,
                            'DescriptionID' => $row->DescriptionID,
                            'DescriptionEN' => $row->DescriptionEN,
                            'DescriptionZH' => $row->DescriptionZH,
                            'DescriptionTH' => $row->DescriptionTH,                       
                        ]);
                    }
                    $data->FeatureInfos()->saveMany($rows);
                    $data->load('FeatureInfos');
                    $data->fresh();
                }

                foreach($data->Details() as $row){                    
                    $row->PurchaseBusinessPartner = $request->BusinessPartner;
                    $row->ParentOid = $data->Oid;
                    $row->ItemGroup = $data->ItemGroup;
                    $row->ItemAccountGroup = $data->ItemAccountGroup;
                    $row->ItemUnit = $data->ItemUnit;
                    $row->PurchaseCurrency = $data->PurchaseCurrency;
                    $row->SalesCurrency = $data->SalesCurrency;
                    $row->City = $data->City;
                    $row->APIType = $data->APIType;
                    $row->IsAllotment = $data->IsAllotment;
                    $row->IsStock = $data->IsStock;
                    $row->save();
                }

                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            $data = Item::with('POSItemServiceObj')->findOrFail($data->Oid);
            // $data = (new ItemResource($data))->type('detail');
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

    public function listitemprocess(Request $request)
    {
        try {            
            $item = $request->input('item');
            $data = ProductionItemProcess::with(['ProductionProcessObj'])->where('Item',$item);
            $data = $data->orderBy('Sequence', 'asc')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveitemprocess(Request $request)
    {        
        $item = $request->input('item');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    
        try {            
            $data = Item::where('Oid',$item)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid','ItemProcess','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();        

                if ($data->ItemProcess()->count() != 0) {
                    foreach ($data->ItemProcess as $rowdb) {
                        $found = false;               
                        foreach ($request->ItemProcess as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ProductionItemProcess::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->ItemProcess) {
                    $details = [];  
                    $disabled = ['Oid','Item','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->ItemProcess as $row) {
                        if (isset($row->Oid)) {
                            $detail = ProductionItemProcess::findOrFail($row->Oid);
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $detail->{$field} = $row->{$field};
                            }
                            $detail->save();
                        } else {
                            $arr = [];
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;                            
                                $arr = array_merge($arr, [
                                    $field => $row->{$field},
                                ]);
                            }
                            $details[] = new ProductionItemProcess($arr);
                        }
                    }
                    $data->ItemProcess()->saveMany($details);
                    $data->load('ItemProcess');
                    $data->fresh();
                }

                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new ProductionOrderResource($data))->type('detail');
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

    public function listitemecommerce(Request $request)
    {
        try {            
            $item = $request->input('item');
            $data = ItemECommerce::with(['ECommerceObj'])->where('Item',$item);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveitemecommerce(Request $request)
    {        
        $item = $request->input('item');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    
        try {            
            $data = Item::where('Oid',$item)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid','ItemECommerces','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();        

                if ($data->ItemECommerces()->count() != 0) {
                    foreach ($data->ItemECommerces as $rowdb) {
                        $found = false;               
                        foreach ($request->ItemECommerces as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ItemECommerce::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->ItemECommerces) {
                    $details = [];  
                    $disabled = ['Oid','Item','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->ItemECommerces as $row) {
                        if (isset($row->Oid)) {
                            $detail = ItemECommerce::findOrFail($row->Oid);
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $detail->{$field} = $row->{$field};
                            }
                            $detail->save();
                        } else {
                            $arr = [];
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $arr = array_merge($arr, [
                                    $field => $row->{$field},
                                ]);
                            }
                            $details[] = new ItemECommerce($arr);
                        }
                    }
                    $data->ItemECommerces()->saveMany($details);
                    $data->load('ItemECommerces');
                    $data->fresh();
                }

                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new ProductionOrderResource($data))->type('detail');
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

    public function featurelist(Request $request) {        
        try {
            $data = FeatureInfoItem::where('Item',$request->input('item'))->get();
            foreach($data as $row) {
                $row->FeatureInfoObj = FeatureInfo::where('Oid',$row->POSFeatureInfo)->first();
                $row->FeatureInfoName = $row->FeatureInfoObj ? $row->FeatureInfoObj->Name : null;
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function featuresave(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'POSFeatureInfo.required'=>__('_.FeatureInfo').__('error.required'),
            'POSFeatureInfo.exists'=>__('_.FeatureInfo').__('error.exists'),
            // 'Item.required'=>__('_.Item').__('error.required'),
            // 'Item.exists'=>__('_.Item').__('error.exists'),
        );
        $rules = array(
            'POSFeatureInfo' => 'required|exists:posfeatureinfo,Oid',
            // 'Item' => 'required|exists:mstitem,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            if (!$Oid) $data = new FeatureInfoItem();
            else $data = FeatureInfoItem::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });
            $data = FeatureInfoItem::with('FeatureInfoObj')->findOrFail($data->Oid);

            // $data = (new FeatureInfoItem($data))->type('detail');
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
    
    public function detailfeaturelist(Request $request) {
        try {
            $query = "SELECT fii.*, pfi.Name AS POSFeatureInfoName FROM posfeatureinfoitem fii 
                LEFT OUTER JOIn mstitem i ON fii.Item = i.Oid
                LEFT OUTER JOIn posfeatureinfo pfi ON fii.POSFeatureInfo = pfi.Oid
                WHERE i.ParentOid = '{$request->input('item')}'";
            $data = DB::select($query);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function featuredestroy(FeatureInfoItem $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->delete();
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

    public function listHotelPrice(Request $request) {        
        try {
            $data = TravelItemHotelPrice::where('Item',$request->input('item'))->get();
            foreach ($data as $row){
                $row->ItemName = $row->ItemObj ? $row->ItemObj->Name.' - '.$row->ItemObj->Code : null;
                $row->CurrencyName = $row->CurrencyObj ? $row->CurrencyObj->Code : null;
                unset($row->ItemObj);
                unset($row->CurrencyObj);
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function detailListHotelPrice($Oid) {        
        try {
            $data = TravelItemHotelPrice::findOrFail($Oid);
            $data->ItemName = $data->ItemObj ? $data->ItemObj->Name.' - '.$data->ItemObj->Code : null;
            $data->CurrencyName = $data->CurrencyObj ? $data->CurrencyObj->Code : null;
            unset($data->ItemObj);
            unset($data->CurrencyObj);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveHotelPrice(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'Item.required'=>__('_.Item').__('error.required'),
            'Item.exists'=>__('_.Item').__('error.exists'),
        );
        $rules = array(
            'Item' => 'required|exists:mstitem,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            if (!$Oid) $data = new TravelItemHotelPrice();
            else $data = TravelItemHotelPrice::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

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

    public function destroyHotelPrice(TravelItemHotelPrice $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->delete();
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

    public function viewEticket($Oid = null)
    {        
        try {            
            $data = ETicket::where('PurchaseEticket',$Oid)->get();
            
            return response()->json(
                $data, Response::HTTP_OK
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
                $files = $request->file('EticketFile');
                $data = PurchaseEticket::findOrFail($Oid);
                
                foreach ($files as $key => $value) {
                    $name = $value->getClientOriginalName();
                    $eticket = $this->posETicketService->create($value, [ 
                        'PurchaseEticket' => $Oid, 
                        'Item' => $data->Item, 
                        'FileName' => $name, 
                        'CostPrice' => $data->Amount,
                        'DateExpiry' => null,
                    ]);
                    $result[] = $eticket->Oid;
                }
               
            });

            $data = ETicket::where('PurchaseEticket',$Oid)->get();
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

    public function deleteEticket($Oid = null)
    {        
        try {            
            DB::transaction(function () use ($Oid) {
                $data = ETicket::findOrFail($Oid);
                $data->delete();
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
    
    public function getPriceMethod($Oid = null)
    {
        try {            
            $item = Item::with(['SalesAddMethodObj','SalesAdd1MethodObj','SalesAdd2MethodObj','SalesAdd3MethodObj','SalesAdd4MethodObj','SalesAdd5MethodObj'])
                ->addSelect('IsUsingPriceMethod','SalesAddMethod','SalesAddAmount1','SalesAddAmount2','SalesAdd1Method','SalesAdd1Amount1','SalesAdd1Amount2',
                'SalesAdd2Method','SalesAdd2Amount1','SalesAdd2Amount2','SalesAdd3Method','SalesAdd3Amount1','SalesAdd3Amount2','SalesAdd4Method','SalesAdd4Amount1',
                'SalesAdd4Amount2','SalesAdd5Method','SalesAdd5Amount1','SalesAdd5Amount2')
                ->findOrFail($Oid);
            return response()->json(
                $item,
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function savePriceMethod(Request $request, $Oid = null)
    {  
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {            
            if (!$Oid) throw new \Exception('Data is failed to be saved');
            $data = Item::with('Details')->findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if ($request->IsUsingPriceMethod == null || ($request->IsUsingPriceMethod == false)){ 
                    //decide to manual price and not using itemtype
                    $data->SalesAddMethod = $request->SalesAddMethod;
                    $data->SalesAddAmount1 = $request->SalesAddAmount1;
                    $data->SalesAddAmount2 = $request->SalesAddAmount2;
                    $data->SalesAdd1Method = $request->SalesAdd1Method;
                    $data->SalesAdd1Amount1 = $request->SalesAdd1Amount1;
                    $data->SalesAdd1Amount2 = $request->SalesAdd1Amount2;
                    $data->SalesAdd2Method = $request->SalesAdd2Method;
                    $data->SalesAdd2Amount1 = $request->SalesAdd2Amount1;
                    $data->SalesAdd2Amount2 = $request->SalesAdd2Amount2;
                    $data->SalesAdd3Method = $request->SalesAdd3Method;
                    $data->SalesAdd3Amount1 = $request->SalesAdd3Amount1;
                    $data->SalesAdd3Amount2 = $request->SalesAdd3Amount2;
                    $data->SalesAdd4Method = $request->SalesAdd4Method;
                    $data->SalesAdd4Amount1 = $request->SalesAdd4Amount1;
                    $data->SalesAdd4Amount2 = $request->SalesAdd4Amount2;
                    $data->SalesAdd5Method = $request->SalesAdd5Method;
                    $data->SalesAdd5Amount1 = $request->SalesAdd5Amount1;
                    $data->SalesAdd5Amount2 = $request->SalesAdd5Amount2;
                    $data->save();
                    if ($data->Details()->count() != 0) {
                        foreach ($data->Details as $row) {
                            if($row->IsUsingPriceMethod){
                                //update only those detail not manual price
                                $detail = Item::findOrFail($row->Oid);
                                $itemType = $detail->ItemType ? $detail->ItemTypeObj->Code : $detail->ItemGroupObj->ItemTypeObj->Code;
                                if($itemType == 'Outbound'){
                                    $dataOutbound = $detail->TravelItemOutboundObj;
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesSGL = $this->calcPriceOB('', $data, $dataOutbound,'SGL');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesTWN = $this->calcPriceOB('', $data, $dataOutbound,'TWN');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesTRP = $this->calcPriceOB('', $data, $dataOutbound,'TRP');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesQuad = $this->calcPriceOB('', $data, $dataOutbound,'Quad');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesQuint = $this->calcPriceOB('', $data, $dataOutbound,'Quint');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesCHT = $this->calcPriceOB('', $data, $dataOutbound,'CHT');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesCWB = $this->calcPriceOB('', $data, $dataOutbound,'CWB');
                                    if (isset($data->SalesAddMethod)) $dataOutbound->SalesCNB = $this->calcPriceOB('', $data, $dataOutbound,'CNB');

                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesSGL1 = $this->calcPriceOB('1', $data, $dataOutbound,'SGL');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesTWN1 = $this->calcPriceOB('1', $data, $dataOutbound,'TWN');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesTRP1 = $this->calcPriceOB('1', $data, $dataOutbound,'TRP');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesQuad1 = $this->calcPriceOB('1', $data, $dataOutbound,'Quad');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesQuint1 = $this->calcPriceOB('1', $data, $dataOutbound,'Quint');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesCHT1 = $this->calcPriceOB('1', $data, $dataOutbound,'CHT');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesCWB1 = $this->calcPriceOB('1', $data, $dataOutbound,'CWB');
                                    if (isset($data->SalesAdd1Method)) $dataOutbound->SalesCNB1 = $this->calcPriceOB('1', $data, $dataOutbound,'CNB');

                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesSGL2 = $this->calcPriceOB('2', $data, $dataOutbound,'SGL');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesTWN2 = $this->calcPriceOB('2', $data, $dataOutbound,'TWN');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesTRP2 = $this->calcPriceOB('2', $data, $dataOutbound,'TRP');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesQuad2 = $this->calcPriceOB('2', $data, $dataOutbound,'Quad');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesQuint2 = $this->calcPriceOB('2', $data, $dataOutbound,'Quint');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesCHT2 = $this->calcPriceOB('2', $data, $dataOutbound,'CHT');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesCWB2 = $this->calcPriceOB('2', $data, $dataOutbound,'CWB');
                                    if (isset($data->SalesAdd2Method)) $dataOutbound->SalesCNB2 = $this->calcPriceOB('2', $data, $dataOutbound,'CNB');

                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesSGL3 = $this->calcPriceOB('3', $data, $dataOutbound,'SGL');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesTWN3 = $this->calcPriceOB('3', $data, $dataOutbound,'TWN');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesTRP3 = $this->calcPriceOB('3', $data, $dataOutbound,'TRP');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesQuad3 = $this->calcPriceOB('3', $data, $dataOutbound,'Quad');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesQuint3 = $this->calcPriceOB('3', $data, $dataOutbound,'Quint');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesCHT3 = $this->calcPriceOB('3', $data, $dataOutbound,'CHT');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesCWB3 = $this->calcPriceOB('3', $data, $dataOutbound,'CWB');
                                    if (isset($data->SalesAdd3Method)) $dataOutbound->SalesCNB3 = $this->calcPriceOB('3', $data, $dataOutbound,'CNB');

                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesSGL4 = $this->calcPriceOB('4', $data, $dataOutbound,'SGL');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesTWN4 = $this->calcPriceOB('4', $data, $dataOutbound,'TWN');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesTRP4 = $this->calcPriceOB('4', $data, $dataOutbound,'TRP');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesQuad4 = $this->calcPriceOB('4', $data, $dataOutbound,'Quad');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesQuint4 = $this->calcPriceOB('4', $data, $dataOutbound,'Quint');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesCHT4 = $this->calcPriceOB('4', $data, $dataOutbound,'CHT');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesCWB4 = $this->calcPriceOB('4', $data, $dataOutbound,'CWB');
                                    if (isset($data->SalesAdd4Method)) $dataOutbound->SalesCNB4 = $this->calcPriceOB('4', $data, $dataOutbound,'CNB');

                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesSGL5 = $this->calcPriceOB('5', $data, $dataOutbound,'SGL');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesTWN5 = $this->calcPriceOB('5', $data, $dataOutbound,'TWN');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesTRP5 = $this->calcPriceOB('5', $data, $dataOutbound,'TRP');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesQuad5 = $this->calcPriceOB('5', $data, $dataOutbound,'Quad');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesQuint5 = $this->calcPriceOB('5', $data, $dataOutbound,'Quint');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesCHT5 = $this->calcPriceOB('5', $data, $dataOutbound,'CHT');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesCWB5 = $this->calcPriceOB('5', $data, $dataOutbound,'CWB');
                                    if (isset($data->SalesAdd5Method)) $dataOutbound->SalesCNB5 = $this->calcPriceOB('5', $data, $dataOutbound,'CNB');

                                    $dataOutbound->save();
                                }else{
                                    if (isset($data->SalesAddMethod))  $detail->SalesAmount = $this->calcPrice('', $data, $row);
                                    if (isset($data->SalesAdd1Method)) $detail->SalesAmount1 = $this->calcPrice('1', $data, $row);
                                    if (isset($data->SalesAdd2Method)) $detail->SalesAmount2 = $this->calcPrice('2', $data, $row);
                                    if (isset($data->SalesAdd3Method)) $detail->SalesAmount3 = $this->calcPrice('3', $data, $row);
                                    if (isset($data->SalesAdd4Method)) $detail->SalesAmount4 = $this->calcPrice('4', $data, $row);
                                    if (isset($data->SalesAdd5Method)) $detail->SalesAmount5 = $this->calcPrice('5', $data, $row);

                                    $detail->save();
                                }
                            }
                        }
                    }
                } else { 
                    //decide to use from itemtype (global) - not manual price
                    $ipm = $data->ItemTypeObj->ItemPriceTypeMethodObj;
                    if ($data->Details()->count() != 0) {
                        foreach ($data->Details as $row) {
                            if($row->IsUsingPriceMethod){
                                //update only those detail not manual price, and parent also not manual
                                $detail = Item::findOrFail($row->Oid);
                                $itemType = $detail->ItemType ? $detail->ItemTypeObj->Code : $detail->ItemGroupObj->ItemTypeObj->Code;
                                if(isset($ipm)){
                                    if($itemType == 'Outbound'){
                                        $dataOutbound = $detail->TravelItemOutboundObj;
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesSGL = $this->calcPriceOB('', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesTWN = $this->calcPriceOB('', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesTRP = $this->calcPriceOB('', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesQuad = $this->calcPriceOB('', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesQuint = $this->calcPriceOB('', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCHT = $this->calcPriceOB('', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCWB = $this->calcPriceOB('', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCNB = $this->calcPriceOB('', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesSGL1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesTWN1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesTRP1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesQuad1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesQuint1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCHT1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCWB1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCNB1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesSGL2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesTWN2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesTRP2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesQuad2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesQuint2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCHT2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCWB2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCNB2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesSGL3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesTWN3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesTRP3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesQuad3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesQuint3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCHT3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCWB3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCNB3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesSGL4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesTWN4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesTRP4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesQuad4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesQuint4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCHT4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCWB4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCNB4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesSGL5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesTWN5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesTRP5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesQuad5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesQuint5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCHT5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCWB5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCNB5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CNB');
    
                                        $dataOutbound->save();
                                    }else{
                                        if (isset($ipm->SalesAddMethod))  $detail->SalesAmount = $this->calcPrice('', $ipm, $row);
                                        if (isset($ipm->SalesAdd1Method)) $detail->SalesAmount1 = $this->calcPrice('1', $ipm, $row);
                                        if (isset($ipm->SalesAdd2Method)) $detail->SalesAmount2 = $this->calcPrice('2', $ipm, $row);
                                        if (isset($ipm->SalesAdd3Method)) $detail->SalesAmount3 = $this->calcPrice('3', $ipm, $row);
                                        if (isset($ipm->SalesAdd4Method)) $detail->SalesAmount4 = $this->calcPrice('4', $ipm, $row);
                                        if (isset($ipm->SalesAdd5Method)) $detail->SalesAmount5 = $this->calcPrice('5', $ipm, $row);

                                        $detail->save();
                                    } 
                                } else {
                                    if($itemType == 'Outbound'){
                                        $dataOutbound = $detail->TravelItemOutboundObj;
                                        $dataOutbound->SalesSGL = $dataOutbound->PurchaseSGL;
                                        $dataOutbound->SalesTWN = $dataOutbound->PurchaseTWN;
                                        $dataOutbound->SalesTRP = $dataOutbound->PurchaseTRP;
                                        $dataOutbound->SalesQuad = $dataOutbound->PurchaseQuad;
                                        $dataOutbound->SalesQuint = $dataOutbound->PurchaseQuint;
                                        $dataOutbound->SalesCHT = $dataOutbound->PurchaseCHT;
                                        $dataOutbound->SalesCWB = $dataOutbound->PurchaseCWB;
                                        $dataOutbound->SalesCNB = $dataOutbound->PurchaseCNB;
                                        $dataOutbound->save();
                                    }else{
                                        $detail->SalesAmount = $detail->PurchaseAmount;
                                        $detail->SalesAmount1 = $detail->PurchaseAmount1;
                                        $detail->SalesAmount2 = $detail->PurchaseAmount2;
                                        $detail->SalesAmount3 = $detail->PurchaseAmount3;
                                        $detail->SalesAmount4 = $detail->PurchaseAmount4;
                                        $detail->SalesAmount5 = $detail->PurchaseAmount5;
                                        $detail->save();
                                    } 
                                    $data->SalesAddMethod = null;
                                    $data->SalesAddAmount1 = null;
                                    $data->SalesAddAmount2 = null;
                                    $data->SalesAdd1Method = null;
                                    $data->SalesAdd1Amount1 = null;
                                    $data->SalesAdd1Amount2 = null;
                                    $data->SalesAdd2Method = null;
                                    $data->SalesAdd2Amount1 = null;
                                    $data->SalesAdd2Amount2 = null;
                                    $data->SalesAdd3Method = null;
                                    $data->SalesAdd3Amount1 = null;
                                    $data->SalesAdd3Amount2 = null;
                                    $data->SalesAdd4Method = null;
                                    $data->SalesAdd4Amount1 = null;
                                    $data->SalesAdd4Amount2 = null;
                                    $data->SalesAdd5Method = null;
                                    $data->SalesAdd5Amount1 = null;
                                    $data->SalesAdd5Amount2 = null;
                                    $data->save();
                                }
                            }
                        }
                    }
                }
                $data->IsUsingPriceMethod = $request->IsUsingPriceMethod;
                $data->SalesAmount = $this->getPriceParent($data->Oid);
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new Item($data))->type('detail');
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
    
    public function getItemAttraction($Oid = null)
    {
        try {
            // $data = POSETicketUpload::with(['BusinessPartnerObj','ItemObj','WarehouseObj'])->where('ItemParent',$Oid)->get();
            $itemType = ItemType::where('Code','Attraction')->first();
            $data = Item::with(['PurchaseBusinessPartnerObj'])->where('ParentOid',$Oid)->where('ItemType',$itemType->Oid)->get();
            foreach($data as $row){
                $row->BusinessPartnerName = $row->PurchaseBusinessPartner ? $row->PurchaseBusinessPartnerObj->Name.' - '.$row->PurchaseBusinessPartnerObj->Code : null;
                // $row->ItemName = $row->Item ? $row->ItemObj->Name : null;
                // $row->WarehouseName = $row->Warehouse ? $row->WarehouseObj->Name : null;
                unset($row->BusinessPartnerObj);
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

    private function getPriceParent($itemParent) {

        $item = Item::where('ParentOid',$itemParent)->orderBy('SalesAmount')->first();  

        return $item->SalesAmount;
    }

    private function calcPrice($priceMethodNo, $itemPriceMethod, $item) {
        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->ParentObj->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ParentObj->SalesCurrency, $item->PurchaseAmount);
        if ($cost == 0) return 0;
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }

    private function calcPriceOB($priceMethodNo, $itemPriceMethod, $itemoutbound, $key) {
        $item = Item::findOrFail($itemoutbound->Oid);
        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->ParentObj->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ParentObj->SalesCurrency, $itemoutbound->{'Purchase'.$key});
        if ($cost == 0) return 0;
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }

    private function calcPriceTrans($priceMethodNo, $itemPriceMethod, $itemtransport, $key) {
        $item = Item::findOrFail($itemtransport->Oid);
        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ParentObj->SalesCurrency, $itemtransport->{'Purchase'.$key});
        if ($cost == 0) return 0;
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }
    

    public function saveItemAttraction(Request $request, $Oid = null)
    {  
        $itemParent = Item::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            if (!$Oid) $data = new Item();
            else $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','IsUsingPriceMethod','PurchaseAmount','IsDayMonday','IsDayTuesday',
                'IsDayWednesday','IsDayThursday','IsDayFriday','IsDaySaturday','IsDaySunday','DateStart','DateEnd'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->save();
                $data->PurchaseBusinessPartner = $request->BusinessPartner;
                $data->Code = now()->format('ymdHis').'-'.str_random(3);      
                $data->ParentOid = $itemParent->Oid;
                $data->Name = $itemParent->Name.' - '.$data->Subtitle;
                $data->ItemGroup = $itemParent->ItemGroup;
                $data->ItemAccountGroup = $itemParent->ItemAccountGroup;
                $data->ItemUnit = $itemParent->ItemUnit;
                $data->ItemType = ItemType::where('Code','Attraction')->first()->Oid;
                $data->PurchaseCurrency = $itemParent->PurchaseCurrency;
                $data->SalesCurrency = $itemParent->SalesCurrency;
                $data->City = $itemParent->City;
                $data->APIType = $itemParent->APIType;
                $data->IsAllotment = $itemParent->IsAllotment;
                $data->IsStock = $itemParent->IsStock;
                $data->IsParent = false;
                $data->IsDetail = true;
                $ipm = $itemParent->IsUsingPriceMethod ? $data->ItemTypeObj->ItemTypePriceMethodObj : $itemParent;
                if($data->IsUsingPriceMethod == true){
                    if (isset($ipm)) {
                        if (isset($ipm->SalesAddMethod)) $data->SalesAmount = $this->calcPrice('', $ipm, $data);
                        if (isset($ipm->SalesAdd1Method)) $data->SalesAmount1 = $this->calcPrice('1', $ipm, $data);
                        if (isset($ipm->SalesAdd2Method)) $data->SalesAmount2 = $this->calcPrice('2', $ipm, $data);
                        if (isset($ipm->SalesAdd3Method)) $data->SalesAmount3 = $this->calcPrice('3', $ipm, $data);
                        if (isset($ipm->SalesAdd4Method)) $data->SalesAmount4 = $this->calcPrice('4', $ipm, $data);
                        if (isset($ipm->SalesAdd5Method)) $data->SalesAmount5 = $this->calcPrice('5', $ipm, $data);
                    }else{
                        $data->SalesAmount = $data->PurchaseAmount;
                        $data->save();
                    }
                }else{
                    $data->SalesAmount = $request->SalesAmount;
                }
                $data->save();

                $itemParent->IsParent = 1;
                $itemParent->SalesAmount = $this->getPriceParent($itemParent->Oid);
                $itemParent->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new Item($data))->type('detail');
            logger($data);
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


    public function deleteAttraction($Oid = null)
    {        
        try {            
            DB::transaction(function () use ($Oid) {
                $data = Item::findOrFail($Oid);
                $data->delete();
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

    public function getItemTransport($Oid = null)
    {
        try {
            $itemType = ItemType::where('Code','Transport')->first();
            $data = Item::with(['PurchaseBusinessPartnerObj','TravelItemTransportObj','TravelItemTransportObj.TravelTransportBrandObj'])->where('ParentOid',$Oid)->where('ItemType',$itemType->Oid)->get();
            foreach($data as $row){
                $row->BusinessPartnerName = $row->PurchaseBusinessPartner ? $row->PurchaseBusinessPartnerObj->Name.' - '.$row->PurchaseBusinessPartnerObj->Code : null;
                // $row->ItemName = $row->Item ? $row->ItemObj->Name : null;
                // $row->WarehouseName = $row->Warehouse ? $row->WarehouseObj->Name : null;
                unset($row->BusinessPartnerObj);
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


    public function saveItemTransport(Request $request, $Oid = null)
    {  
        $itemParent = Item::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            if (!$Oid) $data = new Item();
            else $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','PurchaseAmount','DateStart','DateEnd','IsUsingPriceMethod'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->save();
                $data->PurchaseBusinessPartner = $request->BusinessPartner;
                $data->Code = now()->format('ymdHis').'-'.str_random(3);      
                $data->ParentOid = $itemParent->Oid;
                $data->Name = $itemParent->Name.' - '.$data->Subtitle;
                $data->ItemGroup = $itemParent->ItemGroup;
                $data->ItemAccountGroup = $itemParent->ItemAccountGroup;
                $data->ItemUnit = $itemParent->ItemUnit;
                $data->ItemType = ItemType::where('Code','Transport')->first()->Oid;
                $data->PurchaseCurrency = $itemParent->PurchaseCurrency;
                $data->SalesCurrency = $itemParent->SalesCurrency;
                $data->City = $itemParent->City;
                $data->APIType = $itemParent->APIType;
                $data->IsAllotment = $itemParent->IsAllotment;
                $data->IsStock = $itemParent->IsStock;
                $data->IsParent = false;
                $data->IsDetail = true;
                $data->save();

                $dataTransport = TravelItemTransport::where('Oid',$data->Oid)->first();  
                if (!$dataTransport) { 
                    $datadetail = new ItemDetailLink(); 
                    $datadetail->Oid = $data->Oid;
                    $datadetail->Parent = $itemParent->Oid;
                    $datadetail->save();
                    $dataTransport = new TravelItemTransport();
                    $dataTransport->Oid = $data->Oid; 
                }
                $enabled = ['Year','Capacity','PurchaseHourly','PurchaseWeekday','PurchaseWeekend','TravelTransportBrand'];                
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $dataTransport->{$field} = $request->{$field};
                }

                $ipm = $itemParent->IsUsingPriceMethod ? $data->ItemTypeObj->ItemTypePriceMethodObj : $itemParent;
                if($data->IsUsingPriceMethod == true){
                    if (isset($ipm)) {
                        if (isset($ipm->SalesAddMethod)) $dataTransport->SalesHourly = $this->calcPriceTrans('', $ipm, $dataTransport,'Hourly');
                        if (isset($ipm->SalesAddMethod)) $dataTransport->SalesWeekday = $this->calcPriceTrans('', $ipm, $dataTransport,'Weekday');
                        if (isset($ipm->SalesAddMethod)) $dataTransport->SalesWeekend = $this->calcPriceTrans('', $ipm, $dataTransport,'Weekend');

                        if (isset($ipm->SalesAdd1Method)) $dataTransport->SalesHourly1 = $this->calcPriceTrans('1', $ipm, $dataTransport,'Hourly');
                        if (isset($ipm->SalesAdd1Method)) $dataTransport->SalesWeekday1 = $this->calcPriceTrans('1', $ipm, $dataTransport,'Weekday');
                        if (isset($ipm->SalesAdd1Method)) $dataTransport->SalesWeekend1 = $this->calcPriceTrans('1', $ipm, $dataTransport,'Weekend');

                        if (isset($ipm->SalesAdd2Method)) $dataTransport->SalesHourly2 = $this->calcPriceTrans('2', $ipm, $dataTransport,'Hourly');
                        if (isset($ipm->SalesAdd2Method)) $dataTransport->SalesWeekday2 = $this->calcPriceTrans('2', $ipm, $dataTransport,'Weekday');
                        if (isset($ipm->SalesAdd2Method)) $dataTransport->SalesWeekend2 = $this->calcPriceTrans('2', $ipm, $dataTransport,'Weekend');

                        if (isset($ipm->SalesAdd3Method)) $dataTransport->SalesHourly3 = $this->calcPriceTrans('3', $ipm, $dataTransport,'Hourly');
                        if (isset($ipm->SalesAdd3Method)) $dataTransport->SalesWeekday3 = $this->calcPriceTrans('3', $ipm, $dataTransport,'Weekday');
                        if (isset($ipm->SalesAdd3Method)) $dataTransport->SalesWeekend3 = $this->calcPriceTrans('3', $ipm, $dataTransport,'Weekend');

                        if (isset($ipm->SalesAdd4Method)) $dataTransport->SalesHourly4 = $this->calcPriceTrans('4', $ipm, $dataTransport,'Hourly');
                        if (isset($ipm->SalesAdd4Method)) $dataTransport->SalesWeekday4 = $this->calcPriceTrans('4', $ipm, $dataTransport,'Weekday');
                        if (isset($ipm->SalesAdd4Method)) $dataTransport->SalesWeekend4 = $this->calcPriceTrans('4', $ipm, $dataTransport,'Weekend');

                        if (isset($ipm->SalesAdd5Method)) $dataTransport->SalesHourly5 = $this->calcPriceTrans('5', $ipm, $dataTransport,'Hourly');
                        if (isset($ipm->SalesAdd5Method)) $dataTransport->SalesWeekday5 = $this->calcPriceTrans('5', $ipm, $dataTransport,'Weekday');
                        if (isset($ipm->SalesAdd5Method)) $dataTransport->SalesWeekend5 = $this->calcPriceTrans('5', $ipm, $dataTransport,'Weekend');
                    } else {
                        $dataTransport->SalesHourly = $dataTransport->PurchaseHourly;
                        $dataTransport->SalesWeekday = $dataTransport->PurchaseWeekday;
                        $dataTransport->SalesWeekend = $dataTransport->PurchaseWeekend;
                        $dataTransport->save();
                    }
                }else{
                    $dataTransport->SalesHourly = $request->SalesHourly;
                    $dataTransport->SalesWeekday = $request->SalesWeekday;
                    $dataTransport->SalesWeekend = $request->SalesWeekend;

                    $dataTransport->SalesHourly1 = $request->SalesHourly1;
                    $dataTransport->SalesWeekday1 = $request->SalesWeekday1;
                    $dataTransport->SalesWeekend1 = $request->SalesWeekend1;

                    $dataTransport->SalesHourly2 = $request->SalesHourly2;
                    $dataTransport->SalesWeekday2 = $request->SalesWeekday2;
                    $dataTransport->SalesWeekend2 = $request->SalesWeekend2;

                    $dataTransport->SalesHourly3 = $request->SalesHourly3;
                    $dataTransport->SalesWeekday3 = $request->SalesWeekday3;
                    $dataTransport->SalesWeekend3 = $request->SalesWeekend3;

                    $dataTransport->SalesHourly4 = $request->SalesHourly4;
                    $dataTransport->SalesWeekday4 = $request->SalesWeekday4;
                    $dataTransport->SalesWeekend4 = $request->SalesWeekend4;

                    $dataTransport->SalesHourly5 = $request->SalesHourly5;
                    $dataTransport->SalesWeekday5 = $request->SalesWeekday5;
                    $dataTransport->SalesWeekend5 = $request->SalesWeekend5;

                }

                $dataTransport->save();

                $itemParent->IsParent = true;
                $itemParent->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new Item($data))->type('detail');
            logger($data);
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

    public function deleteItemTransport($Oid = null)
    {
        try {            
            DB::transaction(function () use ($Oid) {
                $data = Item::findOrFail($Oid);                
                $data->DetailLinks()->delete();
                $data->TravelItemTransportObj()->delete();
                $gcrecord = now()->format('ymdHi');
                $data->GCRecord = $gcrecord;
                $data->Code = substr($data->Code,0,39).' '.now()->format('ymdHi');
                $data->Name = $data->Name.' '.now()->format('ymdHi');
                $data->save();
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


    public function getItemHotel($Oid = null)
    {
        try {
            $itemType = ItemType::where('Code','Hotel')->first();
            $data = Item::with(['PurchaseBusinessPartnerObj','TravelItemHotelObj'])->where('ParentOid',$Oid)->where('ItemType',$itemType->Oid)->get();
            foreach($data as $row){
                $row->BusinessPartnerName = $row->PurchaseBusinessPartner ? $row->PurchaseBusinessPartnerObj->Name.' - '.$row->PurchaseBusinessPartnerObj->Code : null;
                // $row->ItemName = $row->Item ? $row->ItemObj->Name : null;
                // $row->WarehouseName = $row->Warehouse ? $row->WarehouseObj->Name : null;
                unset($row->BusinessPartnerObj);
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


    public function saveItemHotel(Request $request, $Oid = null)
    {  
        $itemParent = Item::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            if (!$Oid) $data = new Item();
            else $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','PurchaseAmount','DateStart','DateEnd','IsUsingPriceMethod'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->save();
                $data->PurchaseBusinessPartner = $request->BusinessPartner;
                $data->Code = now()->format('ymdHis').'-'.str_random(3);      
                $data->ParentOid = $itemParent->Oid;
                $data->Name = $itemParent->Name.' - '.$data->Subtitle;
                $data->ItemGroup = $itemParent->ItemGroup;
                $data->ItemAccountGroup = $itemParent->ItemAccountGroup;
                $data->ItemUnit = $itemParent->ItemUnit;
                $data->ItemType = ItemType::where('Code','Hotel')->first()->Oid;
                $data->PurchaseCurrency = $itemParent->PurchaseCurrency;
                $data->SalesCurrency = $itemParent->SalesCurrency;
                $data->City = $itemParent->City;
                $data->APIType = $itemParent->APIType;
                $data->IsAllotment = $itemParent->IsAllotment;
                $data->IsStock = $itemParent->IsStock;
                $data->IsParent = false;
                $data->IsDetail = true;
                $data->save();

                $ipm = $itemParent->IsUsingPriceMethod ? $data->ItemTypeObj->ItemTypePriceMethodObj : $itemParent;
                if($data->IsUsingPriceMethod == true){
                    if (isset($ipm)) {
                        if (isset($ipm->SalesAddMethod)) $data->SalesAmount = $this->calcPrice('', $ipm, $data);
                        if (isset($ipm->SalesAdd1Method)) $data->SalesAmount1 = $this->calcPrice('1', $ipm, $data);
                        if (isset($ipm->SalesAdd2Method)) $data->SalesAmount2 = $this->calcPrice('2', $ipm, $data);
                        if (isset($ipm->SalesAdd3Method)) $data->SalesAmount3 = $this->calcPrice('3', $ipm, $data);
                        if (isset($ipm->SalesAdd4Method)) $data->SalesAmount4 = $this->calcPrice('4', $ipm, $data);
                        if (isset($ipm->SalesAdd5Method)) $data->SalesAmount5 = $this->calcPrice('5', $ipm, $data);
                    }
                }else{
                    $data->SalesAmount = $request->SalesAmount;
                    $data->SalesAmount1 = $request->SalesAmount1;
                    $data->SalesAmount2 = $request->SalesAmount2;
                    $data->SalesAmount3 = $request->SalesAmount3;
                    $data->SalesAmount4 = $request->SalesAmount4;
                    $data->SalesAmount5 = $request->SalesAmount5;
                }

                $data->save();

                $dataHotel = TravelItemHotel::where('Oid',$data->Oid)->first();  
                if (!$dataHotel) { 
                    $datadetail = new ItemDetailLink(); 
                    $datadetail->Oid = $data->Oid;
                    $datadetail->Parent = $itemParent->Oid;
                    $datadetail->save();
                    $dataHotel = new TravelItemHotel();
                    $dataHotel->Oid = $data->Oid; 
                }
                $enabled = ['TravelHotelRoomType','MaxChild','MinOrder','MaxOrder','MaxOccupancy','QtyDouble','QtyTwin','CutOffDay',
                    'AllowExtraBed','RoomSize'];                
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $dataHotel->{$field} = $request->{$field};
                }
                $dataHotel->save();

                $itemParent->IsParent = true;
                $itemParent->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new Item($data))->type('detail');
            logger($data);
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

    public function deleteItemHotel($Oid = null)
    {
        try {            
            DB::transaction(function () use ($Oid) {
                $data = Item::findOrFail($Oid);
                $data->DetailLinks()->delete();
                $data->TravelItemHotelObj()->delete();
                $gcrecord = now()->format('ymdHi');
                $data->GCRecord = $gcrecord;
                $data->Code = substr($data->Code,0,39).' '.now()->format('ymdHi');
                $data->Name = $data->Name.' '.now()->format('ymdHi');
                $data->save();
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

    public function saveItemForEticketUpload(Request $request, $Oid = null)
    {  
        if (!$Oid) throw new \Exception('Must include Item Oid');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = POSETicketUpload::where('Item',$Oid)->first();
            if (!$data) $data = new POSETicketUpload();

            DB::transaction(function () use ($request,$Oid, &$data) {
                $item = Item::findOrFail($Oid);
                $itemParent = Item::with('ItemGroupObj')->findOrFail($item->ParentOid);
                $bp = BusinessPartner::findOrFail($itemParent->PurchaseBusinessPartner);
                $cur = Currency::findOrFail($item->PurchaseCurrency);
                $data->Item = $item->Oid;
                $data->ItemParent = $itemParent->Oid;
                $data->BusinessPartner = $itemParent->PurchaseBusinessPartner;
                $data->Description = $itemParent->Description;
                $data->PurchaseDate = $itemParent->DateFrom;
                $data->Currency = $itemParent->PurchaseCurrency;
                $data->CostPrice = $item->PurchaseAmount;
                $data->Amount = $item->PurchaseAmount ?: $item->PurchaseAmount;
                $data->Rate = 1;
                $data->AmountBase = $cur->ToBaseAmount($item->PurchaseAmount, $data->Rate);
                $data->Warehouse = $item->CompanyObj->Warehouse;
                $data->Account = $bp->BusinessPartnerAccountGroupObj->PurchaseInvoice;
                $data->Status = Status::entry()->first()->Oid;
                $data->save();

                $item->POSETicketUpload = $data->Oid;
                $item->save();
                if(!$item) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new Item($data))->type('detail');
            logger($data);
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

    public function changeStatus(Request $request, Item $data)
    {
        try {
            DB::transaction(function () use ($request, &$data) {
                $data->Status = $request->Status;
                $data->save();
            });
            
            return response()->json(
                $data, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listitemoutbound(Request $request)
    {
        try {
            $item = $request->input('item');
            $itemType = ItemType::where('Code','Outbound')->first();
            $data = Item::with(['TravelItemOutboundObj'])->where('ParentOid',$item)->where('ItemType',$itemType->Oid)->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveitemoutbound(Request $request, $Oid = null)
    {
        $itemParent = Item::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {            
            if (!$Oid) $data = new Item();
            else $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request, $itemParent, &$data) {
                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','IsDayMonday','IsDayTuesday',
                'IsDayWednesday','IsDayThursday','IsDayFriday','IsDaySaturday','IsDaySunday','DateStart','DateEnd'];                
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->save();
                $data->PurchaseBusinessPartner = $itemParent->PurchaseBusinessPartner;
                $data->Code = now()->format('ymdHis').'-'.str_random(3);      
                $data->ParentOid = $itemParent->Oid;
                $data->Name = $itemParent->Name.' - '.$data->Subtitle;
                $data->ItemGroup = $itemParent->ItemGroup;
                $data->ItemAccountGroup = $itemParent->ItemAccountGroup;
                $data->ItemUnit = $itemParent->ItemUnit;
                $data->ItemType = ItemType::where('Code','Outbound')->first()->Oid;
                $data->PurchaseCurrency = $itemParent->PurchaseCurrency;
                $data->SalesCurrency = $itemParent->SalesCurrency;
                $data->City = $itemParent->City;
                $data->APIType = $itemParent->APIType;
                $data->IsAllotment = $itemParent->IsAllotment;
                $data->IsStock = $itemParent->IsStock;
                $data->IsParent = false;
                $data->IsDetail = true;
                $data->IsUsingPriceMethod = 1;
                $data->save();

                $dataOutbound = TravelItemOutbound::where('Oid',$data->Oid)->first();  
                if (!$dataOutbound) { 
                    $dataOutbound = new TravelItemOutbound();
                    $dataOutbound->Oid = $data->Oid; 
                }
                $enabled = ['PurchaseSGL','PurchaseTWN','PurchaseTRP','PurchaseQuad','PurchaseQuint','PurchaseCHT','PurchaseCWB','PurchaseCNB'];                
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $dataOutbound->{$field} = $request->{$field};
                }
                $ipm = $itemParent->IsUsingPriceMethod ? $data->ItemTypeObj->ItemTypePriceMethodObj : $itemParent;
                // if($data->IsUsingPriceMethod == true){ //OUTBOUND BELUM MEMPERBOLEHKAN CUSTOMIZE HARGA
                    if (isset($ipm)) {

                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesSGL = $this->calcPriceOB('', $ipm, $dataOutbound,'SGL');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesTWN = $this->calcPriceOB('', $ipm, $dataOutbound,'TWN');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesTRP = $this->calcPriceOB('', $ipm, $dataOutbound,'TRP');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesQuad = $this->calcPriceOB('', $ipm, $dataOutbound,'Quad');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesQuint = $this->calcPriceOB('', $ipm, $dataOutbound,'Quint');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCHT = $this->calcPriceOB('', $ipm, $dataOutbound,'CHT');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCWB = $this->calcPriceOB('', $ipm, $dataOutbound,'CWB');
                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCNB = $this->calcPriceOB('', $ipm, $dataOutbound,'CNB');

                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesSGL1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'SGL');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesTWN1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'TWN');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesTRP1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'TRP');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesQuad1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'Quad');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesQuint1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'Quint');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCHT1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CHT');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCWB1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CWB');
                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCNB1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CNB');

                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesSGL2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'SGL');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesTWN2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'TWN');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesTRP2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'TRP');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesQuad2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'Quad');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesQuint2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'Quint');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCHT2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CHT');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCWB2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CWB');
                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCNB2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CNB');

                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesSGL3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'SGL');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesTWN3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'TWN');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesTRP3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'TRP');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesQuad3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'Quad');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesQuint3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'Quint');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCHT3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CHT');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCWB3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CWB');
                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCNB3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CNB');

                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesSGL4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'SGL');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesTWN4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'TWN');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesTRP4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'TRP');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesQuad4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'Quad');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesQuint4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'Quint');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCHT4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CHT');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCWB4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CWB');
                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCNB4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CNB');

                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesSGL5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'SGL');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesTWN5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'TWN');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesTRP5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'TRP');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesQuad5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'Quad');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesQuint5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'Quint');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCHT5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CHT');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCWB5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CWB');
                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCNB5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CNB');
                    }else{
                        $dataOutbound->SalesSGL = $dataOutbound->PurchaseSGL;
                        $dataOutbound->SalesTWN = $dataOutbound->PurchaseTWN;
                        $dataOutbound->SalesTRP = $dataOutbound->PurchaseTRP;
                        $dataOutbound->SalesQuad = $dataOutbound->PurchaseQuad;
                        $dataOutbound->SalesQuint = $dataOutbound->PurchaseQuint;
                        $dataOutbound->SalesCHT = $dataOutbound->PurchaseCHT;
                        $dataOutbound->SalesCWB = $dataOutbound->PurchaseCWB;
                        $dataOutbound->SalesCNB = $dataOutbound->PurchaseCNB;
                        $dataOutbound->save();
                    }
                // }
                $dataOutbound->save();
                unset($dataOutbound->Oid);
                unset($dataOutbound->IsDayMonday);
                unset($dataOutbound->IsDayTuesday);
                unset($dataOutbound->IsDayWednesday);
                unset($dataOutbound->IsDayThursday);
                unset($dataOutbound->IsDayFriday);
                unset($dataOutbound->IsDaySaturday);
                unset($dataOutbound->IsDaySunday);
                $dataOutbound = collect($dataOutbound);
                $data = collect($data);
                $data = $data->merge($dataOutbound);
                
                if(!$data) throw new \Exception('Data is failed to be saved');
                
                $itemParent->IsParent = true;
                // $itemParent->SalesAmount = $this->getPriceParent($itemParent->Oid);
                $itemParent->save();
                if(!$itemParent) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new ProductionOrderResource($data))->type('detail');
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

    public function deleteitemoutbound($Oid = null)
    {
        try {            
            DB::transaction(function () use ($Oid) {
                // $data = Item::findOrFail($Oid);
                // $data->TravelItemOutboundObj()->delete();
                // $data->delete();

                $data = Item::findOrFail($Oid);
                $data->TravelItemOutboundObj()->delete();
                $gcrecord = now()->format('ymdHi');
                $data->GCRecord = $gcrecord;
                $data->Code = substr($data->Code,0,39).' '.now()->format('ymdHi');
                $data->Name = $data->Name.' '.now()->format('ymdHi');
                $data->save();
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

    public function listitemdate(Request $request)
    {
        try {            
            $item = $request->input('item');
            $data = TravelItemDate::with(['ItemObj'])->where('Item',$item);
            $data = $data->get();
            foreach($data as $row){            
                $row->DateFrom = Carbon::parse($row->DateFrom)->format('Y-m-d');  
                $row->DateUntil = Carbon::parse($row->DateUntil)->format('Y-m-d');    
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveitemdate(Request $request)
    {        
        $item = $request->input('item');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    
        try {            
            $data = Item::with('Dates')->where('Oid',$item)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                // $disabled = ['Oid','Dates','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                // foreach ($request as $field => $key) {
                //     if (in_array($field, $disabled)) continue;
                //     $data->{$field} = $request->{$field};
                // }
                // $data->save();        

                if ($data->Dates()->count() != 0) {
                    foreach ($data->Dates as $rowdb) {
                        $found = false;               
                        foreach ($request->Dates as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = TravelItemDate::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->Dates) {
                    $details = [];  
                    $disabled = ['Oid','Item','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->Dates as $row) {
                        if (isset($row->Oid)) {
                            $detail = TravelItemDate::findOrFail($row->Oid);
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $detail->{$field} = $row->{$field};
                            }
                            $detail->save();
                        } else {
                            $arr = [];
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;                            
                                $arr = array_merge($arr, [
                                    $field => $row->{$field},
                                ]);
                            }
                            $details[] = new TravelItemDate($arr);
                        }
                    }
                    $data->Dates()->saveMany($details);
                    $data->load('Dates');
                    $data->fresh();
                }

                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new ProductionOrderResource($data))->type('detail');
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

    public function copyschedule(Request $request, $Oid = null)
    {        
        $itemFrom = Item::findOrFail($request->input('itemFrom'));
        $itemTo = Item::findOrFail($Oid);
        try {            
            DB::transaction(function () use ($itemFrom,&$itemTo) {
                if ($itemFrom->Dates()->count() != 0) {
                    $details = [];
                    if ($itemTo->Dates()->count() != 0) $itemTo->Dates()->delete();
                    foreach ($itemFrom->Dates as $rowdetail) {
                        $details[] = new TravelItemDate([
                            'Item' => $itemTo->Oid,
                            'DateFrom' => $rowdetail->DateFrom,
                            'DateUntil' => $rowdetail->DateUntil,
                            'IsDayMonday' => $rowdetail->IsDayMonday,
                            'IsDayTuesday' => $rowdetail->IsDayTuesday,
                            'IsDayWednesday' => $rowdetail->IsDayWednesday,
                            'IsDayThursday' => $rowdetail->IsDayThursday,
                            'IsDayFriday' => $rowdetail->IsDayFriday,
                            'IsDaySaturday' => $rowdetail->IsDaySaturday,
                            'IsDaySunday' => $rowdetail->IsDaySunday
                        ]);
                    }
                    $itemTo->Dates()->saveMany($details);
                }
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

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [ 'file' => 'required|mimes:xls,xlsx' ]);

        if ($validator->fails()) return response()->json($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        if (!$request->hasFile('file')) return response()->json('No file found', Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $file = $request->file('file');
        $this->excelService->import(new ItemExcelImport, $file);
        return response()->json(null, Response::HTTP_CREATED);
    }

    public function importSample(Request $request)
    {
        $url = url('importsamples/import_paymentterm.xlsx');
        return response()->json($url, Response::HTTP_OK);
    }

    public function sendItem(Request $request)
    {
        try {     
            $Oid = $request->input('item');
            $bprole = BusinessPartnerRole::where('Code','TrvOutlet')->first();
            $bp = BusinessPartner::where('BusinessPartnerRole',$bprole->Oid)->whereNotNull('Token')->get();
            $item = Item::where('Oid',$Oid)->get();
            $ig = ItemGroup::where('Oid',$item[0]->ItemGroup)->get();
            foreach($bp as $row){
                $param = ['Data' => $item, 'ItemGroup' => $ig ];
                return $row->Token;
                $this->coreService->postapi("/admin/api/v1/item/send",$row->Token, $param);
                // $result = $this->coreService->getapi("/core/api/version", $row->Token);
                // sleep(5);
                
            }

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

    public function receiveItem(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            foreach ($request->Data as $row) {
                $data = Item::where('Oid',$row->Oid)->first();
                if (!$data) {
                    $data = new Item();
                    $data->Company = company()->Oid;
                    $data->ItemUnit = company()->ItemUnit;
                    $data->City = company()->City;
                    $data->IsActive = 1;
                    $itemType = ItemType::where('Code','Product')->first();
                    $data->ItemType = $itemType->Oid;
                }

                $ig = ItemGroup::where('Oid',$row->ItemGroup)->first();
                if(!$ig){
                    $ig = new ItemGroup();
                    foreach ($request->ItemGroup as $rowdetail) {
                        $ig->Company = company()->Oid;
                        $ig->IsActive = 1;
                        $ig->Oid = $rowdetail->Oid;
                        $ig->Code = $rowdetail->Code;
                        $ig->Name = $rowdetail->Name;
                        $ig->ItemType = $rowdetail->ItemType;
                        $iag = ItemAccountGroup::first();
                        $ig->ItemAccountGroup = $iag ? $iag->Oid : null;
                        $ig->save();
                    }  
                }
                $iag = ItemAccountGroup::where('Oid',$ig->ItemAccountGroup)->first();
                
                $data->Oid = $row->Oid;
                $data->Code = $row->Code;
                $data->Name = $row->Name;
                $data->Barcode = $row->Barcode;
                $data->ItemGroup = $ig->Oid;
                $data->PurchaseCurrency = $iag['PurchaseCurrency'] ?: company()->Currency;
                $data->SalesCurrency = $iag['SalesCurrency'] ?: company()->Currency;
                $data->IsPurchase = $iag['IsPurchase'] ?: 1;
                $data->IsSales = $iag['IsPurchase'] ?: 1;
                $data->IsStock = $row->IsStock;
                $data->SalesAmount = $row->SalesAmount;
                $data->IsParent = false;
                $data->IsDetail = false;
                $data->save();
            }
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function checkStockItem(Request $request)
    {        
        $item = $request->input('item');
        try {         
            $idJournalType = JournalType::where('Code','Stock')->first()->Oid;
            $query = "SELECT i.Oid, i.Code, i.Name, SUM(IFNULL(stk.Quantity,0)) AS Stock
                FROM mstitem i 
                LEFT OUTER JOIN trdtransactionstock stk ON stk.Item = i.Oid
                WHERE i.GCRecord IS NULL AND i.IsStock = 1 AND stk.JournalType = '{$idJournalType}' AND i.Oid = '{$item}'
                GROUP BY i.Oid, i.Code, i.Name;";
            $data = DB::select($query);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    public function listPriceMarkupItem(Request $request)
    {
        try {
            $data = TravelItemPriceBusinessPartner::with(['BusinessPartnerGroupObj','ItemObj','ItemContentObj','BusinessPartnerObj']);
            $data = $data->where('ItemContent', $request->input('item'))->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function detailListPriceMarkupItem($Oid) {        
        try {
            $data = TravelItemPriceBusinessPartner::findOrFail($Oid);
            $data->BusinessPartnerGroupName = $data->BusinessPartnerGroupObj ? $data->BusinessPartnerGroupObj->Name.' - '.$data->BusinessPartnerGroupObj->Code : null;
            unset($data->BusinessPartnerGroupObj);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }


    public function savePriceMarkupItem(Request $request, $Oid = null)
    {        
        $itemcontent = $request->input('itemcontent');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        
        try {           
            if (!$Oid) $data = new TravelItemPriceBusinessPartner();
            else $data = TravelItemPriceBusinessPartner::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data, $itemcontent) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->ItemContent = $itemcontent;
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

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

    public function destroyPriceMarkupItem($data)
    {
        $data = TravelItemPriceBusinessPartner::findOrFail($data);
        try {            
            DB::transaction(function () use ($data) {
                $data->delete();
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

    public function barcodeList(Request $request) {
        $result = [];
        $data = Item::whereNull('GCRecord')->whereNotNull('Barcode')->orderBy('Name')->get();
        foreach($data as $row) {
            $result[] = [
                'Oid' => $row->Oid,
                'Code' => $row->Code,
                'Name' => $row->Name,
                'Barcode' => $row->Barcode,
                'Type' => ' (I) ',
                'SalesAmount' => $row->SalesAmount,
            ];
        }
        $data = CostCenter::whereNull('GCRecord')->whereNotNull('Barcode')->orderBy('Name')->get();
        // dd($data->count());
        foreach($data as $row) {
            $result[] = [
                'Oid' => $row->Oid,
                'Code' => $row->Code,
                'Name' => $row->Name,
                'Barcode' => $row->Barcode,
                'Type' => ' (CC) ',
                'SalesAmount' => 0,
            ];
        }
        return $result;
    }

}
