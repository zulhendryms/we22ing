<?php

namespace App\Core\Internal\Export;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class DataExport implements FromCollection, WithHeadings
{
    private $heading;
    private $dataExport;

    public function __construct($dataExport){
        $this->dataExport = $dataExport;
        $arr = [];

        if($dataExport){
            $checkMethod = method_exists($dataExport[0],'getAttributes');
            if($checkMethod){
                foreach ($dataExport[0]->getAttributes() as $field => $value) array_push($arr, $field);
            }else{
                foreach ($dataExport[0] as $field => $value) array_push($arr, $field);
            }
        }

        $this->heading = $arr;
    }
    public function headings(): array
    {
        return $this->heading;
    }
    
    public function collection()
    {
        return collect($this->dataExport);
        
    }
}