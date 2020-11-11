<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\POS\Entities\POSETicketUpload;
use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class POSETicketUploadPostService extends JournalObjectService
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
            $company = $user->CompanyObj;

            $data = POSETicketUpload::with(['WarehouseObj'])->where('Oid',$id)->first();
            $data->TotalAmount = $data->Count * $data->Amount;
            $data->TotalAmountBase = $data->Count * $data->AmountBase;
            $data->save();

            if ($this->isPeriodClosed($data->DateFrom)) {
                $this->throwPeriodIsClosedError($data->DateFrom);
            }
            
            $data->Journals()->delete();
            $data->Stocks()->delete();

            //region QUERY SETUP
            $fieldParent = ''; $fieldDetail = '';
            $arrDefault = [
                "Oid" => "UUID()",                  "Company" => "p.Company", "CreatedAt" => "NOW()",
                "POSETicketUpload" => "p.Oid",         "JournalType" => "jt.Oid",
                "Code" => "CONCAT(DATE_FORMAT(NOW(), '%y%m%d%H%i%S'),LEFT(UUID(), 8))",       "Date" => "p.DateFrom", 
            ];
            $fromDetailTable =  "poseticket d
                LEFT OUTER JOIN poseticketupload p ON p.Oid = d.ETicketUpload
                LEFT OUTER JOIN mstitem i ON i.Oid = p.Item
                LEFT OUTER JOIN mstitemaccountgroup iag ON iag.Oid = i.ItemAccountGroup
                LEFT OUTER JOIN accaccount a ON a.Oid = iag.StockAccount
                LEFT OUTER JOIN sysjournaltype jt ON jt.Code = 'PET'
                LEFT OUTER JOIN sysstatus s ON s.Code = 'posted'
                LEFT OUTER JOIN company co ON co.Oid = d.Company
                LEFT OUTER JOIN mstcurrency cc ON cc.Oid = p.Currency
                LEFT OUTER JOIN mstitem ir ON ir.Oid = i.ItemStockReplacement";
            $whereClause = "p.Company = '{$company->Oid}'  AND p.Oid = '{$id}' AND p.GCRecord IS NULL";
            $dateInitial = Carbon::parse($data->DateFrom)->format("d/M");
            //endregion

            // //region INSERT STOCK IN
            $arr = array_merge($arrDefault, [
                "Item" => "CASE WHEN ir.Oid IS NOT NULL THEN ir.Oid ELSE i.Oid END",          
                "Currency" => "p.Currency",            "Rate" => "p.Rate",
                "Quantity" => "p.Count",            "Price" => "p.Amount", "PriceBase" => "p.AmountBase",
                "StockQuantity" => "p.Count",            "StockAmount" => "p.Count",
                "Warehouse" => "IFNULL(p.Warehouse, co.POSDefaultWarehouse)",
                "Status" => 's.Oid'
            ]);
            $query = "INSERT INTO trdtransactionstock (%s)
                SELECT %s
                FROM {$fromDetailTable} WHERE {$whereClause} AND i.IsStock = 1 ";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query); //INSERT STOCK

            POSETicketUpload::where('Oid', $id)
            ->update([
                'Status' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $data = POSETicketUpload::findOrFail($id);
            if ($this->isPeriodClosed($data->DateFrom)) {
                $this->throwPeriodIsClosedError($data->DateFrom);
            }
            $data->Journals()->delete();
            $data->Stocks()->delete();
            POSETicketUpload::where('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }

    public function cancelled($id)
    {
        DB::transaction(function() use ($id) {
            $data = POSETicketUpload::findOrFail($id);
            if ($this->isPeriodClosed($data->DateFrom)) {
                $this->throwPeriodIsClosedError($data->DateFrom);
            }
            $data->Journals()->delete();
            $data->Stocks()->delete();
            POSETicketUpload::where('Oid', $id)
            ->update([
                'Status' => Status::cancelled()->value('Oid'),
            ]);
        });
    }
}