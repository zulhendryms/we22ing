<?php

namespace App\AdminApi\Master\Services;

use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\City;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemUnit;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Trading\Entities\PurchaseInvoiceDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class PurchaseInvoiceExcelImport implements OnEachRow
{
    protected $data;

    public function __construct(PurchaseInvoice $data)
    {
        $this->data = $data;
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $item = Item::where('Code', $row[0])->first();
        $itemUnit = ItemUnit::where('Code', 'PCS')->first();
        $amount = (((int) $row[1]) * ((float) $row[2]));

        $return = PurchaseInvoiceDetail::firstOrCreate([
            'Company' => $this->data->Company,
            'PurchaseInvoice' => $this->data->Oid,
            'Item' => $item->Oid,
            'Quantity' => $row[1],
            'Price' => $row[2],
            'ItemUnit' => $item->ItemUnit ?? $itemUnit->Oid,
            'SubtotalAmount' => $amount,
            'TotalAmount' => $amount,
        ]);
    
    }
}