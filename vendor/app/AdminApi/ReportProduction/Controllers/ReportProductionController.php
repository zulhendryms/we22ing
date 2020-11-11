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

class ReportProductionController extends Controller
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
        $this->reportName = 'production1';
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

        $criteria4 = $criteria4." AND DATE_FORMAT(prd.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
        $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
        $criteria4 = $criteria4." AND DATE_FORMAT(prd.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
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
        if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
        if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
        if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);
        
        logger($query);
        $data = DB::select($query);

        switch ($reportname) {
            case 'production1':
                $reporttitle = "Report Production Order Detail Per Item";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'production2':
                $reporttitle = "Report Production Process Per Note";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'production3':
                $reporttitle = "Report Production Per Day";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
        }

        $headerHtml = view('AdminApi\ReportProduction::pdf.header', compact('user', 'reportname', 'filter','reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\ReportProduction::pdf.footer', compact('user'))
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
                route('AdminApi\ReportProduction::view',
                ['reportName' => $reportPath]), Response::HTTP_OK
            );
    }

    private function query($reportname) {
      switch ($reportname) {
            case 'production1':
                return "SELECT 
                    po.Oid, 
                    po.Code, 
                    po.Date, 
                    po.Customer,
                    bp.Name AS CustomerName, 
                    po.DeliveryDate,
                    poi.Oid, 
                    poi.ItemProduct1, 
                    CONCAT(i.Name,' - ',i.Code) ItemName,
                    CONCAT(pro.Name,' - ',pro.Code) ProcessName, SUM(IFNULL(prd.QuantityOrdered,0)) AS QtyTarget,
                    SUM(IFNULL(prd.QuantityProduction,0)) AS QtyActual,
                    SUM(IFNULL(prd.QuantityReject,0)) AS QtyReject,
                    SUM(IFNULL(prd.QuantityOrdered,0) - IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0)) AS Outstanding
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid AND poip.Valid=1
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND prd.ProductionProcess = poip.ProductionProcess
                    LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct1
                    LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                    LEFT OUTER JOIN mstbusinesspartner bp ON po.Customer = bp.Oid
                    LEFT OUTER JOIN prdprocess pro ON pro.Oid = poip.ProductionProcess
                    LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                    WHERE s.Code = 'Posted' AND po.GCRecord IS NULL AND 1=1
                    GROUP BY po.Oid, po.Code, po.Date, po.Customer,bp.Name, po.DeliveryDate,
                    poi.Oid, poi.ItemProduct1, i.Name,i.Code, pro.Name,pro.Code";
            case 'production2':
                return "SELECT 
                    po.Oid, 
                    CONCAT(pro.Name,' - ',pro.Code) ProcessName, 
                    po.Code, 
                    po.Date, 
                    po.Customer,
                    bp.Name AS CustomerName, 
                    po.DeliveryDate,
                    poi.Oid, 
                    poi.ItemProduct1, 
                    CONCAT(i.Name,' - ',i.Code) ItemName,
                    SUM(IFNULL(prd.QuantityOrdered,0)) AS QtyTarget,
                    SUM(IFNULL(prd.QuantityProduction,0)) AS QtyActual,
                    SUM(IFNULL(prd.QuantityReject,0)) AS QtyReject,
                    SUM(IFNULL(prd.QuantityOrdered,0) - IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0)) AS Outstanding
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND prd.ProductionProcess = poip.ProductionProcess
                    LEFT OUTER JOIN mstitem i ON i.Oid = poi.ItemProduct1
                    LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                    LEFT OUTER JOIN mstbusinesspartner bp ON po.Customer = bp.Oid
                    LEFT OUTER JOIN prdprocess pro ON pro.Oid = poip.ProductionProcess  
                    LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                    WHERE s.Code = 'Posted' AND po.GCRecord IS NULL AND 1=1
                    GROUP BY pro.Name,pro.Code, po.Oid, po.Code, po.Date, po.Customer,bp.Name, po.DeliveryDate,
                    poi.Oid, poi.ItemProduct1, i.Name,i.Code";
                break;
            case 'production3':
                return "SELECT 
                    result.Date,
                    result.ProcessCode,
                    result.ProcessName,
                    SUM(QuantityProduction) AS QtyProduction,
                    SUM(result.QuantityReject) AS QtyReject
                    FROM
                        (SELECT 
                        prd.Oid, 
                        DATE_FORMAT(prd.Date,'%Y-%m-%d') AS Date, 
                        pro.Oid AS ProductionProcess, 
                        pro.Code AS ProcessCode,
                        CONCAT(pro.Name,' - ',pro.Code) ProcessName, 
                        IFNULL(prd.QuantityProduction,0) AS QuantityProduction , 
                        IFNULL(prd.QuantityReject,0) AS QuantityReject
                    FROM prdproduction prd
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.Oid = prd.Oid
                    LEFT OUTER JOIN prdprocess pro ON pro.Oid = prd.ProductionProcess
                    WHERE IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0) != 0 AND 4=4
                    GROUP BY DATE_FORMAT(prd.Date,'%Y-%m-%d'), prd.Oid, pro.Oid,pro.Name,pro.Code) AS result  
                    GROUP BY result.Date, result.ProcessCode, result.ProcessName";
        }
      return "";
  }


}