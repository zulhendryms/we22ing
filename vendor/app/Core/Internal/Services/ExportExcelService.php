<?php

namespace App\Core\Internal\Services;

// use Maatwebsite\Excel\Excel;
use Excel;
use App\Core\Internal\Export\DataExport;
// use App\Core\Internal\Services\FileService;
use App\Core\POS\Services\POSETicketService;

class ExportExcelService 
{
    protected $fileService;
    public function __construct(POSETicketService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function export($data, $filename = null)
    {
        $filename = 'export_'.($filename ? $filename.'_' : '').now()->format('ymdHis').'.xlsx';

        $file = Excel::download(new DataExport($data), $filename);
        return $file;
    }


    public function exportWithSaveFile($data, $filename = null)
    {
        $filename = 'export_'.($filename ? $filename.'_' : '').now()->format('ymdHis').'.xlsx';

        // return Excel::create('test', function($excel) use ($data) {
        //     $excel->sheet('mySheet', function($sheet) use ($data)
        //     {
        //         $sheet->fromArray($data);
        //     });
        // })->download('xlsx');
        
        // $file = Excel::download(new DataExport($data), $filename);
        // return $file;
        // $file = base64_decode(substr($file, strpos($file, ',') + 1));
        // return $this->fileService->putFile($file, $filename);
    
        Excel::store(new DataExport($data), $filename, 'export');
            
        // $path = storage_path($filename);

        return route('Core\Export::excel', [ 'key' => $filename ]);
        
    }
}