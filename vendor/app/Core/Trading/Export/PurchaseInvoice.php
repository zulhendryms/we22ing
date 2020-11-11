<?php

namespace App\Core\Trading\Export;

use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Master\Entities\Currency;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceExport implements FromCollection, WithHeadings
{
    private $where;
    //dasdsadsa

    public function __construct($where){
        $this->where = $where;
    }
    public function headings(): array
    {
        return [
            'Code',
            'Date',
            'Business Partner',
            'Item',
            'Qty',
            'Cur',
            'Price',
            'Total',
            'Rate'
        ];
    }
    public function collection()
    {
        // $query = DB::table('trdpurchaseinvoice as p')
        //     ->select('p.Oid','p.Code','p.Date', 'bp.Code', 'bp.Name','i.Code','i.Name','d.Quantity','c.Code','d.Price','d.TotalAmount')
        //     ->leftJoin('trdpurchaseinvoicedetail as d', 'p.Oid', '=', 'd.PurchaseInvoice')
        //     ->leftJoin('mstbusinesspartner as bp', 'bp.Oid', '=', 'p.BusinessPartner')
        //     ->leftJoin('mstitem as i', 'i.Oid', '=', 'd.Item')
        //     ->leftJoin('mstcurrency as c', 'c.Oid', '=', 'p.Currency')->limit(5)->orderBy('p.Code');
        // if ($query) $query->where($this->where);
        $query = "SELECT p.Code PurchaseInvoice, DATE_FORMAT(p.Date, '%Y-%m%-%d') Date, 
        CONCAT(bp.Name,' - ',bp.Code) BusinessPartner, CONCAT(i.Name,' - ',i.Code) Item, 
        IFNULL(d.Quantity,0) Qty, c.Code Currency, 
        IFNULL(d.Price,0) Price, 
        IFNULL(d.Quantity,0)*IFNULL(d.Price,0) TotalAmount, p.Rate
        FROM trdpurchaseinvoice p
        LEFT OUTER JOIN trdpurchaseinvoicedetail d ON p.Oid = d.PurchaseInvoice
        LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.BusinessPartner
        LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
        LEFT OUTER JOIN mstcurrency c ON c.Oid = p.Currency";
        $data = DB::select($query);
        return collect($data);
        
    }
}