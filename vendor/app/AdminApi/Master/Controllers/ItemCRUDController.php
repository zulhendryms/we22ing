<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\ItemAccountGroup;
use App\Core\Master\Entities\ItemContent;
use App\Core\Ferry\Entities\FerryRoute;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Internal\Entities\ItemType;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Master\Entities\ItemGroupUser;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use QrCode;

class ItemCRUDController extends Controller
{
    protected $roleService;
    private $crudController;
    private $module;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'mstitem';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            $itemType = ItemType::where('IsActive',true)->whereIn('Code',['Product','Production','Glass','Transport','TravelFerry'])->get();
            $arr = [];
            foreach ($itemType as $r) $arr[] = $this->popup(true, $r->Code);
            $data = $this->crudController->config($this->module);
            $data[0]->topButton = $arr;
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function field(Request $request) { 
        $fields = $this->crudController->jsonFieldPopup('Item',[
            'Company','Code','Name','ItemType','ItemGroup','ItemAccountGroup','ItemUnit','Barcode',
            'PurchaseCurrency','SalesCurrency','PurchaseAmount','SalesAmount','IsActive','IsStock'
        ]);
        if (!$request->has('ItemType')) return $fields;
        $ity = $request->input('ItemType');
        $itemType = ItemType::where('Code',$ity)->first();
        $i = 0;
        foreach($fields as $f) {
            if ($f['fieldToSave'] == 'ItemType') {
                $fields[$i]['default'] = [$itemType->Oid, $itemType->Code];
            }
            $i = $i + 1;
        }
        return $fields;
    }

    private function popup($isCreate = true, $itemtype = null)
    {
        $data = [
            'name' => 'Quick ' . ($isCreate ? 'Add' : 'Edit').($itemtype ? ' '.$itemtype : null),
            'icon' => 'PlusIcon',
            'type' => 'global_form',
            'showModal' => false,
            'post' => 'item',
            'afterRequest' => "init",
            'config' => 'item/field'.($itemtype ? '?ItemType='.$itemtype : null),
        ];
        if ($isCreate) {
            $data['post'] = 'item';
        } else {
            $data['get'] = 'item/{Oid}';
            $data['post'] = 'item/{Oid}';
        }
        return $data;
    }

