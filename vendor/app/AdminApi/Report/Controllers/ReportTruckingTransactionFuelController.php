<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\BusinessPartnerAccountGroup;
use App\Core\Trucking\Entities\TruckingPrimeMover;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportTruckingTransactionFuelController extends Controller
{
    /** @var ReportService $reportService */
    protected $reportService;

    protected $reportName;

    /**
     * @param ReportService $reportService
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'report_transactionfuel';
        $this->reportService = $reportService;
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

    public function report(Request $request)
    {
        $reportname = $request->input('report');
        $user = Auth::user();
        $query = $this->query($reportname);
        $criteria = ""; $filter="";
        $criteria2 = ""; $filter="";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));
        $criteria = $criteria . " AND DATE_FORMAT(tf.CreatedAt, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('d-m-Y')) . "'; ";

        $criteria = $criteria . reportQueryCompany('trctransactionfuel');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria = $criteria . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('PrimeMover')) {
            $val = TruckingPrimeMover::findOrFail($request->input('PrimeMover'));
            $criteria = $criteria." AND tf.TruckingPrimeMover = '".$val->Oid."'";
            $filter = $filter."TruckingPrimeMover = '".strtoupper($val->Name)."'; ";
        } 

        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);
        if ($criteria2) $query = str_replace(" AND 2=2", $criteria2, $query);
  
        $data = DB::select($query);

        switch ($reportname) {
            case 'daily':
                $reporttitle = "Report Transaction Fuel by Daily";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.truckingtransactionfuel_'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'primemover':
                $reporttitle = "Report Transaction Fuel by PrimeMover";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.truckingtransactionfuel_'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
          }

        $headerHtml = view('AdminApi\Report::pdf.header', compact('user', 'reportname', 'filter', 'reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\Report::pdf.footer', compact('user'))
            ->render();

        $pdf
            ->setOption('header-html', $headerHtml)
            ->setOption('footer-html', $footerHtml)
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('margin-right', 15)
            ->setOption('margin-bottom', 10);

        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action')=='download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action')=='export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath),Response::HTTP_OK);
        if ($request->input('action')=='email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath),Response::HTTP_OK);
        if ($request->input('action')=='post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath),Response::HTTP_OK);
        return response()
            ->json(
                route('AdminApi\Report::view',
                ['reportName' => $reportPath]), Response::HTTP_OK
            );
    }

    private function query($reportname) {
        switch ($reportname) {
            default:
                return "SELECT tf.Oid, tf.Code, pm.Name AS PrimeMover, tf.Quantity, 
                s.Name Status, d.Name Department, tf.Type, tf.Note, co.Name Company,
                DATE_FORMAT(tf.CreatedAt, '%d %M %Y') AS Date
                FROM trctransactionfuel tf
                LEFT OUTER JOIN trcprimemover pm ON tf.TruckingPrimeMover = pm.Oid
                LEFT OUTER JOIN company co ON tf.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON tf.Status = s.Oid
                LEFT OUTER JOIN mstdepartment d ON tf.Department = d.Oid
                WHERE tf.GCRecord IS NULL AND 1=1
                ORDER BY Date ASC";
        }
        return "";
    }


}
