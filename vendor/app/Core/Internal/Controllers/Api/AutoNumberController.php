<?php

namespace App\Core\Internal\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\Internal\Entities\AutoNumberSetup;
use App\Core\Internal\Services\AutoNumberService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AutoNumberController extends Controller 
{

    private $autoNumberService;

    public function __construct(AutoNumberService $autoNumberService)
    {
        $this->autoNumberService = $autoNumberService;
    }

    public function index(Request $request)
    {
        // logger($request->fullUrl());
        // logger($request->all());
        $this->validate($request, [
            'AutoNumberSetup' => 'required',
            'Oid' => 'required'
        ]);
        $setup = AutoNumberSetup::findOrFail($request->input('AutoNumberSetup'));
        $tableQuery = $setup->TableQuery;

        $class = config('autonumber.'.$tableQuery);

        throw_if(is_null($class), new ModelNotFoundException("Model for $tableQuery not found"));
        
        $obj = $class::findOrFail($request->input('Oid'));
        $this->autoNumberService->generate($obj, $setup->Oid);
    }

}