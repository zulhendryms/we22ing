<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\ItemPriceMethod;
use App\Core\POS\Entities\POSETicketUpload;
use App\Core\Master\Entities\ItemAccountGroup;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Internal\Entities\ItemType;
use App\Core\POS\Entities\ItemService;
use App\Core\Travel\Entities\TravelItem;
use App\Core\Travel\Entities\TravelItemHotel;
use App\Core\Travel\Entities\TravelItemOutbound;
use App\Core\Travel\Entities\TravelItemDate;
use App\Core\Travel\Entities\TravelItemTransport;
use App\Core\Master\Entities\ItemDetailLink;
use App\Core\Internal\Entities\Status;
use App\Core\Internal\Entities\PriceMethod;
use App\Core\POS\Services\POSETicketService;
use App\Core\Master\Entities\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Core\Internal\Services\FileCloudService;
use Validator;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;

use Maatwebsite\Excel\Excel;
use App\AdminApi\Master\Services\ItemExcelImport;

class ItemContentController extends Controller
{
    protected $fileCloudService;
    private $excelService;
    protected $posETicketService;
    protected $roleService;
    private $autoNumberService;
    
     public function __construct(
         FileCloudService $fileCloudService,
         Excel $excelService,
         POSETicketService $posETicketService,
         RoleModuleService $roleService,
         AutoNumberService $autoNumberService
         )
     {
        $this->fileCloudService = $fileCloudService;
        $this->excelService = $excelService;
        $this->posETicketService = $posETicketService;
        $this->roleService = $roleService;
        $this->autoNumberService = $autoNumberService;
     }

    //  public function fields() {    
    //      $fields = []; //f = 'FIELD, t = TITLE
    //      $fields[] = serverSideConfigField('Oid');
    //      $fields[] = serverSideConfigField('Code');
    //     //  $fields[] = serverSideConfigField('Name');
    //      $fields[] = ['w'=> 650, 'r'=>1, 'h'=>0, 't'=>'text', 'n'=>'Name'];
    //      //TODO GA TAU KENAPA GA BISA
    //     //  $fields[] = ['w'=> 0, 'r'=>1, 't'=>'combo', 'f'=>'c.Code', 'n'=>'Currency'];
    //      $fields[] = ['w'=> 350, 'r'=>1, 't'=>'combo', 'f'=>'ig.Name', 'n'=>'ItemGroup'];
    //     //  $fields[] = ['w'=> 0, 'r'=>1, 't'=>'combo',' f'=>'ity.Name', 'n'=>'ItemType'];
    //      $fields[] = serverSideConfigField('IsActive');
    //      return $fields;
    //  }
 
    //  public function config(Request $request) {
    //     $fields = serverSideFields($this->fields());
    //     foreach ($fields as &$row) { //combosource
    //         if ($row['headerName'] == 'Currency') $row['source'] = comboSelect('mstcurrency');
    //         elseif ($row['headerName'] == 'ItemGroup') $row['source'] = comboSelect('mstitemgroup');
    //         elseif ($row['headerName'] == 'ItemType') $row['source'] = comboSelect('sysitemtype');
    //         elseif ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
            
    //     }
    //     $fields[0]['cellRenderer'] = 'actionCell';
    //     return $fields;
    //  }
    //  public function list(Request $request) {
    //      $fields = $this->fields();
    //      $roletype = '';
    //      $data = DB::table('mstitemcontent as data')
    //      ->leftJoin('mstcurrency AS c', 'c.Oid', '=', 'data.SalesCurrency')
    //      ->leftJoin('mstitemgroup AS ig', 'ig.Oid', '=', 'data.ItemGroup')
    //      ->leftJoin('sysitemtype AS ity', 'ity.Oid', '=', 'ig.ItemType')
    //     ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
    //     ;
    //     if ($request->has('purchasebusinesspartner')) $data = $data->where('PurchaseBusinessPartner', $request->input('purchasebusinesspartner'));
    //     if ($request->has('itemgroup')) $data = $data->where('ItemGroup', $request->input('itemgroup'));
    //     if ($request->has('itemaccountgroup')) $data = $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
    //     if ($request->has('city')) $data = $data->where('City', $request->input('city'));
    //     if ($request->has('purchasecurrency')) $data = $data->where('PurchaseCurrency', $request->input('purchasecurrency'));
    //     if ($request->has('salescurrency')) $data = $data->where('SalesCurrency', $request->input('salescurrency'));
    //     if ($request->has('itemtype')) {
    //         $data = $data->where('ig.ItemType', $request->input('itemtype'));
    //         $itemType = ItemType::where('Oid',$request->input('itemtype'))->first()->Code;
    //         switch ($itemType) {
    //             case 'Attraction':
    //                 $roletype = 'ItemAttraction';
    //                 break;
    //             case 'Transport':
    //                 $roletype = 'ItemTransport';
    //                 break;
    //             case 'Outbound':
    //                 $roletype = 'ItemAttraction';
    //                 break;
    //             case 'Hotel':
    //                 $roletype = 'ItemOutbound';
    //                 break;
    //         }
    //     }
    //     if ($request->has('itemtypecode')) {
    //         $itemtype = ItemType::where('Code',$request->input('itemtypecode'))->first();
    //         $data = $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
    //             $query->where('ItemType', $itemtype->Oid);
    //         });
    //     }
    //      $data = serverSideQuery($data, $fields, $request, 'mstitemcontent');
    //      if($roletype) $role = $this->roleService->list($roletype); //rolepermission
    //      else $role = $this->roleService->list('ItemAttraction'); //rolepermission
    //      foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role,[
    //          'Duplicate' => true
    //      ]);
    //      return serverSideReturn($data, $fields);
    //  }

