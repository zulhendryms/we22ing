<?php

namespace App\AdminApi\ReportProduction\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\Warehouse;
use App\Core\Master\Entities\Item;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportSalesGFController extends Controller
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
        $this->reportName = 'reportsalesgf';
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
        $criteria = "";
        $criteria2 = "";
        $criteria3 = "";
        $criteria4 = "";
        $filter = "";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $criteria = $criteria . " AND DATE_FORMAT(pr.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
        $criteria = $criteria . " AND DATE_FORMAT(pr.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";

        $criteria2 = $criteria2 . " AND pr.Date < '" . $datefrom->format('Y-m-d') . "'";
        $criteria3 = $criteria3 . " AND pr.Date < '" . $datefrom->format('Y-m-d') . "'";

        $criteria4 = $criteria4 . reportQueryCompany('trdtransactionstock');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        
        if ($filter) $filter = substr($filter, 0);
        if ($criteria) $query = str_replace(" AND 1=1", $criteria, $query);
        if ($criteria2) $query = str_replace(" AND 2=2", $criteria2, $query);
        if ($criteria3) $query = str_replace(" AND 3=3", $criteria3, $query);
        if ($criteria4) $query = str_replace(" AND 4=4", $criteria4, $query);

        logger($query);
        $data = DB::select($query);
// dd($query);
        switch ($reportname) {
            case 'salesgf':
                $reporttitle = "Report Sales by GF";
                $pdf = SnappyPdf::loadview('AdminApi\ReportProduction::pdf.salesgf', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
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
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10);

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

    private function query($reportname)
    {
        switch ($reportname) {
            case 'salesgf':
                return"SELECT 
                pr.Oid,
                pr.Code AS Production,
                DATE_FORMAT(pr.Date, '%d %b %Y') AS Date,
                bp.Name AS BusinessPartner,
                UPPER(s.Code) AS Status,
                pr.Note AS Note,
                pr.DiscountAmount1, 
                pr.DiscountAmount2, 
                pr.TotalAmount
                
                FROM prdorder pr
                LEFT OUTER JOIN mstbusinesspartner bp ON pr.Customer = bp.Oid
                LEFT OUTER JOIN sysstatus s ON pr.Status = s.Oid
                WHERE pr.GCRecord IS NULL AND 1=1
                ORDER BY bp.Name, Date ASC
                ";
            break;

        }
        return "";
    }
}
