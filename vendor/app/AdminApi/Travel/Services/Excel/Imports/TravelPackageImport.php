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
use App\Core\Master\Entities\ItemGroup;

class TravelPackageImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $travelPackage = TravelPackage::updateOrCreate(
            [
                'Code' => $row[0]
            ],
            [
                'Name' => $row[1],
                'IsActive' => $row[2],
            ],
        );

        $itemGroup = ItemGroup::where('Code', $row[4])->first();

        $travelPackage = TravelPackage::updateOrCreate(
            [
                'Code' => $row[0]
            ],
            [
                'Name' => $row[1],
                'IsActive' => $row[2],
            ],
        );
    
        $travelPackage->Details()->create([
            'Name' => $row[3],
            'ItemGroup' => $itemGroup->Oid,
        ]);

        // zfx: Do not copy the following code
        // zfx: THIS USED FOR TESTING PURPOSES ONLY
        // try {
        //     if ($rowIndex === 2) {
        //         if (!$row[1]) {
        //             throw new \Exception("Error Processing Request", 1);
        //         }
        //     }
            
        //     $travelPackage = TravelPackage::firstOrCreate([
        //         'code' => $row[0],
        //     ]);
        
        //     $travelPackage->Details()->create([
        //         'name' => $row[1],
        //     ]);
        // } catch (\Throwable $th) {
        //     throw $th;
        // }
    }
}