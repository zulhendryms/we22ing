<?php

namespace App\AdminApi\Travel\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Entities\TravelItemHotelPrice;
use App\Core\Travel\Entities\TravelItemHotelPriceDetail;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Entities\Country;
use App\Core\Travel\Entities\TravelItemHotelPriceCountry;
use App\Core\Travel\Entities\TravelItemHotelPriceCountryBlacklist;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TravelItemHotelPriceController extends Controller
{
    protected $roleService;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            $data = $this->crudController->config('trvitemhotelprice');
            $data[0]->topButton = [
                [
                    'name' => 'Add New',
                    'icon' => 'PlusIcon',
                    'type' => 'open_form',
                    'newTab' => true,
                    'url' => "travelitemhotelprice/form?ItemName={Url.ItemName}&Item={Url.Item}"
                ],
            ];
            return $data;

        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table('trvitemhotelprice as data');
            if ($request->has('Item')) $data->where('data.Item',$request->input('Item'));
            $data = $this->crudController->list('trvitemhotelprice', $data, $request);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function presearch(Request $request)
    {
        return [
            [
                'fieldToSave' => 'Item',
                'type' => 'autocomplete',
                'column' => '1/2',
                'default' => null,
                'source' => [],
                'store' => 'autocomplete/item',
                'hiddenField'=> 'ItemName',
            ],
            [
                'type' => 'action',
                'column' => '1/3'
            ]
        ];
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table('trvitemhotelprice as data');
            if ($request->has('Item')) $data->where('Item',$request->input('Item'));
            $data = $this->crudController->index('trvitemhotelprice', $data, $request, false);  
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    private function showSub($Oid)
    {
        try {
            $data = $this->crudController->detail('trvitemhotelprice', $Oid);
            // $data->Action = $this->action($data);
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function show(TravelItemHotelPrice $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data = $this->crudController->saving('trvitemhotelprice', $request, $Oid, true);
            return $data;
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function destroy(TravelItemHotelPrice $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            errjson($e);
        }
    }

    public function addCountry(Request $request, $module, $Oid) 
    {
        try {
            $result;
            DB::transaction(function () use ($request, &$result, $Oid, $module) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $source = Country::where('Region', $request->Region)->orWhere('SubRegion',$request->Region)->limit(5)->get();
                $parent = TravelItemHotelPrice::findOrFail($Oid);
                foreach ($source as $row){
                    if ($request->Action == 'Add') {
                        if ($module == 'whitelist') {
                            $data = TravelItemHotelPriceCountry::where("Country",$row->Oid)->where("TravelItemHotelPrice",$parent->Oid)->first();
                            if ($data) continue;
                            $data = new TravelItemHotelPriceCountry();
                        } elseif ($module == 'blacklist') {
                            $data = TravelItemHotelPriceCountryBlacklist::where("Country",$row->Oid)->where("TravelItemHotelPrice",$parent->Oid)->first();
                            if ($data) continue;
                            $data = new TravelItemHotelPriceCountryBlacklist();
                        }
                        $data->Company = $parent->Company;
                        $data->TravelItemHotelPrice = $parent->Oid;
                        $data->Country = $row->Oid;
                        $data->save();
                        $result[] = $data;
                    } else {
                        if ($module == 'whitelist') $data = TravelItemHotelPriceCountry::where("Country",$row->Oid)->where("TravelItemHotelPrice",$parent->Oid)->first();
                        elseif ($module == 'blacklist') $data = TravelItemHotelPriceCountryBlacklist::where("Country",$row->Oid)->where("TravelItemHotelPrice",$parent->Oid)->first();
                        if ($data) {
                            $result[] = $data;
                            $data->delete();
                        }
                    }
                }
            });
            
            return response()->json(
                $result, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
