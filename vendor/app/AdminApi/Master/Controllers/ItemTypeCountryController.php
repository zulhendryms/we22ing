<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Item;
use App\Core\Internal\Entities\ItemType;
use App\Core\Master\Resources\ItemTypeCountryResource;
use App\Core\Master\Entities\ItemTypeCountry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;


class ItemTypeCountryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $item = $request->input('item');
            $data = ItemTypeCountry::with(['CountryObj'])->where('ItemType', $item);
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
        // $item = $request->input('item');
        // $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        // $dataArray = object_to_array($request);
        
        // if ($validator->fails()) {
            //     return response()->json(
                //         $validator->messages(),
                //         Response::HTTP_UNPROCESSABLE_ENTITY
        //     );
        // }
        
        // try {
           
        //     $data = ItemTypeCountry::where('ItemType', $item)->firstOrFail();
            
        //     DB::transaction(function () use ($request, &$data) {
        //         $disabled = disabledFieldsForEdit();
        //         foreach ($request as $field => $key) {
        //             if (in_array($field, $disabled)) continue;
        //             $data->{$field} = $request->{$field};
        //         }
        //         $data->save();
        //         if(!$data) throw new \Exception('Data is failed to be saved');
        //     });

        //     $data = (new ItemTypeCountryResource($data))->type('detail');
        //     return response()->json(
        //         $data, Response::HTTP_CREATED
        //     );
        // } catch (\Exception $e) {
        //     return response()->json(
        //         errjson($e),
        //         Response::HTTP_UNPROCESSABLE_ENTITY
        //     );
        // }

        $item = $request->input('item');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {
            $data = ItemType::where('Oid',$item)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid','ItemTypeCountries','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                
                // if ($data->ItemTypeCountries()->count() != 0) {
                //     foreach ($data->ItemTypeCountries as $rowdb) {
                //         $found = false;               
                //         foreach ($request->ItemTypeCountries as $rowapi) {
                //             if (isset($rowapi->Oid)) {
                //                 if ($rowdb->Oid == $rowapi->Oid) $found = true;
                //             }
                //         }
                //         if (!$found) {
                //             $detail = ItemTypeCountry::findOrFail($rowdb->Oid);
                //             $detail->delete();
                //         }
                //     }
                // }

                if($request->ItemTypeCountries) {
                    $details = [];  
                    $disabled = ['Oid','Item','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->ItemTypeCountries as $row) {
                        if (isset($row->Oid)) {
                            $detail = ItemTypeCountry::findOrFail($row->Oid);
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
                            $details[] = new ItemTypeCountry($arr);
                        }
                    }
                    $data->ItemTypeCountries()->saveMany($details);
                    $data->load('ItemTypeCountries');
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

    public function destroy($data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data = ItemTypeCountry::findOrFail($data);
                $data->delete();
                
                return response()->json(
                    null, Response::HTTP_NO_CONTENT
                );
            });
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
