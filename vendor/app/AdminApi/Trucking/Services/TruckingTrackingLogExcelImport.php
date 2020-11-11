<?php

namespace App\AdminApi\Trucking\Services;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Trucking\Entities\TruckingTrackingLog;

class TruckingTrackingLogExcelImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        if (!isset($row[0])) return null;
        $return = TruckingTrackingLog::firstOrCreate([
            'TruckingPrimeMoverText' => isset($row[0]) ? $row[0] : null,
            'DateFrom' => isset($row[1]) ? $row[1] : null,
            'DateUntil' => isset($row[2]) ? $row[2] : null,
            'AddressFrom' => isset($row[3]) ? $row[3] : null,
            'AddressUntil' => isset($row[4]) ? $row[4] : null,
            'FuelUsed' => isset($row[5]) ? $row[5] : 0,
            'KMPerLitre' => isset($row[6]) ? $row[6] : 0,
            'LitrePer100KM' => isset($row[7]) ? $row[7] : 0,
            'IdleTime' => isset($row[8]) ? $row[8] : null,
            'NetDrivingTime' => isset($row[9]) ? $row[9] : null,
        ]);
    
    }
}