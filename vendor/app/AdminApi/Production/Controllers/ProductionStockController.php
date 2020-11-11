<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\TransactionStock;
use App\Core\Trading\Resources\TransactionStockResource;
use App\Core\Trading\Resources\TransactionStockCollection;
use App\Core\Internal\Entities\Status;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;

class ProductionStockController extends Controller
{
    public function list()
    {        
        try {         
            $idJournalType = JournalType::where('Code','Stock')->first()->Oid;
            $query = "SELECT i.Oid, i.Code, i.Name,i.SalesAmount,i.PurchaseAmount, SUM(IFNULL(stk.Quantity,0)) AS Stock
                FROM mstitem i 
                LEFT OUTER JOIN trdtransactionstock stk ON stk.Item = i.Oid AND stk.JournalType = '{$idJournalType}'
                WHERE i.GCRecord IS NULL AND i.IsStock = 1
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

    public function listDetail(Request $request)
    {        
        try {         
            $idJournalType = JournalType::where('Code','Stock')->first()->Oid;
            $query = "SELECT stk.Oid, stk.Code, DATE_FORMAT(stk.Date, '%Y-%m-%d') AS Date, stk.Note, stk.Quantity
                FROM mstitem i 
                LEFT OUTER JOIN trdtransactionstock stk ON stk.Item = i.Oid AND i.Oid = '{$request->input('item')}' AND stk.JournalType = '{$idJournalType}'
                WHERE i.GCRecord IS NULL AND i.IsStock = 1;";
            $data = DB::select($query);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    public function save(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);

        $messsages = array(
            'Date.required'=>__('_.Date').__('error.required'),
            'Date.date'=>__('_.Date').__('error.date'),
            'Item.required'=>__('_.Item').__('error.required'),
            'Item.exists'=>__('_.Item').__('error.exists'),
        );
        $rules = array(
            'Date' => 'required|date',
            'Details.*.Item' => 'required|exists:mstitem,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $detail = [];
            $code = now()->format('ymdHis').'-'.str_random(3);
            DB::transaction(function () use ($request, $Oid, $code, &$detail) {
                foreach ($request->Details as $row) {
                    if (!$Oid) $data = new TransactionStock();
                    else $data = TransactionStock::findOrFail($Oid);
                    // $data->Company = Auth::user()->Company;
                    $data->Code = $code;
                    $data->Date = Carbon::parse($request->Date)->format('Y-m-d');
                    $data->Item = $row->Item;
                    $data->Note = $request->Note;
                    $data->Quantity = $row->StockQuantity;
                    $data->StockQuantity = $row->StockQuantity;
                    $data->JournalType = JournalType::where('Code','Stock')->first()->Oid;
                    $data->Status = Status::posted()->first()->Oid;
                    $data->Warehouse = $request->Warehouse;
                    $data->save();
                    $data->fresh();
                    $detail[] = $data;

                    if(!$data) throw new \Exception('Data is failed to be saved');
                }
            });

            $data = [
                'Details' => $detail
            ];

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

    public function destroy(TransactionStock $data)
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
