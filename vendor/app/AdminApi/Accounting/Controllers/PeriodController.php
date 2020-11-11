<?php

namespace App\AdminApi\Accounting\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\Period;
use App\Core\Accounting\Services\ProcessJournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PeriodController extends Controller
{
    protected $journalService;
    public function __construct(ProcessJournalService $journalService)
    {
        $this->journalService = $journalService;
    }
    public function index(Request $request)
    {
        try {            
            $user = Auth::user();
            $data = Period::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('year')) {
                $data = $data
                    ->where('DatePeriod', 'like', $request->year.'%');
            }
            $data = $data->get();
            foreach($data as $row){
                $Status = $row->Status;
                $row->Role = [
                    'IsRead' => 1,
                    'IsProcess' => 0,
                    'IsClose' => 0
                ];
                if($Status == 0){
                    $row->StatusName = 'Open';
                    $row->Role = [
                        'IsRead' => 0,
                        'IsProcess' => 1,
                        'IsClose' => 1
                    ];
                }else if($Status == 1){
                    $row->StatusName = 'Close';
                    $row->Role = [
                        'IsRead' => 1,
                        'IsProcess' => 1,
                        'IsClose' => 1
                    ];
                }
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function open(Period $data)
    {
        try {            
            $this->journalService->open($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function close(Period $data)
    {
        try {            
            $this->journalService->close($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            ); 
        }
    }
    public function process(Period $data)
    {
        try {            
            $this->journalService->process($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function pos()
    {
        try {            
            $this->journalService->processPOS();
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
            