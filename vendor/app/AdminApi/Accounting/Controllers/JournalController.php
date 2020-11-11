<?php

namespace App\AdminApi\Accounting\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\Journal;
use App\Core\Accounting\Resources\JournalCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JournalController extends Controller
{
    
    public function index(Request $request, $Oid = null)
    {
        try {            
            $user = Auth::user();
            $data = Journal::with(['CurrencyObj','BusinessPartnerObj','AccountObj']);
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('cashbank')) $data->where('CashBank', $request->input('cashbank'));
            if ($request->has('purchaseinvoice')) $data->where('PurchaseInvoice', $request->input('purchaseinvoice'));
            if ($request->has('salesinvoice')) $data->where('SalesInvoice', $request->input('salesinvoice'));
            if ($request->has('pointofsale')) $data->where('PointOfSale', $request->input('pointofsale'));
            if ($request->has('stocktransfer')) $data->where('StockTransfer', $request->input('stocktransfer'));        
            if ($request->has('stockadjustment')) $data->whereNull('Code'); //TODO: harus dibuat dulu
            $data = $data->get();
            $result = [];
            foreach($data as $row) {
                // 'Oid' => $row->Oid,
                // 'Code' => $row->Code,
                // 'Date' => $row->Date,
                $result[] = [
                    'Account' => $row->AccountObj ? $row->AccountObj->Name.' - '.$row->AccountObj->Code : null,
                    'Description' => $row->Description,
                    'Debet' => number_format($row->DebetAmount,2),
                    'Credit' => number_format($row->CreditAmount,2),
                    'DebetBase' => number_format($row->DebetBase,2),
                    'CreditBase' => number_format($row->CreditBase,2),
                    'BusinessPartner' => $row->BusinessPartnerObj ? $row->BusinessPartnerObj->Name : null,
                    'Project' => $row->ProjectObj ? $row->ProjectObj->Name : null,
                    'Action' => [
                        'name' => 'Open Purchase Order',
                        'icon' => 'ListIcon',
                        'type' => 'open_view',
                        'portalget' => "development/table/vueview?code=PurchaseOrder",
                        'get' => 'purchaseorder/15ae544e-d163-406f-a869-ff6139651a1a'
                        
                    ]
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
            