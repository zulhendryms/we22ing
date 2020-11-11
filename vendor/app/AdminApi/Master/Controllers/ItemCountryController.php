<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Item;
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
use App\Core\Master\Resources\ItemResource;
use App\Core\Master\Resources\ItemCollection;
use App\Core\POS\Entities\FeatureInfoItem;
use App\Core\POS\Entities\FeatureInfo;
use App\Core\Production\Entities\ProductionItem;
use App\Core\Production\Entities\ProductionItemGlass;
use App\Core\Production\Entities\ProductionItemProcess;
use App\Core\Master\Entities\ItemCountry;
use App\Core\Trading\Entities\SalesInvoice;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Internal\Entities\Status;
use App\Core\Internal\Entities\PriceMethod;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\ETicket;
use App\Core\POS\Services\POSETicketService;
use App\Core\Master\Entities\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Core\Internal\Services\FileCloudService;
use Validator;

use Maatwebsite\Excel\Excel;
use App\AdminApi\Master\Services\ItemExcelImport;

class ItemCountryController extends Controller
{
    public function index(Request $request)
    {
        try {            
            $item = $request->input('item');
            $data = ItemCountry::with(['CountryObj'])->where('Item',$item);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request)
    {        
        $item = $request->input('item');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = Item::where('Oid',$item)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid','ItemCountries','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();        

                if ($data->ItemCountries()->count() != 0) {
                    foreach ($data->ItemCountries as $rowdb) {
                        $found = false;               
                        foreach ($request->ItemCountries as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ItemCountry::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->ItemCountries) {
                    $details = [];  
                    $disabled = ['Oid','Item','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->ItemCountries as $row) {
                        if (isset($row->Oid)) {
                            $detail = ItemCountry::findOrFail($row->Oid);
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
                            $details[] = new ItemCountry($arr);
                        }
                    }
                    $data->ItemCountries()->saveMany($details);
                    $data->load('ItemCountries');
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
}
