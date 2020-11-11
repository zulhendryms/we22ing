<?php

namespace App\AdminApi\Master\Services;

use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\Master\Entities\City;
use App\Core\Master\Entities\Currency;

class CustomerExcelImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $businessPartnerGroup = BusinessPartnerGroup::where('Name',$row[2])->first();
        logger($businessPartnerGroup);

        $return = BusinessPartner::firstOrCreate([
            'Code' => $row[0],
            'Name' => $row[1],
            'BusinessPartnerRole' => BusinessPartnerRole::where('Code','Customer')->first()->Oid,
            'BusinessPartnerGroup' => $businessPartnerGroup->Oid,
            'BusinessPartnerAccountGroup' => $businessPartnerGroup->BusinessPartnerAccountGroup,
            'City' => City::where('Name',$row[3])->first()->Oid,
            'PurchaseCurrency' => Currency::where('Code',$row[4])->first()->Oid,      
            'SalesCurrency' => Currency::where('Code',$row[4])->first()->Oid,
            'IsActive' => $row[5],
        ]);
    
    }
}