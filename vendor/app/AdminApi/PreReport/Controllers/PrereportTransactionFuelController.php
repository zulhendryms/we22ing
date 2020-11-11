<?php

namespace App\AdminApi\PreReport\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Trucking\Entities\TruckingTransactionFuel;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class PrereportTransactionFuelController extends Controller
{
    protected $reportService;
    protected $reportName;
    private $crudController;

    /**
     * @param ReportService $reportService
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'prereport-transactionfuel';
        $this->reportService = $reportService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function view(Request $request, $reportName)
    {
        return response()
            ->file($this
                ->reportService
                ->setFileName($reportName)
                ->getFilePath())
            ->deleteFileAfterSend(true);
    }

    public function report(Request $request, $Oid = null)
    {
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $request->has('report') ? $request->input('report') : 'transactionfuel';
        $Oid = $request->input('oid');
        
        $query = $this->query($reportname, $Oid);
        $data = DB::select($query);
        // dd($query);
        switch ($reportname) {
            case 'transactionfuel':
                    $reporttitle = "PreReport Transaction Fuel";
                    // return view('AdminApi\PreReport::pdf.prereportcashbank',  compact('data','reporttitle','reportname'));
                    $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_'. $reportname, compact('data', 'reporttitle', 'reportname'));
                    $pdf
                        ->setOption('footer-right', "Page [page] of [toPage]")
                        ->setOption('footer-font-size', 5)
                        ->setOption('footer-line', true)
                        // ->setOption('page-width', '215.9')
                        ->setOption('page-height', '297')
                        ->setOption('margin-right', 15)
                        ->setOption('margin-bottom', 10);
                }


        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action')=='download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action')=='export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath),Response::HTTP_OK);
        if ($request->input('action')=='email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath),Response::HTTP_OK);
        if ($request->input('action')=='post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath),Response::HTTP_OK);
        return response()
            ->json(
                route(
                    'AdminApi\Report::view',
                    ['reportName' => $reportPath]
                ),
                Response::HTTP_OK
            );
    }

    private function query($reportname, $Oid)
    {
        switch ($reportname) {
            case 'transactionfuel':
                return "SELECT
                t.Oid,
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                t.Code AS Code,
                pm.Name AS PrimeMover,
                d.Name AS Department,
                e.Name AS Employee,
                t.Type AS Type,
                t.Quantity, t.Note,
                u1.Name AS Approval1
                
                FROM trctransactionfuel t
                LEFT OUTER JOIN company co ON t.Company = co.Oid
                LEFT OUTER JOIN trcprimemover pm ON t.TruckingPrimeMover = pm.Oid
                LEFT OUTER JOIN mstdepartment d ON t.Department = d.Oid
                LEFT OUTER JOIN mstemployee e ON t.Employee = e.Oid
                LEFT OUTER JOIN user u1 ON d.Approval1 = u1.Oid
                WHERE t.GCRecord IS NULL AND t.Oid =  '" . $Oid . "'
                ORDER BY t.CreatedAt ASC
                ";
        }
        return " ";
    }
}
