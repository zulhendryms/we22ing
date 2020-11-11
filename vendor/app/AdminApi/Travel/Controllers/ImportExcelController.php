<?php

namespace App\AdminApi\Travel\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Maatwebsite\Excel\Excel;
use App\AdminApi\Travel\Services\Excel\Imports\TravelPackageImport;
 
class ImportExcelController extends Controller 
{
    private $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx'
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $this->excel->import(new TravelPackageImport, $file);
            return response()->json(
                null, Response::HTTP_CREATED
            );
        }
    }
}