    // public function index(Request $request)
    // {        
    //     try {        
    //         $user = Auth::user();    
    //         $type = $request->input('type') ?: 'combo';
    //         $data = ItemContent::with(['PurchaseBusinessPartnerObj','ItemTypeObj','ProductionItemObj'])->whereNull('GCRecord');
    //         if ($request->has('purchasebusinesspartner')) $data->where('PurchaseBusinessPartner', $request->input('purchasebusinesspartner'));
    //         if ($request->has('itemgroup')) $data->where('ItemGroup', $request->input('itemgroup'));
    //         if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
    //         if ($request->has('city')) $data->where('City', $request->input('city'));
    //         if ($request->has('purchasecurrency')) $data->where('PurchaseCurrency', $request->input('purchasecurrency'));
    //         if ($request->has('salescurrency')) $data->where('SalesCurrency', $request->input('salescurrency'));
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
    //         if ($request->has('parent')) $data->where('IsParent', $request->input('parent'));
            
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
    
    // private function showSub(ItemContent $data) {        
    //     $data->ItemTypeCode = $data->ItemTypeObj ? $data->ItemTypeObj->Code : null;
    //     $data->SalesCurrencyName = $data->SalesCurrencyObj ? $data->SalesCurrencyObj->Code : null;
    //     $data->ItemGroupName = $data->ItemGroupObj ? $data->ItemGroupObj->Name.' - '.$data->ItemGroupObj->Code : null;        
    //     $data->ItemTypeCode = $data->ItemTypeObj ? $data->ItemTypeObj->Code : null;
    //     $data->IsTypeService = $data->ItemType ? $data->ItemTypeObj->Code == 'Service' : false;
    //     $data->IsTypeHotel = $data->ItemType ? $data->ItemTypeObj->Code == 'Hotel' : false;
    //     $data->IsTypeTransport = $data->ItemType ? $data->ItemTypeObj->Code == 'Tranport' : false;
    //     $data->IsTypeTravel = $data->ItemType ? $data->ItemTypeObj->Code == 'Travel' : false;
    //     foreach ($data->Details as $row) {
    //         $row->ItemTypeCode = $row->ItemTypeObj ? $row->ItemTypeObj->Code : null; 
    //         $row->Stock = $row->ETickets()->available()->count();
    //     }
    //     return $data;
    // }
    
    // public function show(ItemContent $data)
    // {
    //     try {
    //         $data = ItemContent::with(['Details','PurchaseBusinessPartnerObj'])->findOrFail($data->Oid);
    //         // return $this->showSub($data);
    //         return $data;
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_NOT_FOUND
    //         );
    //     }
    // }

    
    // public function destroy(ItemContent $data)
    // {
    //     try {            
    //         DB::transaction(function () use ($data) {
    //             $gcrecord = now()->format('ymdHi');
    //             $data->GCRecord = $gcrecord;
    //             $data->Code = substr($data->Code,0,19).' '.now()->format('ymdHi');
    //             $data->Name = $data->Name.' '.now()->format('ymdHi');
    //             $data->save();

    //             if ($data->Details()->count() != 0) {
    //                 foreach($data->Details as $row){                    
    //                     $gcrecord = now()->format('ymdHi');
    //                     $row->GCRecord = $gcrecord;
    //                     $row->Code = substr($row->Code,0,19).' '.now()->format('ymdHi');
    //                     $row->Name = $row->Name.' '.now()->format('ymdHi');
    //                     $row->save();
    //                 }
    //             }

