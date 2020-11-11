<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\ProductionOrder;
use App\Core\Production\Entities\ProductionOrderItem;
use App\Core\Production\Entities\ProductionOrderItemDetail;
use App\Core\Production\Entities\ProductionOrderItemOther;
use App\Core\Production\Entities\ProductionOrderItemProcess; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Services\FileCloudService;
use Validator;

class ProductionOrderItemController extends Controller
{
    /** @var fileCloudService $fileCloudService */
    protected $fileCloudService;

    /**
     * @param fileCloudService $fileCloudService
     * @return void
     */
    public function __construct(FileCloudService $fileCloudService)
    {
        $this->fileCloudService = $fileCloudService;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = ProductionOrderItem::whereNull('GCRecord');
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

    public function show(ProductionOrderItem $data)
    {
        try {
            $data = ProductionOrderItem::with(['OrderItemDetails', 'OrderItemOthers', 'OrderItemProcess', 'OrderItemProcess.ProductionPriceProcessObj.Details', 'OrderItemProcess.ProductionProcessObj', 'ProductionUnitConvertionObj', 'ProductionUnitConvertionObj.Details'])->findOrFail($data->Oid);
            // return $data;
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function create(Request $request)
    {
        $order = $request->input('order');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        $dataArray = object_to_array($request);
        $messsages = array(
            'ItemProduct1.required' => __('_.ItemProduct1') . __('error.required'),
            'ItemProduct1.exists' => __('_.ItemProduct1') . __('error.exists'),
            'ItemGlass1.required' => __('_.ItemGlass1') . __('error.required'),
            'ItemGlass1.exists' => __('_.ItemGlass1') . __('error.exists'),
            'ProductionShape.required' => __('_.ProductionShape') . __('error.required'),
            'ProductionShape.exists' => __('_.ProductionShape') . __('error.exists'),
        );
        $rules = array(
            'ItemProduct1' => 'required|exists:mstitem,Oid',
            'ItemGlass1' => 'exists:mstitem,Oid',
            'ProductionShape' => 'required|exists:prdshape,Oid',

        );

        $validator = Validator::make($dataArray, $rules, $messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            DB::transaction(function () use ($request, &$data, $order) {
                $order = ProductionOrder::where('Oid', $order)->firstOrFail();
                $idOrder = $order->Oid;
                $data = new ProductionOrderItem();
                // $data->Company = Auth::user()->Company;
                $data->ProductionOrder = $idOrder;
                $data->ItemProduct1 = $request->ItemProduct1;
                $data->ItemGlass1 = $request->ItemGlass1;
                $data->ItemProduct2 = $request->ItemProduct2;
                $data->ItemGlass2 = $request->ItemGlass2;
                $data->ItemProduct3 = $request->ItemProduct3;
                $data->ItemGlass3 = $request->ItemGlass3;
                $data->ItemProduct4 = $request->ItemProduct4;
                $data->ItemGlass4 = $request->ItemGlass4;
                $data->ItemProduct5 = $request->ItemProduct5;
                $data->ItemGlass5 = $request->ItemGlass5;
                $data->ProductionShape = $request->ProductionShape;
                $data->ProductionUnitConvertion = $request->ProductionUnitConvertion;
                if ($data->ProductionShapeObj->Image != null) $data->Image = $data->ProductionShapeObj->Image;
                $data->save();

                // $query = "INSERT INTO prdorderitemprocess (Oid, Company, ProductionOrderItem, ProductionProcess,Valid)
                // SELECT UUID() AS Oid, poi.Company, poi.Oid AS ProductionOrderItem, ip.ProductionProcess, 0
                // FROM prdorderitem poi
                // LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct1
                // LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                // WHERE i.Oid IS NOT NULL AND poi.Oid = '".$data->Oid."'
                // UNION ALL
                // SELECT UUID(), poi.Company, poi.Oid, ip.ProductionProcess, 0
                // FROM prdorderitem poi
                // LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct2
                // LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                // WHERE i.Oid IS NOT NULL AND poi.Oid = '".$data->Oid."'
                // UNION ALL
                // SELECT UUID(), poi.Company, poi.Oid, ip.ProductionProcess, 0
                // FROM prdorderitem poi
                // LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct3
                // LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                // WHERE i.Oid IS NOT NULL AND poi.Oid = '".$data->Oid."'
                // UNION ALL
                // SELECT UUID(), poi.Company, poi.Oid, ip.ProductionProcess, 0
                // FROM prdorderitem poi
                // LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct4
                // LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                // WHERE i.Oid IS NOT NULL AND poi.Oid = '".$data->Oid."'
                // UNION ALL
                // SELECT UUID(), poi.Company, poi.Oid, ip.ProductionProcess, 0
                // FROM prdorderitem poi
                // LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct5
                // LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                // WHERE i.Oid IS NOT NULL AND poi.Oid = '".$data->Oid."'";
                $query = "INSERT INTO prdorderitemprocess (Oid, Company, ProductionOrderItem, ProductionProcess,Valid)
                    SELECT UUID() AS Oid, p.Company, '" . $data->Oid . "', p.Oid, 0 
                    FROM prdprocess p WHERE p.GCRecord IS NULL";

                $save = DB::insert($query);

                if ($save) {
                    $query = "UPDATE prdorderitemprocess LEFT OUTER JOIN  (
                        SELECT poi.Oid AS ProductionOrderItem, ip.ProductionProcess
                        FROM prdorderitem poi
                        LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct1
                        LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                        WHERE i.Oid IS NOT NULL AND ip.Valid = 1 AND poi.Oid = '" . $data->Oid . "'
                        UNION ALL
                        SELECT poi.Oid AS ProductionOrderItem, ip.ProductionProcess
                        FROM prdorderitem poi
                        LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct2
                        LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                        WHERE i.Oid IS NOT NULL AND ip.Valid = 1 AND poi.Oid = '" . $data->Oid . "'
                        UNION ALL
                        SELECT poi.Oid AS ProductionOrderItem, ip.ProductionProcess
                        FROM prdorderitem poi
                        LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct3
                        LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                        WHERE i.Oid IS NOT NULL AND ip.Valid = 1 AND poi.Oid = '" . $data->Oid . "'
                        UNION ALL
                        SELECT poi.Oid AS ProductionOrderItem, ip.ProductionProcess
                        FROM prdorderitem poi
                        LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct4
                        LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                        WHERE i.Oid IS NOT NULL AND ip.Valid = 1 AND poi.Oid = '" . $data->Oid . "'
                        UNION ALL
                        SELECT poi.Oid AS ProductionOrderItem, ip.ProductionProcess
                        FROM prdorderitem poi
                        LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct5
                        LEFT OUTER JOIN prditemprocess ip ON ip.Item = i.Oid
                        WHERE i.Oid IS NOT NULL AND ip.Valid = 1 AND poi.Oid = '" . $data->Oid . "'
                        ) AS TableResult ON TableResult.ProductionOrderItem = prdorderitemprocess.ProductionOrderItem AND TableResult.ProductionProcess = prdorderitemprocess.ProductionProcess
                    SET prdorderitemprocess.Valid = 1
                      WHERE TableResult.ProductionOrderItem IS NOT NULL AND prdorderitemprocess.ProductionOrderItem = '" . $data->Oid . "'";
                    // , prdorderitemprocess.ProductionPrice = TableResult.ProductionPrice

                    DB::update($query);
                }

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            return $data;
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

    public function edit(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        try {
            $data = ProductionOrderItem::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $data->Note = $request->Note;
                $data->ProductionShape = $request->ProductionShape;
                $data->ProductionUnitConvertion = $request->ProductionUnitConvertion;

                if (isset($request->Image->base64)) $data->Image = $this->fileCloudService->uploadImage($request->Image, $data->Image);
                if (isset($request->Image1->base64)) $data->Image1 = $this->fileCloudService->uploadImage($request->Image1, $data->Image1);
                if (isset($request->Image2->base64)) $data->Image2 = $this->fileCloudService->uploadImage($request->Image2, $data->Image2);
                if (isset($request->Image3->base64)) $data->Image3 = $this->fileCloudService->uploadImage($request->Image3, $data->Image3);
                if (isset($request->Image4->base64)) $data->Image4 = $this->fileCloudService->uploadImage($request->Image4, $data->Image4);
                if (isset($request->Image5->base64)) $data->Image5 = $this->fileCloudService->uploadImage($request->Image5, $data->Image5);
                if (isset($request->Image6->base64)) $data->Image6 = $this->fileCloudService->uploadImage($request->Image6, $data->Image6);
                if (isset($request->Image7->base64)) $data->Image7 = $this->fileCloudService->uploadImage($request->Image7, $data->Image7);
                if (isset($request->Image8->base64)) $data->Image8 = $this->fileCloudService->uploadImage($request->Image8, $data->Image8);
                if (isset($request->Image9->base64)) $data->Image9 = $this->fileCloudService->uploadImage($request->Image9, $data->Image9);
                if (isset($request->Image10->base64)) $data->Image10 = $this->fileCloudService->uploadImage($request->Image10, $data->image10);

                $data->save();

                if (isset($request->OrderItemDetails)) {

                    serverSideDeleteDetail($data->OrderItemDetails, $request->OrderItemDetails);
                    foreach ($request->OrderItemDetails as $row) {
                        if (isset($row->Oid)) $detail = ProductionOrderItemDetail::findOrFail($row->Oid);
                        else $detail = new ProductionOrderItemDetail();
                        $detail->Company = $data->Company;
                        $detail->ProductionOrderItem = $data->Oid;
                        $detail = serverSideSave($detail, $row);
                        $detail->save();
                    }
                    $data->load('OrderItemDetails');
                    $data->fresh();
                }

                if (isset($request->OrderItemOthers)) {
                    serverSideDeleteDetail($data->OrderItemOthers, $request->OrderItemOthers);
                    foreach ($request->OrderItemOthers as $row) {
                        if (isset($row->Oid)) $detail = ProductionOrderItemOther::findOrFail($row->Oid);
                        else $detail = new ProductionOrderItemOther();
                        $detail->Company = $data->Company;
                        $detail->ProductionOrderItem = $data->Oid;
                        $detail = serverSideSave($detail, $row, ['ItemName']);
                        $detail->save();
                    }
                    $data->load('OrderItemOthers');
                    $data->fresh();
                }

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            return $data;
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

    public function destroy(ProductionOrderItem $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->OrderItemOthers()->delete();
                $data->OrderItemDetails()->delete();
                $data->OrderItemProcess()->delete();
                $data->delete();
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

    public function listorderitemprocess(Request $request)
    {
        try {
            $orderitem = $request->input('orderitem');
            $data = ProductionOrderItemProcess::with(['ProductionProcessObj'])->where('ProductionOrderItem', $orderitem);
            // $data = $data->ProductionProcessObj()->orderBy('Sequence', 'asc')->get();
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function saveorderitemprocess(Request $request)
    {
        $orderitem = $request->input('orderitem');
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        try {
            $data = ProductionOrderItem::where('Oid', $orderitem)->firstOrFail();
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid', 'OrderItemProcess', 'GCRecord', 'OptimisticLock', 'CreatedAt', 'UpdatedAt', 'CreatedAtUTC', 'UpdatedAtUTC', 'CreatedBy', 'UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();

                if ($data->OrderItemProcess()->count() != 0) {
                    foreach ($data->OrderItemProcess as $rowdb) {
                        $found = false;
                        foreach ($request->OrderItemProcess as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ProductionOrderItemProcess::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if ($request->OrderItemProcess) {
                    $details = [];
                    $disabled = ['Oid', 'ProductionOrderItem', 'GCRecord', 'OptimisticLock', 'CreatedAt', 'UpdatedAt', 'CreatedAtUTC', 'UpdatedAtUTC', 'CreatedBy', 'UpdatedBy'];
                    foreach ($request->OrderItemProcess as $row) {
                        if (isset($row->Oid)) {
                            $detail = ProductionOrderItemProcess::findOrFail($row->Oid);
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
                            $details[] = new ProductionOrderItemProcess($arr);
                        }
                    }
                    $data->OrderItemProcess()->saveMany($details);
                    $data->load('OrderItemProcess');
                    $data->fresh();
                }

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            // $data = (new ProductionOrderResource($data))->type('detail');
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
}