    public function presearch(Request $request)
    {
        $itemType = ItemType::where('IsActive',true)->whereIn('Code',['Product','Production','Glass','Transport','TravelFerry'])->get();
        $arr = [];
        foreach ($itemType as $r) $arr[] = [
            "Oid"=>$r->Code,
            "Name"=>$r->Code,
        ];
        return [
            [
                "fieldToSave" => "Type",
                "hideLabel" => true,
                "type" => "combobox",
                'hiddenField'=> 'TypeName',
                "column" => "1/2",
                "source" => $arr,
                "store" => "",
                "default" => "View"
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

            $type = $request->has('Type') ? $request->input('Type') : null;
            if (!$type && !$request->has('itemtype')) return null;            
            if ($type) {
                $itemtype = ItemType::where('Code', $type)->first();
                $itemgroups = ItemGroup::where('ItemType', $itemtype->Oid)->pluck('Oid');
                $data = $data->whereIn('data.ItemGroup', $itemgroups);
            }
            if ($request->has('itemtype')) $data = $data->where('ItemGroup.ItemType', $request->input('itemtype'));
            
            $itemGroupUser = ItemGroupUser::select('ItemGroup')->where('User', $user->Oid)->pluck('ItemGroup');
            if ($itemGroupUser->count() > 0) $data->whereIn('data.ItemGroup', $itemGroupUser);


            $data = $this->crudController->list($this->module, $data, $request);
            foreach ($data->data as $row) $row->Action = $this->action($row->Oid);
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
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = Item::with(['PurchaseBusinessPartnerObj', 'ItemTypeObj', 'ProductionItemObj'])->whereNull('GCRecord');
            if ($request->has('purchasebusinesspartner')) $data->where('PurchaseBusinessPartner', $request->input('purchasebusinesspartner'));
            if ($request->has('itemgroup')) $data->where('ItemGroup', $request->input('itemgroup'));
            if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
            if ($request->has('city')) $data->where('City', $request->input('city'));
            if ($request->has('purchasecurrency')) $data->where('PurchaseCurrency', $request->input('purchasecurrency'));
            if ($request->has('salescurrency')) $data->where('SalesCurrency', $request->input('salescurrency'));
            if ($request->has('stockupload')) $data->where('APIType', 'AutoStock');
            if ($request->has('ecommerce')) {
                $input = $request->input('ecommerce');
                $data->whereHas('ItemECommerces', function ($query) use ($input) {
                    $query->where('ECommerce', $input)->where('IsActive', 1);
                });
            }
            if ($request->has('itemtypecode')) {
                $itemtype = ItemType::where('Code', $request->input('itemtypecode'))->first();
                $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
                    $query->where('ItemType', $itemtype->Oid);
                });
            }
            if ($request->has('itemtype')) {
                $itemtype = $request->input('itemtype');
                $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
                    $query->where('ItemType', $itemtype);
                });
            }

            if ($request->has('businesspartnergroup')) {
                $businesspartnergroup = $request->input('businesspartnergroup');
                $data->whereHas('PurchaseBusinessPartnerObj', function ($query) use ($businesspartnergroup) {
                    $query->where('BusinessPartnerGroup', $businesspartnergroup);
                });
            }

            if ($request->has('itemcontent')) $data->where('ItemContent', $request->input('itemcontent'));
            if ($type != 'combo') $data->with(['ItemGroupObj', 'PurchaseBusinessPartnerObj', 'SalesCurrencyObj']);
            if (!$request->has('parent') && !$request->has('detail')) $data->where('IsDetail', 0)->get();
            if ($request->has('parent')) $data->where('IsParent', $request->input('parent'));
            if ($request->has('detail')) $data->where('IsDetail', $request->input('detail') == 1 ? true : false);
            if ($request->has('isstock')) $data->where('IsStock', $request->input('isstock')->whereNull('ItemStockReplacement'));
            if ($request->has('auto_stock')) $data->where('APIType', 'auto_stock');
            if ($request->has('issales')) $data->where('IsSales', $request->input('issales'));
            if ($request->has('ispurchase')) $data->where('IsPurchase', $request->input('ispurchase'));
            if ($request->input('pospriceage') == 1)
                $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Travel']);
                });
            if ($request->input('pospriceday') == 1)
                $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Hotel', 'Transport']);
                });
            if ($request->input('poseticketupload') == 1)
                $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Travel', 'Transport', 'Hotel']);
                });
            if ($request->input('transport') == 1)
                $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Transport']);
                });
            if ($request->input('hotel') == 1) {
                $data->whereHas('ItemGroupObj', function ($query) {
                    $itemtype = ItemType::where('Code', 'hotel')->first();
                    $query->where('ItemType', $itemtype->Oid);
                });
            }
            if ($request->input('product') == 1)
                $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Product']);
                });
            if ($request->input('production') == 1) {
                // $data = $data->where('ItemType','f40443b1-c7e8-11e9-bbdd-d2118390b116');
                $data = $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Production']);
                });
            }
            if ($request->input('glass') == 1)
                $data->whereHas('ItemTypeObj', function ($query) {
                    $query->whereIn('Code', ['Glass']);
                });
            if ($user->BusinessPartner) $data = $data->where('PurchaseBusinessPartner', $user->BusinessPartner);
            $data = $data->orderBy('Name')->get();

            foreach ($data as $row) {
                $row->ItemGroupName = $row->ItemGroupObj ? $row->ItemGroupObj->Name : null;
                $row->Action = [
                    [
                        'name' => 'Open',
                        'icon' => 'ViewIcon',
                        'type' => ',edit',
                    ]  ,
                    [
                        'name' => 'Delete',
                        'icon' => 'TrashIcon',
                        'type' => 'delete',
                    ]  
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function action($data)
    {
        $data = Item::with('ItemGroupObj.ItemTypeObj', 'ItemGroupObj')->where('OId', $data)->first();
        $itemType = $data->ItemGroupObj->ItemTypeObj->Code;
        $url = 'item';
        return [            
            $this->popup(false),
            [
                'name' => 'Edit In Detail',
                'icon' => 'PlusIcon',
                'type' => 'open_form',
                'url' => "item/form?item={Oid}",
                // 'url' => "item/form?Type=" . $itemType . "&item={Oid}",
            ],
            [
                'name' => 'Synchronize',
                'icon' => 'UnlockIcon',
                'type' => 'confirm',
                'post' => $url . '/sync?item={Oid}',
            ],
            [
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => $url . '/{Oid}'
            ]
        ];
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data->Oid);
        return $data;
    }

    public function show(Item $data)
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
        $query = "SELECT RIGHT(Barcode,LENGTH(Barcode)-1) AS lastBarcode FROM mstitem WHERE LENGTH(Barcode) = 7 AND LEFT(Barcode,2)='A0' ORDER BY Barcode DESC LIMIT 1";
        $getBarcode = DB::select($query);
        $query = "SELECT RIGHT(Code,LENGTH(Code)-1) AS lastCode FROM mstitem WHERE LENGTH(Code) = 7 AND LEFT(Code,2)='A0' ORDER BY Code DESC LIMIT 1";
        $getCode = DB::select($query);

        if ($getBarcode) $getBarcode = is_numeric($getBarcode[0]->lastBarcode) ? intval($getBarcode[0]->lastBarcode) + 1 : 0;
        if ($getCode) $getCode = is_numeric($getCode[0]->lastCode) ? (intval($getCode[0]->lastCode)) + 1 : 0;
        if ($getBarcode) {
            $numberBarcode = $getBarcode > $getCode ? $getBarcode : $getCode;
            $replaceNumber = str_replace(",", "", $numberBarcode);

            $barcode = $replaceNumber;
            if (!isset($barcode)) $barcode = '';
            if ($barcode == []) $barcode = '';
            $numlength = strlen($barcode ?: '');
            $length = 6 - $numlength;
            $nol = "";
            for ($i = 1; $i <= $length; $i++) {
                $nol .= '0';
            }
            $resultBarcode = 'A' . $nol . $barcode;
        } else {
            $resultBarcode = null;
        }

        try {
            $data = $this->crudController->saving($this->module, $request, $Oid, false);

            //logic
            $company = Auth::user()->CompanyObj;
            if (!$Oid) {
                $request->IsUsingPriceMethod = 1;
                if ($company->IsAutoGenerateBarcode == true) {
                    // $request->Code = $resultBarcode;
                    $data->Barcode = $resultBarcode;
                }
                if (!isset($data->ItemType)) $data->ItemType = ItemType::where('Code', 'Product')->first()->Oid;
            }
            // $itemType = ItemType::findOrFail($data->ItemType)->Code;
            // if ($data->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
            if ($data->Type == 'TravelFerry') {
                $tmp = $company->CompanySetting ? json_decode($company->CompanySetting) : null;
                $tmp = isset($tmp->ItemContentFerry) ? $tmp->ItemContentFerry : null;
                if ($tmp) $itemContent = ItemContent::where('Oid', $tmp)->first();
                else {                    
                    $itemType = ItemType::where('Code', 'TravelFerry')->first();
                    $itemContent = ItemContent::where('ItemType', $itemType->Oid)->where('Company', $company->Oid)->first();
                }
                $data->ItemContent = $itemContent->Oid;
                $data->ItemGroup = $itemContent->ItemGroup;
                $data->ItemAccountGroup = $itemContent->ItemAccountGroup;
                $route = FerryRoute::where('Oid', $data->FerryRoute)->first();
                $businessPartner = BusinessPartner::where('Oid', $data->PurchaseBusinessPartner)->first();
                $data->Name = $businessPartner ? $businessPartner->Code : null;
                $data->Name = $data->Name.' '.($route ? $route->Code : null);
                $data->Name = $data->Name.' '.($data->FerryTime ?: 'NO FERRY TIME');
                $data->IsDetail = true;
                $data->IsParent = false;
            }
            if ($data->Type == 'Transport') {
                $tmp = $company->CompanySetting ? json_decode($company->CompanySetting) : null;
                $tmp = isset($tmp->ItemContentTransport) ? $tmp->ItemContentTransport : null;
                if ($tmp) $itemContent = ItemContent::where('Oid', $tmp)->first();
                else {                    
                    $itemType = ItemType::where('Code', 'Transport')->first();
                    $itemContent = ItemContent::where('ItemType', $itemType->Oid)->where('Company', $company->Oid)->first();
                }
                $data->ItemContent = $itemContent->Oid;
                $data->ItemGroup = $itemContent->ItemGroup;
                $data->ItemAccountGroup = $itemContent->ItemAccountGroup;
                $data->IsDetail = true;
                $data->IsParent = false;
            }
            $itemGroup = ItemGroup::findOrFail($data->ItemGroup);
            if (company()->IsItemAutoGenerateNameFromItemGroup) $data->Name = $itemGroup ? $itemGroup->Name : null;
            if (!isset($data->Slug)) $data->Slug = $data->Name ?: null;
            if (!isset($data->Name)) $data->NameEN = $data->Name ?: null;
            if (!isset($data->Description)) $data->Description = null;
            if (!isset($data->Description)) $data->DescriptionEN = $data->Description ?: null;
            if (!isset($data->ItemAccountGroup)) $data->ItemAccountGroup = $itemGroup->ItemAccountGroup ?: null;
            $iag = ItemAccountGroup::findOrFail($data->ItemAccountGroup);
            $city = null;
            if (isset($data->PurchaseBusinessPartner)) $city = BusinessPartner::where('Oid', $data->PurchaseBusinessPartner)->first()->City;
            if (!isset($data->ItemUnit)) $data->ItemUnit = $company->ItemUnit ?: null;
            if (!isset($data->City)) $data->City = $city ?: $company->City;
            if (!isset($data->IsActive)) $data->IsActive = 1;
            if (!isset($data->PurchaseCurrency)) $data->PurchaseCurrency = $iag->PurchaseCurrency ?: $company->Currency;
            if (!isset($data->SalesCurrency)) $data->SalesCurrency = $iag->SalesCurrency ?: $company->Currency;
            if (!isset($data->IsPurchase)) $data->IsPurchase = $iag->IsPurchase ?: 1;
            if (!isset($data->IsSales)) $data->IsSales = $iag->IsSales ?: 1;
            if (!isset($data->PurchaseAmount)) $data->PurchaseAmount = 0;
            if (!isset($data->UsualAmount)) $data->UsualAmount = $data->SalesAmount ?: 0;
            if (!isset($data->SalesAmount)) $data->SalesAmount = $data->UsualAmount ?: 0;
            if (!isset($data->IsStock)) $data->IsStock = 1;
            if (!isset($data->Type)) $data->Type = 'Product';
            if ($data->Barcode) {
                $qrCode = QrCode::format('png')
                    ->size(500)->errorCorrection('H')
                    ->generate($data->Barcode);
                $qrCode = base64_encode($qrCode);
                $data->QRCode = 'data:image/png;base64,' . $qrCode;
            }

            $data->save();

            $data->IsParent = false;
            if ($data->Details()->count() == 0) $data->IsParent = false;
            else $data->IsParent = true;
            $data->IsDetail = $data->ItemContent ? true : false;
            $data->save();

            $role = $this->roleService->list('Item'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Role = $this->roleService->generateActionMaster($role);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(Item $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            return response()->js - on(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = serverSideConfigField('Code');
        $fields[] = serverSideConfigField('Name');
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Barcode',];
        $fields[] = ['w' => 0,   'f' => 'ItemGroup.Name', 'n' => 'ItemGroup'];
        $fields[] = ['w' => 0,   'f' => 'SalesCurrency.Code', 'n' => 'SalesCurrency'];
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'SalesAmount',];
        $fields[] = serverSideConfigField('IsActive');
        return $fields;
    }

    public function quickConfig(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields());
        return $fields;
    }
    public function quickList(Request $request)
    {
        $fields = $this->fields();
        $data = DB::table('mstitem as data')
            ->leftJoin('mstcurrency AS SalesCurrency', 'SalesCurrency.Oid', '=', 'data.SalesCurrency')
            ->leftJoin('mstitemgroup AS ItemGroup', 'ItemGroup.Oid', '=', 'data.ItemGroup')
            ->leftJoin('sysitemtype AS ItemType', 'ItemType.Oid', '=', 'data.ItemType');
        if ($request->has('itemtype')) $data = $data->where('ItemGroup.ItemType', $request->input('itemtype'));
        $fields = $this->crudController->jsonList($data, $this->fields(), $request, 'mstitem');
        $role = $this->roleService->list('Item'); //rolepermission
        foreach ($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        $fields = $this->crudController->jsonListReurn($data, $fields);
    }
}
