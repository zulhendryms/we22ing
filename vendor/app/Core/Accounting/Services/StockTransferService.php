<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\Trading\Entities\StockTransfer;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class StockTransferService extends JournalObjectService
{
    /** @var JournalService $this->journalService */
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function post($id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();

            $data = StockTransfer::with([
                'WarehouseFromObj',
                'WarehouseToObj'
            ])->where('Oid',$id)->first();
            $company = $data->CompanyObj;

            if ($this->isPeriodClosed($data->Date)) {
                $this->throwPeriodIsClosedError($data->Date);
            }
            
            $data->Journals()->delete();
            $data->Stocks()->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "StockTransfer" => "p.Oid",
                "Code" => "p.Code",                 "Date" => "p.Date", 
            ];
            $fromDetailTable =  "trdstocktransferdetail d
                LEFT OUTER JOIN trdstocktransfer p ON p.Oid = d.StockTransfer
                LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.StockAccount
                LEFT OUTER JOIN sysjournaltype jti ON jti.Code = 'STIN'
                LEFT OUTER JOIN sysjournaltype jto ON jto.Code = 'STOUT'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = co.Currency
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($data->Date)->format("d/M");
            //endregion

            // //region INSERT STOCK IN
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    "JournalType" => "jti.Oid",          
                "Currency" => "co.Currency",            "Rate" => "1",
                "Quantity" => "d.Quantity",            "Price" => "0", "PriceBase" => "0",
                "StockQuantity" => "d.Quantity",            "StockAmount" => "0",
                "Warehouse" => "IFNULL(p.WarehouseTo, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause} AND i.IsStock = 1 ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK

                        
            // //region INSERT STOCK IN
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",    "JournalType" => "jto.Oid",          
                "Currency" => "co.Currency",            "Rate" => "1",
                "Quantity" => "d.Quantity * -1",            "Price" => "0", "PriceBase" => "0",
                "StockQuantity" => "d.Quantity * -1",            "StockAmount" => "0",
                "Warehouse" => "IFNULL(p.WarehouseFrom, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause} AND i.IsStock = 1 ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK

            StockTransfer::where('Oid', $id)
            ->update([
                'Status' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $data = StockTransfer::findOrFail($id);
            if ($this->isPeriodClosed($data->Date)) {
                $this->throwPeriodIsClosedError($data->Date);
            }
            $data->Journals()->delete();
            $data->Stocks()->delete();
            StockTransfer::where('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }

    public function cancelled($id)
    {
        DB::transaction(function() use ($id) {
            $data = StockTransfer::findOrFail($id);
            if ($this->isPeriodClosed($data->Date)) {
                $this->throwPeriodIsClosedError($data->Date);
            }
            $data->Journals()->delete();
            $data->Stocks()->delete();
            StockTransfer::where('Oid', $id)
            ->update([
                'Status' => Status::cancelled()->value('Oid'),
            ]);
        });
    }
}