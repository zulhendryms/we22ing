<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\ItemPriceMethod;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemGroupUser;
use App\Core\Internal\Entities\ItemType;
use App\Core\Internal\Entities\PriceMethod;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ItemGroupController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
        )
        {
        $this->module = 'mstitemgroup';
            $this->roleService = $roleService; 
            $this->crudController = new CRUDDevelopmentController();
        }

    public function config(Request $request) {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function presearch(Request $request) {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function list(Request $request) {
        try {
            $data = DB::table($this->module.' as data');
            $itemGroupUser = ItemGroupUser::select('ItemGroup')->where('User', $user->Oid)->pluck('ItemGroup');
            if ($itemGroupUser->count() > 0) $data->whereIn('data.Oid', $itemGroupUser);
            $data = $this->crudController->list($this->module, $data, $request,true);        
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    } 

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';

            $data = ItemGroup::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('itemtype')) $data->where('ItemType', $request->input('itemtype'));
            if ($request->has('itemtypecode')) {
                $itemtype = $request->input('itemtypecode');
                $data->whereHas('ItemTypeObj', function ($query) use ($itemtype) {
                    $query->where('Code', $itemtype);
                });
            }
            if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
            if ($type != 'combo') $data->with(['ItemTypeObj','ItemAccountGroupObj']);
            $data = $data->orderBy('Name')->get();
            if($type == 'list'){
                $itemTypes = ItemType::where('IsActive',1)->whereNull('GCRecord')->orderBy('Name')->get();
                foreach ($itemTypes as $itemType) {
                    $details = [];
                    foreach ($data as $row) {
                        if ($row->ItemType == $itemType->Oid)
                        $details[] = [
                            'Oid'=> $row->Oid,
                            'title' => $row->Name.' '.$row->Code,
                            'expanded' => false,
                        ];
                    }

                    $results[] = [
                        'Oid'=> $itemType->Oid,
                        'title' => $itemType->Name.' '.$itemType->Code,
                        'expanded' => false,
                        'children' => $details
                    ];
                }

                return $results;
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSub($Oid) {
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            return $data;
        } catch (\Exception $e) { err_return($e); }
    }

    public function show(ItemGroup $data) {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function save(Request $request, $Oid = null) {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, true);
            });
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function destroy(ItemGroup $data) {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function getPriceMethod($Oid = null)
    {
        try {
            $itemPriceMethod = ItemPriceMethod::with(['SalesAddMethodObj','SalesAdd1MethodObj','SalesAdd2MethodObj','SalesAdd3MethodObj','SalesAdd4MethodObj','SalesAdd5MethodObj'])->where('ItemGroup',$Oid)->first();
            if (!$itemPriceMethod) {
                $itemPriceMethod = new ItemPriceMethod();
                $itemPriceMethod->save();
                $itemPriceMethod->ItemGroup = $Oid;
                $itemPriceMethod->save();
                $idItemPriceMethod = $itemPriceMethod->Oid;
            }else{
                $idItemPriceMethod = $itemPriceMethod->Oid;
            }
            $result = $itemPriceMethod;
            $result->Oid = $Oid;
            $result->ItemPriceMethod = $idItemPriceMethod;
            unset($result->ItemGroup);
            return response()->json(
                $result,
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
            $data = ItemGroup::with('ItemPriceMethodObj')->findOrFail($Oid);
            $ipm = $data->ItemPriceMethodObj;
            DB::transaction(function () use ($request, $ipm, &$data) {
                    $itemPriceMethod = ItemPriceMethod::where('ItemGroup',$data->Oid)->first();
                    if (!$itemPriceMethod) $itemPriceMethod = new ItemPriceMethod();
                    $itemPriceMethod->ItemGroup = $data->Oid;
                    $itemPriceMethod->Code = 'ItemGroup-'.$data->Code;
                    $itemPriceMethod->Name = 'ItemGroup-'.$data->Name;
                    $itemPriceMethod->IsActive = 1;
                    $itemPriceMethod->SalesAddMethod = $request->SalesAddMethod;
                    $itemPriceMethod->SalesAddAmount1 = $request->SalesAddAmount1;
                    $itemPriceMethod->SalesAddAmount2 = $request->SalesAddAmount2;
                    $itemPriceMethod->SalesAdd1Method = $request->SalesAdd1Method;
                    $itemPriceMethod->SalesAdd1Amount1 = $request->SalesAdd1Amount1;
                    $itemPriceMethod->SalesAdd1Amount2 = $request->SalesAdd1Amount2;
                    $itemPriceMethod->SalesAdd2Method = $request->SalesAdd2Method;
                    $itemPriceMethod->SalesAdd2Amount1 = $request->SalesAdd2Amount1;
                    $itemPriceMethod->SalesAdd2Amount2 = $request->SalesAdd2Amount2;
                    $itemPriceMethod->SalesAdd3Method = $request->SalesAdd3Method;
                    $itemPriceMethod->SalesAdd3Amount1 = $request->SalesAdd3Amount1;
                    $itemPriceMethod->SalesAdd3Amount2 = $request->SalesAdd3Amount2;
                    $itemPriceMethod->SalesAdd4Method = $request->SalesAdd4Method;
                    $itemPriceMethod->SalesAdd4Amount1 = $request->SalesAdd4Amount1;
                    $itemPriceMethod->SalesAdd4Amount2 = $request->SalesAdd4Amount2;
                    $itemPriceMethod->SalesAdd5Method = $request->SalesAdd5Method;
                    $itemPriceMethod->SalesAdd5Amount1 = $request->SalesAdd5Amount1;
                    $itemPriceMethod->SalesAdd5Amount2 = $request->SalesAdd5Amount2;
                    $itemPriceMethod->save();
                    $data->ItemPriceMethod = $itemPriceMethod->Oid;
                    $data->save();
                    //By EKA 20191024 saat save pricemethod, update ke semua detail yg parent nya pake global, dan detail nya tdk manual isi salesamount
                    $details = Item::where('ItemGroup',$data->Oid)->where('IsDetail',1)->whereHas('ParentObj', function ($query) {
                        $query->whereNull('ItemPriceMethod');
                    })->where('IsUsingPriceMethod',true)->get();
                    if ($details->count() != 0) {
                        foreach ($details as $row) {
                            if (isset($ipm)) {
                                if (isset($ipm->SalesAddMethod)) $row->SalesAmount = $this->calcPrice('', $ipm, $row);
                                if (isset($ipm->SalesAdd1Method)) $row->SalesAmount1 = $this->calcPrice('1', $ipm, $row);
                                if (isset($ipm->SalesAdd2Method)) $row->SalesAmount2 = $this->calcPrice('2', $ipm, $row);
                                if (isset($ipm->SalesAdd3Method)) $row->SalesAmount3 = $this->calcPrice('3', $ipm, $row);
                                if (isset($ipm->SalesAdd4Method)) $row->SalesAmount4 = $this->calcPrice('4', $ipm, $row);
                                if (isset($ipm->SalesAdd5Method)) $row->SalesAmount5 = $this->calcPrice('5', $ipm, $row);
                            }
                            $row->save();
                        }
                    }
                    $itemParent = Item::where('ItemGroup',$data->Oid)->where('IsParent',1)->whereNull('ItemPriceMethod')->get();
                    logger($itemParent);
                    if ($itemParent->count() != 0) {
                        foreach ($itemParent as $row) {
                            $row->SalesAmount = $this->getPriceParent($row->Oid);
                            $row->save();
                        }
                    }
                
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

    private function calcPrice($priceMethodNo, $itemPriceMethod, $item) {

        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ParentObj->SalesCurrency, $item->PurchaseAmount);
        logger($item->SalesCurrency);
        logger('$cost '.$cost.' $salesAmount1 '.$salesAmount1);
        logger('salesmethod '.$salesMethod);
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }

    private function getPriceParent($itemParent) {

        $item = Item::where('ParentOid',$itemParent)->orderBy('SalesAmount')->first();  

        return $item->SalesAmount;
    }
}
