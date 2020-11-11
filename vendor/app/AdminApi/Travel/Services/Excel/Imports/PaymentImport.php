<?php

namespace App\AdminApi\Travel\Services\Excel\Imports;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Entities\TravelPackage;
use App\Core\Travel\Entities\TravelPackageDetail;
use App\Core\Master\Entities\PaymentTerm;

class PaymentImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $travelPackage = PaymentTerm::firstOrCreate([
            'Code' => $row[0],
            'Name' => $row[1],
            'Interval' => $row[2],
            'IsActive' => $row[3],
        ]);
    
    }
}