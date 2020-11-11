<?php

namespace App\AdminApi\Master\Services;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\PaymentTerm;

class PaymentTermExcelImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $return = PaymentTerm::firstOrCreate([
            'Code' => $row[0],
            'Name' => $row[1],
            'Interval' => $row[2],
            'IsActive' => $row[3],
        ]);
    
    }
}