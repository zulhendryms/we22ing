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

class ReportPrintOrderController extends Controller
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
        $this->reportName = 'order';
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

    public function report(Request $request, $Oid = null )
    {
        $user = Auth::user();
        $headertitle="ORDER";
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $query = $this->query($reportname, $Oid);
        
        $data = DB::select($query);

        $query = "SELECT po.Code,
                po.Date,
                s.Name AS StatusName,
                po.DeliveryDate,
                bp.Name AS BusinessPartner,
                bp.ContactPerson,
                bp.FullAddress,
                d.Name AS Department,
                CASE WHEN ip1.Name IS NULL THEN NULL ELSE CONCAT(ip1.Name, ' - ', ig1.Name) END AS ItemProduction1,
                CASE WHEN ip2.Name IS NULL THEN NULL ELSE CONCAT(ip2.Name, ' - ', ig2.Name) END AS ItemProduction2,
                CASE WHEN ip3.Name IS NULL THEN NULL ELSE CONCAT(ip3.Name, ' - ', ig3.Name) END AS ItemProduction3,
                CASE WHEN ip4.Name IS NULL THEN NULL ELSE CONCAT(ip4.Name, ' - ', ig4.Name) END AS ItemProduction4,
                CASE WHEN ip5.Name IS NULL THEN NULL ELSE CONCAT(ip5.Name, ' - ', ig5.Name) END AS ItemProduction5,
                poi.Note AS NoteParent,
                po.Discount1, 
                po.Discount2,
                poi.Image,
                poi.Oid AS ProductionOrderItem,
                poi.Description
                FROM prdorder po 
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN mstdepartment d ON d.Oid = po.Department
                LEFT OUTER JOIN mstitem ip1 ON ip1.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig1 ON ig1.Oid = poi.ItemGlass1
                LEFT OUTER JOIN mstitem ip2 ON ip2.Oid = poi.ItemProduct2
                LEFT OUTER JOIN mstitem ig2 ON ig2.Oid = poi.ItemGlass2
                LEFT OUTER JOIN mstitem ip3 ON ip3.Oid = poi.ItemProduct3
                LEFT OUTER JOIN mstitem ig3 ON ig3.Oid = poi.ItemGlass3
                LEFT OUTER JOIN mstitem ip4 ON ip4.Oid = poi.ItemProduct4
                LEFT OUTER JOIN mstitem ig4 ON ig4.Oid = poi.ItemGlass4
                LEFT OUTER JOIN mstitem ip5 ON ip5.Oid = poi.ItemProduct5
                LEFT OUTER JOIN mstitem ig5 ON ig5.Oid = poi.ItemGlass5
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
            WHERE po.GCRecord IS NULL  AND po.Oid = '".$Oid."'               
            ORDER BY poi.CreatedAt, poi.Oid";
        $parent = DB::select($query);


        // return view('AdminApi\ReportProduction::pdf.quotation2', compact('data', 'headertitle'));

        // $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.quotation2', compact('data'));
        
        // $headerHtml = view('AdminApi\ReportProduction::pdf.header2', compact('data','date','headertitle'))
        //     ->render();
        // $footerHtml = view('AdminApi\ReportProduction::pdf.footer2')
        //     ->render();
        
        switch ($reportname) {
            case 'order':
                $reporttitle = "Report ORDER";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.order', compact('data', 'reportname','parent'));
                $headerHtml = view('AdminApi\ReportProduction::pdf.header3', compact( 'reportname', 'user'))
                ->render();
                $footerHtml = view('AdminApi\ReportProduction::pdf.footer3', compact('data'))
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

    private function query($reportname, $Oid) {
        switch ($reportname) {
            default:
                return "SELECT poid.Code,
                po.Date,
                s.Name AS StatusName,
                po.DeliveryDate,
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
                poid.Apprx,
                poid.Quantity,
                poid.SalesAmount,
                poid.Note,
                poi.Note AS NoteParent,
                po.Discount1, 
                po.Discount2,
                poi.Image,
                poid.ProductionOrderItem,
                igg.Code AS ItemGroup,
                pop.AdditionalInfo1 AS Line1,
                pop.AdditionalInfo2 AS Line2
                FROM prdorder po 
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstitem ip1 ON ip1.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig1 ON ig1.Oid = poi.ItemGlass1
                LEFT OUTER JOIN mstitem ip2 ON ip2.Oid = poi.ItemProduct2
                LEFT OUTER JOIN mstitem ig2 ON ig2.Oid = poi.ItemGlass2
                LEFT OUTER JOIN mstitem ip3 ON ip3.Oid = poi.ItemProduct3
                LEFT OUTER JOIN mstitem ig3 ON ig3.Oid = poi.ItemGlass3
                LEFT OUTER JOIN mstitem ip4 ON ip4.Oid = poi.ItemProduct4
                LEFT OUTER JOIN mstitem ig4 ON ig4.Oid = poi.ItemGlass4
                LEFT OUTER JOIN mstitem ip5 ON ip5.Oid = poi.ItemProduct5
                LEFT OUTER JOIN mstitem ig5 ON ig5.Oid = poi.ItemGlass5
                LEFT OUTER JOIN mstitemgroup igg ON igg.Oid = ip1.ItemGroup
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                LEFT OUTER JOIN (
                SELECT pop.ProductionOrderItem, pop.AdditionalInfo1,pop.AdditionalInfo2 
                FROM prdorderitemprocess pop 
                INNER JOIN prdprocess p ON p.Oid = pop.ProductionProcess AND p.Code = 'Bvl' -- AND pop.Valid = 1
                GROUP BY pop.ProductionOrderItem
                ) pop ON pop.ProductionOrderItem = poi.Oid
            WHERE po.GCRecord IS NULL  AND po.Oid = '".$Oid."'               
            ORDER BY poid.ProductionOrderItem, poid.Sequence";
        }
        return " ";
    }
}