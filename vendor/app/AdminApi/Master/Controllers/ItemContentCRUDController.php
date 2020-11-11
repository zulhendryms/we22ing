<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\ItemAccountGroup;
use App\Core\POS\Entities\FeatureInfoItem;
use App\Core\Internal\Entities\ItemType;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ItemContentCRUDController extends Controller
{
    protected $roleService;
    private $crudController;
    private $module;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
        $this->module = 'mstitemcontent';
    }

    public function config(Request $request)
    {
        try {
            $data = $this->crudController->config($this->module);
            $itemType = ItemType::where('IsActive', true)->whereIn('Code', ['Attraction','Hotel','Outbound','Restaurant'])->get();
            $arr = [];
            foreach ($itemType as $r) {
                $arr[] =
            [
                'name' => 'New Item'.$r->Name,
                'icon' => 'PlusIcon',
                'type' => 'open_form',
                'url' => "itemcontent/form?ItemType=".$r->Oid.'&ItemTypeName='.$r->Code
            ];
            }
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
            
            //FILTER NEW TYPE
            $type = $request->has('Type') ? $request->input('Type') : null;
            if (!$type && !$request->has('itemtype')) {
                return null;
            }
            if ($type) {
                $itemtype = ItemType::where('Code', $type)->first();
                $itemgroups = ItemGroup::where('ItemType', $itemtype->Oid)->pluck('Oid');
                $data = $data->whereIn('data.ItemGroup', $itemgroups);
            }

            //FILTER OLD
            if ($request->has('purchasebusinesspartner')) {
                $data = $data->where('data.PurchaseBusinessPartner', $request->input('purchasebusinesspartner'));
            }
            if ($request->has('itemgroup')) {
                $data = $data->where('data.ItemGroup', $request->input('itemgroup'));
            }
            if ($request->has('itemaccountgroup')) {
                $data = $data->where('data.ItemAccountGroup', $request->input('itemaccountgroup'));
            }
            if ($request->has('city')) {
                $data = $data->where('data.City', $request->input('city'));
            }
            if ($request->has('purchasecurrency')) {
                $data = $data->where('data.PurchaseCurrency', $request->input('purchasecurrency'));
            }
            if ($request->has('salescurrency')) {
                $data = $data->where('data.SalesCurrency', $request->input('salescurrency'));
            }
            if ($request->has('itemtype')) {
                // $data = $data->LeftJoin('mstitemcontent as data');]
                $itemGroups = ItemGroup::where('ItemType', $request->input('itemtype'))->pluck('Oid');
                $data = $data->whereIn('data.ItemGroup', $itemGroups);
                // $data = $data->where('ig.ItemType', $request->input('itemtype'));
                $itemType = ItemType::where('Oid', $request->input('itemtype'))->first()->Code;
                switch ($itemType) {
                    case 'Attraction':
                        $roletype = 'ItemAttraction';
                        break;
                    case 'Transport':
                        $roletype = 'ItemTransport';
                        break;
                    case 'Outbound':
                        $roletype = 'ItemAttraction';
                        break;
                    case 'Hotel':
                        $roletype = 'ItemOutbound';
                        break;
                    case 'Transport':
                        $roletype = 'ItemTransport';
                        break;
                }
            }
            if ($request->has('itemtypecode')) {
                $itemtype = ItemType::where('Code', $request->input('itemtypecode'))->first();
                $data = $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
                    $query->where('ItemType', $itemtype->Oid);
                });
            }

            $data = $this->crudController->list($this->module, $data, $request, true);
            // dd($data);
            foreach ($data->data as $row) {
                $row->Action = $this->action($row->Oid);
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

    private function action($Oid)
    {
        $data = ItemContent::with('ItemGroupObj.ItemTypeObj')->where('OId', $Oid)->first();
        $itemType = $data->ItemGroupObj->ItemTypeObj->Code;
        return [
            [
                'name' => 'Edit',
                'icon' => 'PlusIcon',
                'type' => 'open_form',
                'url' => "itemcontent/form?type=".$itemType."&item={Oid}",
            ],
            [
                'name' => 'Delete',
                'icon' => 'PlusIcon',
                'type' => 'delete',
                'url' => "itemcontent/delete={Oid}",
            ]
        ];
    }

    private function showSub($Oid)
    {
        $data = ItemContent::with('Details')->where('Oid', $Oid)->first();
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data->Oid);
        
        foreach ($data->Details as $row) {
            if ($data->Type == 'Hotel') {
                $row->Action = [
                [
                    "name"=> "Edit Contract Price",
                    "icon"=> "PlusIcon",
                    "type"=> "open_form",
                    "newTab"=> true,
                    "url"=> "travelitemhotelprice?Item=".$row->Oid."&ItemName=".$row->Name,
                    "afterRequest"=> "apply"
                ]
            ];
            }
        }
        return $data;
    }

    public function show(ItemContent $data)
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
            $data=null;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving('mstitemcontent', $request, $Oid, false);
                
                //logic
                $itemGroup = ItemGroup::findOrFail($data->ItemGroup);
                $company = Auth::user()->CompanyObj;
                if (!isset($data->ItemType)) {
                    $data->ItemType = $itemGroup->ItemType ?: null;
                }
                if (!isset($data->Slug)) {
                    $data->Slug = $data->Name ?: null;
                }
                if (!isset($data->Name)) {
                    $data->NameEN = $data->Name ?: null;
                }
                if (!isset($data->APIType)) {
                    $data->APIType = 'auto';
                }
                if (!isset($data->Description)) {
                    $data->Description = null;
                }
                // if (!isset($data->Description)) $data->DescriptionEN = $data->Description ?: null;
                if (!isset($data->ItemAccountGroup)) {
                    $data->ItemAccountGroup = $itemGroup->ItemAccountGroup ?: null;
                }
                $iag = ItemAccountGroup::findOrFail($data->ItemAccountGroup);
                $city = null;
                if (isset($data->PurchaseBusinessPartner)) {
                    $city = BusinessPartner::where('Oid', $data->PurchaseBusinessPartner)->first()->City;
                }
                if (!isset($data->City)) {
                    $data->City = $city ?: $company->City;
                }
                if (!isset($data->IsActive)) {
                    $data->IsActive = 1;
                }
                if (!isset($data->PurchaseCurrency)) {
                    $data->PurchaseCurrency = $iag->PurchaseCurrency ?: $company->Currency;
                }
                if (!isset($data->SalesCurrency)) {
                    $data->SalesCurrency = $iag->SalesCurrency ?: $company->Currency;
                }
                if (!isset($data->IsPurchase)) {
                    $data->IsPurchase = $iag->IsPurchase ?: 1;
                }
                if (!isset($data->IsSales)) {
                    $data->IsSales = $iag->IsSales ?: 1;
                }
                if (!isset($data->PurchaseAmount)) {
                    $data->PurchaseAmount = 0;
                }
                // if (!isset($data->UsualAmount)) $data->UsualAmount = $data->UsualAmount ?: 0;
                // if (!isset($data->SalesAmount)) $data->SalesAmount = $data->SalesAmount ?: 0;
                if (!isset($data->IsStock)) {
                    $data->IsStock = 1;
                }
                $data->IsParent = true;
                $data->IsDetail = false;
                $data->save();

                if (isset($data->Details)) {
                    foreach ($data->Details as $detail) {
                        $this->saveSameField($data, $detail);
                        $detail->save();
                    }
                }

                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $role = $this->roleService->list('ItemContent'); //rolepermission
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

    private function saveSameField($data, $row)
    {
        $row->PurchaseBusinessPartner = $data->BusinessPartner;
        $row->Company = $data->Company;
        $row->ItemContent = $data->Oid;
        $row->ItemGroup = $data->ItemGroup;
        $row->ItemAccountGroup = $data->ItemAccountGroup;
        $row->PurchaseCurrency = $data->PurchaseCurrency;
        $row->SalesCurrency = $data->SalesCurrency;
        $row->City = $data->City;
        $row->APIType = $data->APIType;
        $row->IsAllotment = $data->IsAllotment;
        $row->IsStock = $data->IsStock;
        $row->IsDetail = true;
        $row->IsParent = false;
        $row->save();
    }

    public function saveDetail(Request $request)
    {
        $itemContent = ItemContent::where('Oid', $request->input('ItemContent'))->first();
        if ($request->input('Oid')) {
            $data = Item::where('Oid', $request->input('Oid'))->first();
        }
        if (!$data) {
            $data = new Item();
        }
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        try {
            DB::transaction(function () use ($request, &$data, $itemContent) {
                $data = $this->crudController->save('mstitem', $data, $request, $itemContent); //ga tau napa ga bisa save detail
                $data->ItemType = $itemContent->ItemGroupObj->ItemType;
                if ($data->TravelHotelRoomCategoryObj) {
                    $data->Name = $data->ItemContentObj->Name.' - '.$data->TravelHotelRoomCategoryObj->Name;
                }
                $this->saveSameField($itemContent, $data);
                $data->save();

                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });
            $data = $this->crudController->detail('mstitem', $data->Oid);
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

    public function deleteDetail($oid)
    {
        try {
            return $this->crudController->delete('mstitem', $oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(ItemContent $data)
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
}
