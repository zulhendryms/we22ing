<?php

namespace App\AdminApi\ReportProduction\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\AccountSection;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Internal\Entities\AccountType;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportPrintQuotationController extends Controller
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
        $this->reportName = 'quotation';
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

    public function report(Request $request, $Session = null )
    {
        $user = Auth::user();
        $headertitle="QUOTATION";
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $query = $this->query($reportname, $Session);
        
        $data = DB::select($query);
        // $query = "SELECT p.Oid, p.Quantity, p.Amount, CONCAT(IFNULL(p.Description, i.Name), ' - ', IFNULL(i.Code, '')) AS Item
        $query = "SELECT p.Oid, p.Quantity, p.Amount, IFNULL(p.Description, i.Name) AS Item
            FROM prdorderdetail p
            LEFT OUTER JOIN mstitem i ON i.Oid = p.Item WHERE p.ProductionOrder = '".$Session."'";
        $detail = DB::select($query);

        // return view('AdminApi\ReportProduction::pdf.quotation2', compact('data', 'headertitle'));

        // $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.quotation2', compact('data'));
        
        // $headerHtml = view('AdminApi\ReportProduction::pdf.header2', compact('data','date','headertitle'))
        //     ->render();
        // $footerHtml = view('AdminApi\ReportProduction::pdf.footer2')
        //     ->render();
        
        switch ($reportname) {
            case 'quotation':
                $reporttitle = "Report Quotation 2";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.quotation', compact('data', 'reportname', 'detail'));
                $headerHtml = view('AdminApi\ReportProduction::pdf.header2', compact( 'reportname', 'user'))
                ->render();
                $footerHtml = view('AdminApi\ReportProduction::pdf.footer2', compact('data'))
                    ->render();
            break;
        }

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

    private function query($reportname, $Session) {
        switch ($reportname) {
            default:
                return "SELECT po.Code,
                po.Date,
                bp.Name AS BusinessPartner,
                bp.ContactPerson,
                bp.FullAddress,
                CASE WHEN ip1.Name IS NULL THEN NULL ELSE CONCAT(ip1.Name, ' - ', ig1.Name) END AS ItemProduction1,
                CASE WHEN ip2.Name IS NULL THEN NULL ELSE CONCAT(ip2.Name, ' - ', ig2.Name) END AS ItemProduction2,
                CASE WHEN ip3.Name IS NULL THEN NULL ELSE CONCAT(ip3.Name, ' - ', ig3.Name) END AS ItemProduction3,
                CASE WHEN ip4.Name IS NULL THEN NULL ELSE CONCAT(ip4.Name, ' - ', ig4.Name) END AS ItemProduction4,
                CASE WHEN ip5.Name IS NULL THEN NULL ELSE CONCAT(ip5.Name, ' - ', ig5.Name) END AS ItemProduction5,
                poid.Width,
                poid.Height,
                poid.Quantity,
                poid.SalesAmount,
                poid.SalesAmountGlass,
                poid.Note,
                po.Note,
                po.Discount1, 
                po.Discount2,
                po.DiscountAmount1, 
                po.DiscountAmount2,
                po.SubtotalAmount, 
                po.SubtotalAmountGlass,
                po.SubtotalAmountItem,
                po.TotalAmount,
                po.TotalAmountBase,
                poid.ProductionOrderItem,
                poi.Description,
                ipd1.IsFreeForZeroPrice,
                poid.Description AS DetailDescription
            FROM prdorder po 
            LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
            LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
            LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
            LEFT OUTER JOIN mstitem ip1 ON ip1.Oid = poi.ItemProduct1
            LEFT OUTER JOIN prditem ipd1 ON ipd1.Oid = ip1.Oid
            LEFT OUTER JOIN mstitem ig1 ON ig1.Oid = poi.ItemGlass1
            LEFT OUTER JOIN mstitem ip2 ON ip2.Oid = poi.ItemProduct2
            LEFT OUTER JOIN mstitem ig2 ON ig2.Oid = poi.ItemGlass2
            LEFT OUTER JOIN mstitem ip3 ON ip3.Oid = poi.ItemProduct3
            LEFT OUTER JOIN mstitem ig3 ON ig3.Oid = poi.ItemGlass3
            LEFT OUTER JOIN mstitem ip4 ON ip4.Oid = poi.ItemProduct4
            LEFT OUTER JOIN mstitem ig4 ON ig4.Oid = poi.ItemGlass4
            LEFT OUTER JOIN mstitem ip5 ON ip5.Oid = poi.ItemProduct5
            LEFT OUTER JOIN mstitem ig5 ON ig5.Oid = poi.ItemGlass5
            WHERE po.GCRecord IS NULL  AND po.Oid = '".$Session."'               
            ORDER BY poi.CreatedAt, poid.Sequence, poid.ProductionOrderItem";
        }
        return " ";
    }
}