    //             // $data->Details()->delete();
    //             // $data->ItemECommerces()->delete();
    //             // $data->delete();
    //         });
    //         return response()->json(
    //             null, Response::HTTP_NO_CONTENT
    //         );
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }
    // }

    // public function save(Request $request, $Oid = null)
    // {
    //     $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    //     $dataArray = object_to_array($request);
    //     $messsages = array(
    //         'Code.required'=>__('_.Code').__('error.required'),
    //         'Code.max'=>__('_.Code').__('error.max'),
    //         'Name.required'=>__('_.Name').__('error.required'),
    //         'Name.max'=>__('_.Name').__('error.max'),
    //         'ItemGroup.required'=>__('_.ItemGroup').__('error.required'),
    //         'ItemGroup.exists'=>__('_.ItemGroup').__('error.exists'),
    //     );
    //     $rules = array(
    //         'Code' => 'required|max:255',
    //         'Name' => 'required|max:255',
    //         'ItemGroup' => 'required|exists:mstitemgroup,Oid',
    //     );

    //     $validator = Validator::make($dataArray, $rules,$messsages);

    //     if ($validator->fails()) {
    //         return response()->json(
    //             $validator->messages(),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }

    //     try {            
    //         $excluded = ['Image1','Image2','Image3','Image4','Image5','Image6','Image7','Image8']; 

    //         DB::transaction(function () use ($request, &$data, $Oid, $excluded) {
    //             $company = Auth::user()->CompanyObj;
    //             if (!$Oid) {
    //                 logger('5 '.$request->ItemType);
    //                 $data = new ItemContent();
    //                 // $itemType = ItemType::findOrFail($request->ItemType)->Code;
    //                 $request->IsUsingPriceMethod = 1;
    //             } else {
    //                 logger(7);
    //                 $data = ItemContent::findOrFail($Oid);
    //                 // $itemType = ItemType::findOrFail($data->ItemType)->Code;
    //             }
    //             $itemGroup = ItemGroup::findOrFail($request->ItemGroup);
    //             if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
    //             if (company()->IsItemAutoGenerateNameFromItemGroup) $request->Name = $itemGroup ? $itemGroup->Name : null;
    //             if (!isset($request->Slug)) $request->Slug = $request->Name ?: null;
    //             if (!isset($request->Name)) $request->NameEN = $request->Name ?: null;
    //             if (!isset($request->APIType)) $request->APIType = 'auto';
    //             if (!isset($request->Description)) $request->Description = null;
    //             if (!isset($request->Description)) $request->DescriptionEN = $request->Description ?: null;
    //             if (!isset($request->ItemAccountGroup)) $request->ItemAccountGroup = $itemGroup->ItemAccountGroup ?: null;
    //             $iag = ItemAccountGroup::findOrFail($request->ItemAccountGroup);
    //             $city = null;
    //             if (isset($request->PurchaseBusinessPartner)) $city = BusinessPartner::where('Oid',$request->PurchaseBusinessPartner)->first()->City;
    //             if (!isset($request->City)) $request->City = $city ?: $company->City;
    //             if (!isset($request->IsActive)) $request->IsActive = 1;
    //             if (!isset($request->PurchaseCurrency)) $request->PurchaseCurrency = $iag->PurchaseCurrency ?: $company->Currency;
    //             if (!isset($request->SalesCurrency)) $request->SalesCurrency = $iag->SalesCurrency ?: $company->Currency;
    //             if (!isset($request->IsPurchase)) $request->IsPurchase = $iag->IsPurchase ?: 1;
    //             if (!isset($request->IsSales)) $request->IsSales = $iag->IsSales ?: 1;
    //             if (!isset($request->PurchaseAmount)) $request->PurchaseAmount = 0;
    //             // if (!isset($request->UsualAmount)) $request->UsualAmount = $request->UsualAmount ?: 0;
    //             // if (!isset($request->SalesAmount)) $request->SalesAmount = $request->SalesAmount ?: 0;
    //             if (!isset($request->IsStock)) $request->IsStock = 1;
    //             $enabled = ['Code','Name','Subtitle','Barcode','Slug','Note','ItemGroup','ItemAccountGroup','City','IsUsingPriceMethod',
    //                 'IsPurchase','PurchaseBusinessPartner','PurchaseCurrency','PurchaseAmount',
    //                 'IsSales','SalesCurrency','UsualAmount','SalesAmount',
    //                 'AgentAmount','AgentAmount1','AgentAmount2','AgentAmount3','AgentAmount4','AgentAmount5',
    //                 'NameEN','NameID','NameZH','NameTH','Description','DescriptionID','DescriptionEN','DescriptionZH','DescriptionID',
    //                 'APIType','APICode','Sequence','IsAllotment','IsStock','QauantitySold','InternalSold','QuantityReview','InternalRating','ETicketMergeType',
    //                 'IsParent','IsDetail','Featured','CountReviews','LastPurchased','ItemType','Barcode','ItemStockReplacement','IsAutoGenerateBarcode','DateStart',
    //                 'DateEnd','Phone','Address','Longitude','Latitude','Expiry','MinQuantity','CutOffDay','DescCaptionEN','DescCaptionZH','DescCaptionID','DescIncludedEN',
    //                 'DescIncludedZH','DescIncludedID','DescTermConditionEN','DescTermConditionZH','DescTermConditionID','DescRedemptionEN','DescRedemptionZH',
    //                 'DescRedemptionID','DescCancelationEN','DescCancelationZH','DescCancelationID','DescLocationEN','DescLocationZH','DescLocationID',
    //                 'InputDate','InputTitle1','InputTitle2','InputTitle3','InputPassenger','Stock','MaxQuantity','YoutubeURL','KeywordEN','KeywordCN','DescOperatingHourEN','DescOperatingHourID','DescOperatingHourZH'];
    //             logger(15);
    //             foreach ($request as $field => $key) {
    //                 if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
    //             }
    //             logger(16);
    //             if (isset($request->Image1->base64)) $data->Image1 = $this->fileCloudService->uploadImage($request->Image1, $data->Image1);
    //             if (isset($request->Image2->base64)) $data->Image2 = $this->fileCloudService->uploadImage($request->Image2, $data->Image2);
    //             if (isset($request->Image3->base64)) $data->Image3 = $this->fileCloudService->uploadImage($request->Image3, $data->Image3);
    //             if (isset($request->Image4->base64)) $data->Image4 = $this->fileCloudService->uploadImage($request->Image4, $data->Image4);
    //             if (isset($request->Image5->base64)) $data->Image5 = $this->fileCloudService->uploadImage($request->Image5, $data->Image5);
    //             if (isset($request->Image6->base64)) $data->Image6 = $this->fileCloudService->uploadImage($request->Image6, $data->Image6);
    //             if (isset($request->Image7->base64)) $data->Image7 = $this->fileCloudService->uploadImage($request->Image7, $data->Image7);
    //             if (isset($request->Image8->base64)) $data->Image8 = $this->fileCloudService->uploadImage($request->Image8, $data->Image8);
    //             $data->IsParent = true;
    //             $data->IsDetail = false;
    //             $data->save();
    //             if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'mstitemcontent');

    //             $query = "INSERT INTO mstitemecommerce (Oid, Company, Item, ECommerce, IsActive)
    //                 SELECT UUID(), i.Company,'".$data->Oid."', i.Oid, 0
    //                 FROM mstecommerce i 
    //                 LEFT OUTER JOIN mstitemecommerce ie ON i.Oid = ie.ECommerce AND ie.Item = '".$data->Oid."'
    //                 WHERE ie.Oid IS NULL";

    //             DB::insert($query);

    //             logger(17);

    //             if ($data->Details()->count() != 0) {
    //                 foreach($data->Details as $row){                    
    //                     $row->PurchaseBusinessPartner = $data->BusinessPartner;
    //                     // $row->ParentOid = $data->Oid;
    //                     $row->ItemContent = $data->Oid;
    //                     $row->ItemGroup = $data->ItemGroup;
    //                     $row->ItemAccountGroup = $data->ItemAccountGroup;
    //                     // $row->ItemUnit = $data->ItemUnit;
    //                     $row->PurchaseCurrency = $data->PurchaseCurrency;
    //                     $row->SalesCurrency = $data->SalesCurrency;
    //                     $row->City = $data->City;
    //                     $row->APIType = $data->APIType;
    //                     $row->IsAllotment = $data->IsAllotment;
    //                     $row->IsStock = $data->IsStock;
    //                     $row->save();
    //                 }
    //             }

    //             if(!$data) throw new \Exception('Data is failed to be saved');
    //         });

    //         $data = ItemContent::findOrFail($data->Oid);
    //         // $data = (new ItemResource($data))->type('detail');
    //         $data = $this->showSub($data);
    //         $role = $this->roleService->list('Item'); //rolepermission
    //         $data->CurrencyName = $data->SalesCurrencyObj->Name;
    //         $data->ItemGroupName = $data->ItemGroupObj->Name;
    //         $data->ItemTypeName = $data->ItemGroupObj->ItemTypeObj->Name;
    //         $data->Role = $this->roleService->generateActionMaster($role);

    //         return response()->json(
    //             $data, Response::HTTP_CREATED
    //         );
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             errjson($e),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }
    // }
    
    public function getPriceMethod($Oid = null)
    {
        try {            
            $item = ItemContent::with(['SalesAddMethodObj','SalesAdd1MethodObj','SalesAdd2MethodObj','SalesAdd3MethodObj','SalesAdd4MethodObj','SalesAdd5MethodObj'])
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
            $data = ItemContent::with('Details')->findOrFail($Oid);
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
                                    $dataOutbound->save();
                                }else{
                                    if (isset($data->SalesAddMethod))  $detail->SalesAmount = $this->calcPrice('', $data, $row);

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
                                        $tmp = $this->calcPriceOutbound($ipm, $detail->TravelItemOutboundObj);
                                        
                                        $dataOutbound->save();
                                    }else{
                                        if (isset($ipm->SalesAddMethod))  $detail->SalesAmount = $this->calcPrice('', $ipm, $row);

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
            $data = Item::with(['PurchaseBusinessPartnerObj'])->where('ItemContent',$Oid)->where('ItemType',$itemType->Oid)->orderBy('Subtitle')->get();
            foreach($data as $row){
                $row->BusinessPartnerName = $row->PurchaseBusinessPartnerObj ? $row->PurchaseBusinessPartnerObj->Name.' - '.$row->PurchaseBusinessPartnerObj->Code : null;
                $row->Stock = $row->ETickets()->available()->count();
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
        
    public function createItemAttraction(Request $request)
    {  
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = new Item();
            DB::transaction(function () use ($request,$itemParent,&$data) {
                $enabled = ['Code','Subtitle','IsUsingPriceMethod','PurchaseAdult','PurchaseChild','PurchaseInfant','PurchaseSenior','IsFeaturedItem','IsActive','IsForAllAges','Initial','Code'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                if (!$data->Code) $data->Code = now()->format('ymdHis').'-'.str_random(3);
                $data->ItemType = ItemType::where('Code','Attraction')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

                // by eka 2020-01-14
                // if($data->IsUsingPriceMethod == true){
                //     if (isset($ipm)) $this->priceSetAttractionFromSales($data, $this->calcPriceAttraction($ipm, $data));
                //     else  $this->priceSetAttractionFromPurchase($data, $data);
                // } else $this->priceSetAttractionFromSales($data, $request);

                if($data->IsUsingPriceMethod == false) $this->priceSetAttractionFromSales($data, $request);
                $data->save();

                $itemParent->IsParent = 1;
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

    public function saveItemAttraction(Request $request, $Oid)
    {  
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            // if (!$Oid) $data = new Item();
            $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','IsUsingPriceMethod',
                'PurchaseAdult','PurchaseChild','PurchaseInfant','PurchaseSenior','IsFeaturedItem','DateStart','DateEnd','IsForAllAges','Initial','Code',
                'SalesUsualAdult','SalesUsualChild','SalesUsualInfant','SalesUsualSenior','SalesAdultMinimum','SalesChildMinimum','SalesInfantMinimum','SalesSeniorMinimum'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->ItemType = ItemType::where('Code','Attraction')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

                // by eka 2020-01-14
                //calc sell price
                // if($data->IsUsingPriceMethod == true){
                //     if (isset($ipm)) $this->priceSetFromSales($data, $this->calcPriceAmount($ipm, $data));
                //     else  $this->priceSetFromPurchase($data, $data);
                // } else $this->priceSetFromSales($data, $request);
                // $data->save();

                if($data->IsUsingPriceMethod == false) $this->priceSetAttractionFromSales($data, $request);

                $itemParent->IsParent = 1;
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
                $check = DB::select("SELECT * FROM trvtransactiondetail WHERE Item ='{$Oid}'");
                if ($check) throw new \Exception('There is already transaction');
                DB::delete("DELETE FROM mstitem WHERE Company!='{$data->Company}' AND ItemSource='{$data->Oid}'");
                $data->Dates()->delete();
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

    public function getItemRestaurant($Oid = null)
    {
        try {
            // $data = POSETicketUpload::with(['BusinessPartnerObj','ItemObj','WarehouseObj'])->where('ItemParent',$Oid)->get();
            $itemType = ItemType::where('Code','Restaurant')->first();
            $data = Item::with(['PurchaseBusinessPartnerObj'])->where('ItemContent',$Oid)->where('ItemType',$itemType->Oid)->orderBy('Subtitle')->get();
            foreach($data as $row){
                $row->BusinessPartnerName = $row->PurchaseBusinessPartnerObj ? $row->PurchaseBusinessPartnerObj->Name.' - '.$row->PurchaseBusinessPartnerObj->Code : null;
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

    public function createItemRestaurant(Request $request)
    {  
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = new Item();
            DB::transaction(function () use ($request,$itemParent,&$data) {
                $enabled = ['Code','Subtitle','IsUsingPriceMethod','PurchaseAdult','PurchaseChild','PurchaseInfant','IsFeaturedItem','IsActive',
                'SalesUsualAdult','SalesUsualChild','SalesUsualInfant','SalesUsualSenior'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                if (!$data->Code) $data->Code = now()->format('ymdHis').'-'.str_random(3);
                $data->ItemType = ItemType::where('Code','Restaurant')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

                // by eka 2020-01-14
                // if($data->IsUsingPriceMethod == true){
                //     if (isset($ipm)) $this->priceSetAttractionFromSales($data, $this->calcPriceAttraction($ipm, $data));
                //     else  $this->priceSetAttractionFromPurchase($data, $data);
                // } else $this->priceSetAttractionFromSales($data, $request);

                if($data->IsUsingPriceMethod == false) $this->priceSetAttractionFromSales($data, $request);
                $data->save();

                $itemParent->IsParent = 1;
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

    public function saveItemRestaurant(Request $request, $Oid)
    {  
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            // if (!$Oid) $data = new Item();
            $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','IsUsingPriceMethod','PurchaseAdult','PurchaseChild','PurchaseInfant','IsFeaturedItem','DateStart','DateEnd',
                'SalesUsualAdult','SalesUsualChild','SalesUsualInfant','SalesUsualSenior'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->ItemType = ItemType::where('Code','Restaurant')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

                // by eka 2020-01-14
                //calc sell price
                // if($data->IsUsingPriceMethod == true){
                //     if (isset($ipm)) $this->priceSetFromSales($data, $this->calcPriceAmount($ipm, $data));
                //     else  $this->priceSetFromPurchase($data, $data);
                // } else $this->priceSetFromSales($data, $request);
                // $data->save();

                if($data->IsUsingPriceMethod == false) $this->priceSetAttractionFromSales($data, $request);
                // if($data->IsStock == true){
                //     $data->ItemAdult = $request->ItemAdult;
                //     $data->ItemChild = $request->ItemChild;
                //     $data->ItemInfant = $request->ItemInfant;
                // }

                $itemParent->IsParent = 1;
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

    public function deleteRestaurant($Oid = null)
    {        
        try {            
            DB::transaction(function () use ($Oid) {
                $data = Item::findOrFail($Oid);
                $check = DB::select("SELECT * FROM trvtransactiondetail WHERE Item ='{$Oid}'");
                if ($check) throw new \Exception('There is already transaction');
                DB::delete("DELETE FROM mstitem WHERE Company!='{$data->Company}' AND ItemSource='{$data->Oid}'");
                $data->Dates()->delete();
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
            $data = Item::with(['PurchaseBusinessPartnerObj','TravelItemTransportObj','TravelItemTransportObj.TravelTransportBrandObj'])->where('ItemContent',$Oid)->where('ItemType',$itemType->Oid)->orderBy('Subtitle')->get();
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
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            if (!$Oid) $data = new Item();
            else $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','PurchaseAmount','DateStart','DateEnd','IsUsingPriceMethod','IsFeaturedItem',
                'SalesUsualAmount'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->ItemType = ItemType::where('Code','Transport')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

                $dataTransport = TravelItemTransport::where('Oid',$data->Oid)->first();  
                if (!$dataTransport) { 
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
                $data->TravelItemTransportObj()->delete();
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

    public function getItemHotel($Oid = null)
    {
        try {
            $itemType = ItemType::where('Code','Hotel')->first();
            $data = Item::with(['PurchaseBusinessPartnerObj','TravelItemHotelObj'])->where('ItemContent',$Oid)->where('ItemType',$itemType->Oid)->orderBy('Subtitle')->get();
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

    public function createItemHotel(Request $request)
    {  
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = new Item();
            DB::transaction(function () use ($request,$itemParent,&$data) {
                $enabled = ['Code','Subtitle','IsUsingPriceMethod','PurchaseAmount','IsFeaturedItem','Initial','Code'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                if (!$data->Code) $data->Code = now()->format('ymdHis').'-'.str_random(3);
                $data->ItemType = ItemType::where('Code','Hotel')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();
        
                $itemParent->IsParent = 1;
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

    public function saveItemHotel(Request $request, $Oid = null)
    {  
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request,$itemParent,&$data) {

                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','PurchaseAmount','DateStart','DateEnd','IsUsingPriceMethod','TravelHotelRoomType',
                'MaxChild','MinOrder','MaxOrder','MaxOccupancy','QtyDouble','QtyTwin','CutOffDay','AllowExtraBed','RoomSize','IsFeaturedItem','Initial','Code','SalesUsualAmount'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->ItemType = ItemType::where('Code','Hotel')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

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
                $check = DB::select("SELECT * FROM trvtransactiondetail WHERE Item ='{$Oid}'");
                if ($check) throw new \Exception('There is already transaction');
                DB::delete("DELETE FROM mstitem WHERE Company!='{$data->Company}' AND ItemSource='{$data->Oid}'");
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
    

    public function saveItemForEticketUpload(Request $request, $Oid = null)
    {  
        if (!$Oid) throw new \Exception('Must include Item Oid');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = POSETicketUpload::where('Item',$Oid)->first();
            if (!$data) $data = new POSETicketUpload();

            DB::transaction(function () use ($request,$Oid, &$data) {
                $item = Item::findOrFail($Oid);
                $itemParent = ItemContent::with('ItemGroupObj')->findOrFail($item->ItemContent);
                $bp = BusinessPartner::findOrFail($itemParent->PurchaseBusinessPartner);
                $cur = Currency::findOrFail($item->PurchaseCurrency);
                $data->Item = $item->Oid;
                $data->ItemContent = $itemParent->Oid;
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

    public function listitemoutbound(Request $request)
    {
        try {
            $item = $request->input('item');
            $itemType = ItemType::where('Code','Outbound')->first();
            $data = Item::with(['TravelItemOutboundObj'])->where('ItemContent',$item)->where('ItemType',$itemType->Oid)->get();
            foreach ($data as $row){
                $row->DateStart = Carbon::parse($row->DateStart)->format('Y-m-d');
                $row->DateEnd = Carbon::parse($row->DateEnd)->format('Y-m-d');
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function createItemOutbound(Request $request)
    {
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {            
            $data = new Item();

            DB::transaction(function () use ($request, $itemParent, &$data) {
                $enabled = ['Code','Subtitle','IsUsingPriceMethod','PurchaseSGL','PurchaseTWN','PurchaseTRP','PurchaseQuad','PurchaseQuint','PurchaseCHT','PurchaseCWB','PurchaseCNB','IsFeaturedItem','Initial','Code','DateStart','DateEnd'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                if (!$data->Code) $data->Code = now()->format('ymdHis').'-'.str_random(3);
                $data->ItemType = ItemType::where('Code','Outbound')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                $data->save();

                $ipm = $itemParent->IsUsingPriceMethod ? $data->ItemTypeObj->ItemTypePriceMethodObj : $itemParent;
                if($data->IsUsingPriceMethod == true){
                    if (isset($ipm)) { 
                        $tmp = $this->calcPriceOutbound($ipm, $data);
                        foreach ($tmp as $field => $key) $data->{$field} = $tmp->{$field};
                        $data->save();
                    } else {
                        $this->priceSetOutboundFromPurchase($data, $data);
                        $data->save();
                    }
                } else $this->priceSetOutboundFromSales($data, $request);
                $data->save();
                
                if(!$data) throw new \Exception('Data is failed to be saved');
                
                $itemParent->IsParent = true;
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

    public function saveitemoutbound(Request $request, $Oid)
    {
        $itemParent = ItemContent::with('ItemTypeObj.ItemTypePriceMethodObj')->findOrFail($request->input('item'));
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {            
            if (!$Oid) $data = new Item();
            else $data = Item::findOrFail($Oid);

            DB::transaction(function () use ($request, $itemParent, &$data) {
                $enabled = ['Subtitle','IsActive','Description','DescriptionZH','DescriptionID','IsDayMonday','IsDayTuesday',
                'IsDayWednesday','IsDayThursday','IsDayFriday','IsDaySaturday','IsDaySunday','DateStart','DateEnd','PurchaseSGL',
                'PurchaseTWN','PurchaseTRP','PurchaseQuad','PurchaseQuint','PurchaseCHT','PurchaseCWB','PurchaseCNB','IsFeaturedItem','IsUsingPriceMethod','Code','Initial',
                'SalesUsualSGL','SalesUsualTWN','SalesUsualTRP','SalesUsualQuad','SalesUsualQuint','SalesUsualCHT','SalesUsualCWB','SalesUsualCNB'];
                   
                foreach ($request as $field => $key) {
                    if (in_array($field, $enabled)) $data->{$field} = $request->{$field};
                }
                $data->ItemType = ItemType::where('Code','Outbound')->first()->Oid;
                $this->saveItemFromParent($data, $itemParent);
                if (isset($request->Image1->base64)) $data->Image1 = $this->fileCloudService->uploadImage($request->Image1, $data->Image1);
                $data->save();

                $ipm = $itemParent->IsUsingPriceMethod ? $data->ItemTypeObj->ItemTypePriceMethodObj : $itemParent;
                if($data->IsUsingPriceMethod == true){
                    if (isset($ipm)) { 
                        $tmp = $this->calcPriceOutbound($ipm, $data);
                        foreach ($tmp as $field => $key) $data->{$field} = $tmp->{$field};
                        $data->save();
                    } else {
                        $this->priceSetOutboundFromPurchase($data, $data);
                        $data->save();
                    }
                } else $this->priceSetOutboundFromSales($data, $request);
                $data->save();
                
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

    public function deleteItemOutbound($Oid = null)
    {
        try {            
            DB::transaction(function () use ($Oid) {
                $data = Item::findOrFail($Oid);
                $check = DB::select("SELECT * FROM trvtransactiondetail WHERE Item ='{$Oid}'");
                if ($check) throw new \Exception('There is already transaction');
                DB::delete("DELETE FROM mstitem WHERE Company!='{$data->Company}' AND ItemSource='{$data->Oid}'");
                $data->TravelItemOutboundObj()->delete();
                $data->Dates()->delete();
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
    
    private function priceSetAttractionFromSales($source, $target) {
        $source->SalesAdult = $target->SalesAdult;
        $source->SalesChild = $target->SalesChild;
        $source->SalesInfant = $target->SalesInfant;
        $source->SalesSenior = $target->SalesSenior;
        $source->save();
    }

    private function priceSetFromSales($source, $target) {
        $source->SalesAmount = $target->SalesAmount;
        $source->save();
    }

    private function priceSetFromPurchase($source, $target) {
        $source->SalesAmount = $target->PurchaseAmount;
        $source->save();
    }

    private function priceSetOutboundFromSales($source, $target) {
        $source->SalesSGL = $target->SalesSGL;
        $source->SalesTWN = $target->SalesTWN;
        $source->SalesTRP = $target->SalesTRP;
        $source->SalesQuad = $target->SalesQuad;
        $source->SalesQuint = $target->SalesQuint;
        $source->SalesCHT = $target->SalesCHT;
        $source->SalesCWB = $target->SalesCWB;
        $source->SalesCNB = $target->SalesCNB;

        $source->save();
    }

    private function priceSetOutboundFromPurchase($source, $target) {   
        $this->priceSetOutboundFromPurchaseSub($source, $target, '');
        $this->priceSetOutboundFromPurchaseSub($source, $target, '1');
        $this->priceSetOutboundFromPurchaseSub($source, $target, '2');
        $this->priceSetOutboundFromPurchaseSub($source, $target, '3');
        $this->priceSetOutboundFromPurchaseSub($source, $target, '4');
        $this->priceSetOutboundFromPurchaseSub($source, $target, '5');
    }

    private function priceSetOutboundFromPurchaseSub($source, $target, $no) {
        // $source->{'SalesSGL'.$no} = $target->PurchaseSGL;
        // $source->{'SalesTWN'.$no} = $target->PurchaseTWN;
        // $source->{'SalesTRP'.$no} = $target->PurchaseTRP;
        // $source->{'SalesQuad'.$no} = $target->PurchaseQuad;
        // $source->{'SalesQuint'.$no} = $target->PurchaseQuint;
        // $source->{'SalesCHT'.$no} = $target->PurchaseCHT;
        // $source->{'SalesCWB'.$no} = $target->PurchaseCWB;
        // $source->{'SalesCNB'.$no} = $target->PurchaseCNB;
        $source->save();
    }    

    private function priceSetOutboundFromPurchaseArray($source, $target) {
        $arr = [];
        $arr = array_merge($arr, $this->$this->priceSetOutboundFromPurchaseSub($source, $target, ''));
        $arr = array_merge($arr, $this->$this->priceSetOutboundFromPurchaseSub($source, $target, '1'));
        $arr = array_merge($arr, $this->$this->priceSetOutboundFromPurchaseSub($source, $target, '2'));
        $arr = array_merge($arr, $this->$this->priceSetOutboundFromPurchaseSub($source, $target, '3'));
        $arr = array_merge($arr, $this->$this->priceSetOutboundFromPurchaseSub($source, $target, '4'));
        $arr = array_merge($arr, $this->$this->priceSetOutboundFromPurchaseSub($source, $target, '5'));
        return $arr;
    }

    private function priceSetOutboundFromPurchaseArraySub($source, $target, $no) {
        return [
            'SalesSGL'.$no => $target->PurchaseSGL,
            'SalesTWN'.$no => $target->PurchaseTWN,
            'SalesTRP'.$no => $target->PurchaseTRP,
            'SalesQuad'.$no => $target->PurchaseQuad,
            'SalesQuint'.$no => $target->PurchaseQuint,
            'SalesCHT'.$no => $target->PurchaseCHT,
            'SalesCWB'.$no => $target->PurchaseCWB,
            'SalesCNB'.$no => $target->PurchaseCNB,
        ];
    }    

    private function saveItemFromParent($data, $itemParent) {
        if (!$data->Code) $data->Code = now()->format('ymdHis').'-'.str_random(3);
        $data->Company = $itemParent->Company;
        $data->ItemContent = $itemParent->Oid;
        $data->PurchaseBusinessPartner = $itemParent->PurchaseBusinessPartner;
        $data->Name = $itemParent->Name.' - '.$data->Subtitle;
        $data->ItemGroup = $itemParent->ItemGroup;
        $data->ItemAccountGroup = $itemParent->ItemAccountGroup;
        $data->ItemUnit = $itemParent->ItemUnit;
        $data->PurchaseCurrency = $itemParent->PurchaseCurrency;
        $data->SalesCurrency = $itemParent->SalesCurrency;
        $data->City = $itemParent->City;
        $data->APIType = $itemParent->APIType;
        $data->IsAllotment = $itemParent->IsAllotment;
        $data->IsStock = $itemParent->IsStock;
        $data->IsParent = false;
        $data->IsDetail = true;
        $data->save();
    }

    private function getPriceParent($itemParent) {

        $item = Item::where('ItemContent',$itemParent)->orderBy('SalesAmount')->first();  
        if(!$item) return 0;

        return $item->SalesAmount;
    }

    private function calcPriceOutbound($ipm, $item) {
        $result = new \stdClass();
        // $result = mergeObjectField($result, $this->calcPriceOBSub('', $ipm, $item));
        // $result = mergeObjectField($result, $this->calcPriceOBSub('1', $ipm, $item));
        // $result = mergeObjectField($result, $this->calcPriceOBSub('2', $ipm, $item));
        // $result = mergeObjectField($result, $this->calcPriceOBSub('3', $ipm, $item));
        // $result = mergeObjectField($result, $this->calcPriceOBSub('4', $ipm, $item));
        // $result = mergeObjectField($result, $this->calcPriceOBSub('5', $ipm, $item));
        return $result;
    }

    private function calcPriceOBSub($no, $itemPriceMethod, $item) {
        $item = Item::findOrFail($item->Oid);
        $method = $itemPriceMethod->{'SalesAdd'.$no.'Method'};
        $amt1 = $itemPriceMethod->{'SalesAdd'.$no.'Amount1'};
        $amt2 = $itemPriceMethod->{'SalesAdd'.$no.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->ItemContentObj->PurchaseCurrency);
        $result = new \stdClass();
        $result->{'SalesSGL'.$no} = $item->PurchaseSGL;
        $result->{'SalesTWN'.$no} = $item->PurchaseTWN;
        $result->{'SalesTRP'.$no} = $item->PurchaseTRP;
        $result->{'SalesQuad'.$no} = $item->PurchaseQuad;
        $result->{'SalesQuint'.$no} = $item->PurchaseQuint;
        $result->{'SalesCHT'.$no} = $item->PurchaseCHT;
        $result->{'SalesCWB'.$no} = $item->PurchaseCWB;
        $result->{'SalesCNB'.$no} = $item->PurchaseCNB;
        $result = $curPurch->convertRateObject($item->ItemContentObj->SalesCurrency, $result);
        if (!$method) return $result;
        $data = PriceMethod::where('Oid',$method)->first();
        if ($data) {
            switch ($data->Code) {
                case "Percentage":
                    $result->{'SalesSGL'.$no} = $this->calculatePricePercentage($result->{'SalesSGL'.$no}, $amt1, $amt2);
                    $result->{'SalesTWN'.$no} = $this->calculatePricePercentage($result->{'SalesTWN'.$no}, $amt1, $amt2);
                    $result->{'SalesTRP'.$no} = $this->calculatePricePercentage($result->{'SalesTRP'.$no}, $amt1, $amt2);
                    $result->{'SalesQuad'.$no} = $this->calculatePricePercentage($result->{'SalesQuad'.$no}, $amt1, $amt2);
                    $result->{'SalesQuint'.$no} = $this->calculatePricePercentage($result->{'SalesQuint'.$no}, $amt1, $amt2);
                    $result->{'SalesCHT'.$no} = $this->calculatePricePercentage($result->{'SalesCHT'.$no}, $amt1, $amt2);
                    $result->{'SalesCWB'.$no} = $this->calculatePricePercentage($result->{'SalesCWB'.$no}, $amt1, $amt2);
                    $result->{'SalesCNB'.$no} = $this->calculatePricePercentage($result->{'SalesCNB'.$no}, $amt1, $amt2);
                    break;
                case "Amount": 
                    $result->{'SalesSGL'.$no} = $this->calculatePriceAmount($result->{'SalesSGL'.$no}, $amt1, $amt2);
                    $result->{'SalesTWN'.$no} = $this->calculatePriceAmount($result->{'SalesTWN'.$no}, $amt1, $amt2);
                    $result->{'SalesTRP'.$no} = $this->calculatePriceAmount($result->{'SalesTRP'.$no}, $amt1, $amt2);
                    $result->{'SalesQuad'.$no} = $this->calculatePriceAmount($result->{'SalesQuad'.$no}, $amt1, $amt2);
                    $result->{'SalesQuint'.$no} = $this->calculatePriceAmount($result->{'SalesQuint'.$no}, $amt1, $amt2);
                    $result->{'SalesCHT'.$no} = $this->calculatePriceAmount($result->{'SalesCHT'.$no}, $amt1, $amt2);
                    $result->{'SalesCWB'.$no} = $this->calculatePriceAmount($result->{'SalesCWB'.$no}, $amt1, $amt2);
                    $result->{'SalesCNB'.$no} = $this->calculatePriceAmount($result->{'SalesCNB'.$no}, $amt1, $amt2);
                    break;
                case "PercentageAmount": 
                    $result->{'SalesSGL'.$no} = $this->calculatePricePercentageAmount($result->{'SalesSGL'.$no}, $amt1, $amt2);
                    $result->{'SalesTWN'.$no} = $this->calculatePricePercentageAmount($result->{'SalesTWN'.$no}, $amt1, $amt2);
                    $result->{'SalesTRP'.$no} = $this->calculatePricePercentageAmount($result->{'SalesTRP'.$no}, $amt1, $amt2);
                    $result->{'SalesQuad'.$no} = $this->calculatePricePercentageAmount($result->{'SalesQuad'.$no}, $amt1, $amt2);
                    $result->{'SalesQuint'.$no} = $this->calculatePricePercentageAmount($result->{'SalesQuint'.$no}, $amt1, $amt2);
                    $result->{'SalesCHT'.$no} = $this->calculatePricePercentageAmount($result->{'SalesCHT'.$no}, $amt1, $amt2);
                    $result->{'SalesCWB'.$no} = $this->calculatePricePercentageAmount($result->{'SalesCWB'.$no}, $amt1, $amt2);
                    $result->{'SalesCNB'.$no} = $this->calculatePricePercentageAmount($result->{'SalesCNB'.$no}, $amt1, $amt2);
                    break;
                case "AmountPercentage": 
                    $result->{'SalesSGL'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesSGL'.$no}, $amt1, $amt2);
                    $result->{'SalesTWN'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesTWN'.$no}, $amt1, $amt2);
                    $result->{'SalesTRP'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesTRP'.$no}, $amt1, $amt2);
                    $result->{'SalesQuad'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesQuad'.$no}, $amt1, $amt2);
                    $result->{'SalesQuint'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesQuint'.$no}, $amt1, $amt2);
                    $result->{'SalesCHT'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesCHT'.$no}, $amt1, $amt2);
                    $result->{'SalesCWB'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesCWB'.$no}, $amt1, $amt2);
                    $result->{'SalesCNB'.$no} = $this->calculatePriceAmountPercentage($result->{'SalesCNB'.$no}, $amt1, $amt2);
                    break;
            }
        }
        return $result;
    }
    private function calculatePricePercentage($cost, $amt1, $amt2) {
        if ($cost == null or $cost == 0) return 0;
        return $cost + (($cost * $amt1)/100);
    }
    private function calculatePriceAmount($cost, $amt1, $amt2) {
        if ($cost == null or $cost == 0) return 0;
        return $cost + $amt1;
    }
    private function calculatePricePercentageAmount($cost, $amt1, $amt2) {
        if ($cost == null or $cost == 0) return 0;
        return $cost + (($cost * $amt1)/100) + $amt2;
    }
    private function calculatePriceAmountPercentage($cost, $amt1, $amt2) {
        if ($cost == null or $cost == 0) return 0;
        return $cost + $amt1 + ((($cost + $amt1) * $amt2)/100);
    }

    private function calcPriceAmount($ipm, $item) {
        $result = new \stdClass();
        $result->SalesAmount = $this->calcPrice('', $ipm, $item);
        return $result;
    }

    private function calcPrice($priceMethodNo, $itemPriceMethod, $item) {
        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->ItemContentObj->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ItemContentObj->SalesCurrency, $item->PurchaseAmount);
        if ($cost == 0) return 0;
        if (!$salesMethod) return $cost;
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
        $cost = $curPurch->convertRate($item->ItemContentObj->SalesCurrency, $itemtransport->{'Purchase'.$key});
        if ($cost == 0) return 0;
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }

    public function duplicateItemContent(Request $request)
    {
        try {            
            $itemcontent = ItemContent::findOrFail($request->input('itemcontent'));
            $data = new ItemContent();
            $disabled = disabledFieldsForEdit();
            foreach ($itemcontent->getAttributes() as $field => $key) {
                if (in_array($field, $disabled)) continue;
                $data->{$field} = $itemcontent->{$field};
            }
            $data->Code = now()->format('ymdHis').'-'.str_random(3);
            $data->Name = $itemcontent->Name.' Copy 1';
            $data->save();

            
            if ($itemcontent->Details()->count() != 0) {
                foreach($itemcontent->Details as $row){  
                    $detail = new Item();
                    foreach ($row->getAttributes() as $field => $key) {
                        if (in_array($field, $disabled)) continue;
                        $detail->{$field} = $row->{$field};
                    }
                    $detail->ItemContent = $data->Oid;
                    $detail->Code = now()->format('ymdHis').'-'.str_random(3);
                    $detail->Name = $row->Name.' Copy 1';
                    $detail->save(); 

                    $item = Item::findOrFail($row->Oid);
                    if ($item->Dates()->count() != 0) {
                        foreach($item->Dates as $rowdate){ 
                            $date = new TravelItemDate();
                            foreach ($rowdate->getAttributes() as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $date->{$field} = $rowdate->{$field};
                            }
                            $date->Item = $detail->Oid;
                            $date->save();
                        }
                    }
                }
            }  

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

    public function reorderItemContent(Request $request) {
        $query = "SELECT i.Oid,i.ItemContent, i.Name, IFNULL(i.Sequence,-1) AS Sequence 
            FROM mstitem i 
            WHERE i.ItemContent = '{$request->input('ItemContent')}'
            ORDER BY i.Sequence";
        return DB::select($query);
    }

    public function reorderItemContentUpdate(Request $request) {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        foreach ($request as $row) {
            $query = "UPDATE mstitem SET Sequence={$row->Sequence} WHERE Oid = '{$row->Oid}'";
            DB::update($query);
        }
        return $request;
    }    
}
