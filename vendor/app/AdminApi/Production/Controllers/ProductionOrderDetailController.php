<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\ProductionOrder;
use App\Core\Production\Entities\ProductionOrderDetail;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class ProductionOrderDetailController extends Controller
{
    public function index(Request $request)
    {        
        try {            
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = ProductionOrderDetail::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('order')) $data->where('ProductionOrder', $request->input('order'));
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function show(ProductionOrderDetail $data)
    {
        try {            
            $data = ProductionOrderDetail::with(['ItemObj'])->findOrFail($data->Oid);
            return $data;
            // return (new ProductionOrderDetailResource($data))->type('detail');
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    // public function create(Request $request)
    // {        
    //     $order = $request->input('order');
    //     $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    //     $dataArray = object_to_array($request);
    //     $messsages = array(
    //         'Item.required'=>__('_.Item').__('error.required'),
    //         'Item.exists'=>__('_.Item').__('error.exists'),
    //     );
    //     $rules = array(
    //         'Item' => 'required|exists:mstitem,Oid',
    //     );

    //     $validator = Validator::make($dataArray, $rules,$messsages);

    //     if ($validator->fails()) {
    //         return response()->json(
    //             $validator->messages(),
    //             Response::HTTP_UNPROCESSABLE_ENTITY
    //         );
    //     }

    //     try {            
    //         DB::transaction(function () use ($request, &$data, $order) {
    //             $order = ProductionOrder::where('Oid',$order)->firstOrFail();
    //             $idOrder = $order->Oid;
    //             $data = new ProductionOrderDetail();
    //             // $data->Company = Auth::user()->Company;
    //             $data->ProductionOrder = $idOrder;
    //             $data->Item = $request->Item;
    //             $data->Quantity = $request->Quantity;
    //             $data->Amount = $request->Amount;
    //             $data->Description = $request->Description;
    //             $data->save();        

    //             if(!$data) throw new \Exception('Data is failed to be saved');
    //         });

    //         $data = (new ProductionOrderDetailResource($data))->type('detail');
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

    // public function edit(Request $request, $Oid = null)
    // {        
    //     $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

    //     try {            
    //         $data = ProductionOrderDetail::findOrFail($Oid);
    //         DB::transaction(function () use ($request, &$data) {   
    //             $data->Item = $request->Item;
    //             $data->Quantity = $request->Quantity;
    //             $data->Amount = $request->Amount;
    //             $data->Description = $request->Description;
    //             $data->save();  

    //             if(!$data) throw new \Exception('Data is failed to be saved');
    //         });

    //         $data = (new ProductionOrderDetailResource($data))->type('detail');
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

    public function save(Request $request)
    {        
        $order = $request->input('order');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    
        try {            
            $data = ProductionOrder::where('Oid',$order)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid','Details','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();        

                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $rowdb) {
                        $found = false;               
                        foreach ($request->Details as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ProductionOrderDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->Details) {
                    $details = [];  
                    $disabled = ['Oid','ProductionOrder','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy','ItemName','ItemObj'];
                    foreach ($request->Details as $row) {
                        if (isset($row->Oid)) {
                            $detail = ProductionOrderDetail::findOrFail($row->Oid);
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
                            $details[] = new ProductionOrderDetail($arr);
                        }
                    }
                    $data->Details()->saveMany($details);
                    $data->load('Details');
                    $data->fresh();
                }

                if(!$data) throw new \Exception('Data is failed to be saved');
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

    public function destroy(ProductionOrderDetail $data)
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
}
