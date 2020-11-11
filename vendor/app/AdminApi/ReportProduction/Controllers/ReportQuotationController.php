<?php

namespace App\AdminApi\ReportProduction\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\Account;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Master\Entities\BusinessPartner;
use App\AdminApi\ReportProduction\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportQuotationController extends Controller
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
        $this->reportName = 'quotation1';
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
        $criteria = ""; $filter=""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; 

        $datefrom = Carbon::parse($request->input('datefrom'));
        $dateto = Carbon::parse($request->input('dateto'));

        $criteria = $criteria." AND DATE_FORMAT(po.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
        $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
        $criteria = $criteria." AND DATE_FORMAT(po.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
        $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

        $criteria2 = $criteria2." AND po.Date < '".$datefrom->format('Y-m-d')."'";
        $criteria3 = $criteria3." AND po.Date < '".$datefrom->format('Y-m-d')."'";

        if ($request->input('businesspartner')) {
            $val = BusinessPartner::find($request->input('businesspartner'));
            if ($val) {
                $criteria = $criteria." AND po.Customer = '".$val->Oid."'";
                $filter = $filter."B. Partner = '".strtoupper($val->Name)."'; ";
            }
        }
        
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);
        
        logger($query);
        $data = DB::select($query);

        switch ($reportname) {
            case 'quotation1':
                $reporttitle = "Report Quotation";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                $headerHtml = view('AdminApi\ReportProduction::pdf.header', compact('user', 'reportname', 'filter','reporttitle'))
                    ->render();
                $footerHtml = view('AdminApi\ReportProduction::pdf.footer', compact('user'))
                    ->render();
            break;
            case 'quotation2':
                $reporttitle = "Report Quotation 2";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                $headerHtml = view('AdminApi\ReportProduction::pdf.header2', compact('user', 'reportname', 'filter','reporttitle'))
                    ->render();
                $footerHtml = view('AdminApi\ReportProduction::pdf.footer2', compact('user'))
                    ->render();
            break;
        }

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
                route('AdminApi\ReportProduction::view',
                ['reportName' => $reportPath]), Response::HTTP_OK
            );
    }

    private function query($reportname) {
        switch ($reportname) {
            case 'quotation1':
                return "SELECT po.Oid,
                    po.Code,
                    po.Date,
                    po.Customer,
                    poid.Width,
                    poid.Height,
                    poid.SalesAmount,
                    poid.SalesAmountDescription,
                    po.DeliveryDate,
                    bp.Name AS CustomerName,
                    poi.Oid AS ProductionOrderItem,
                    poid.Oid AS ProductionOrderItemDetail,
                    ip.Name AS ItemProduct,
                    ig.Name AS ItemGlass
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                    LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                    LEFT OUTER JOIN mstbusinesspartner bp ON po.Customer = bp.Oid
                    WHERE po.GCRecord IS NULL  AND 1=1
                    GROUP BY po.Oid, po.Code, po.Date, po.Customer,bp.Name, po.DeliveryDate,poi.Oid,poid.Oid,ip.Name,ig.Name,poid.Width, poid.Height,poid.SalesAmount,poid.SalesAmountDescription";
            break;
            case 'quotation2':
                    return "SELECT po.Code,
                        po.Date,
                        bp.Name AS BusinessPartner,
                        bp.ContactPerson,
                        bp.FullAddress,
                        ip.Name AS ItemProduction,
                        ig.Name AS ItemGlass,
                        poid.Width,
                        poid.Height,
                        poid.Quantity,
                        poid.SalesAmount,
                        poid.Note,
                        po.Note,
                        po.Discount1, 
                        po.Discount2
                    FROM prdorder po 
                    LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                    LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                    ORDER BY ip.Name";
            break;
        }
        return "";
  }


}