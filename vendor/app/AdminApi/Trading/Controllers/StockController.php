<?php

namespace App\AdminApi\Trading\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\TransactionStock; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockController extends Controller
{
    
    public function index(Request $request, $Oid = null)
    {
        try {            
            $user = Auth::user();
            $data = TransactionStock::with(['ItemObj','ProjectObj','WarehouseObj']);
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('purchaseinvoice')) $data->where('PurchaseInvoice', $request->input('purchaseinvoice'));
            if ($request->has('salesinvoice')) $data->where('SalesInvoice', $request->input('salesinvoice'));
            if ($request->has('pointofsale')) $data->where('PointOfSale', $request->input('pointofsale'));
            if ($request->has('stocktransfer')) $data->where('StockTransfer', $request->input('stocktransfer'));
            if ($request->has('stockadjustment')) $data->where('Oid','12de9cb7-3d10-4501-86cc-4d3871fd2717');
            $data = $data->get();
            foreach($data as $row){            
                $row->Date = Carbon::parse($row->Date)->format('Y-m-d');   
                $row->Total = $row->Quantity * $row->Price;    
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

}
            