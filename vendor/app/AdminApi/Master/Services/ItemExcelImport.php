<?php

namespace App\AdminApi\Master\Services;

use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemGroup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class ItemExcelImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $company = Auth::user()->CompanyObj;
        $itemGroup = ItemGroup::where('Name', $row[5])->first();
        $businessPartner = BusinessPartner::first();
        $currency = Currency::where('Code', $row[2])->first(); // [zfx] TODO later change Food to Supplier
        

        $return = Item::firstOrCreate([
            'CreatedAt' => Carbon::now(),
            'IsActive' => true,
            'IsParent' => false,
            'IsDetail' => false,
            'IsPurchase' => false,
            'IsSales' => true,
            'Code' => $row[0] ?? now()->format('ymdHis').'-'.str_random(3),
            'Name' => $row[1],
            'NameEN' => $row[1],
            'NameID' => $row[1],
            'NameZH' => $row[1],
            'NameTH' => $row[1],
            'Company' => $company->Oid,
            'ItemUnit' => $company->ItemUnit,
            'ItemGroup' => $itemGroup->Oid,// [zfx] EXCEL DEFAULT ???
            'ItemAccountGroup' => $itemGroup->ItemAccountGroup,
            'City' => $company->City,
            'PurchaseBusinessPartner' => $businessPartner->Oid,
            'PurchaseCurrency' => $currency->Oid ?? $company->Currency,
            'SalesCurrency' => $currency->Oid ?? $company->Currency,
            'SalesAmount' => $row[3],
            'PurchaseAmount' => $row[4],
        ]);
    }
}