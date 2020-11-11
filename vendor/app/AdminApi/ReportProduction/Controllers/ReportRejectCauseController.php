<?php

namespace App\AdminApi\ReportProduction\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\Item;
use App\Core\Production\Entities\ProductionProcess;
use App\Core\Production\Entities\ProductionItemGlass;
use App\Core\Production\Entities\ProductionThickness;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportRejectCauseController extends Controller
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
        $this->reportName = 'productionrejectcause';
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

        $criteria4 = $criteria4 . reportQueryCompany('prdproduction');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Customer')) {
            $val = BusinessPartner::findOrFail($request->input('p_Customer'));
            $criteria = $criteria." AND bp.Oid = '".$val->Oid."'";
            $filter = $filter."AND Customer = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_ProductionProcess')) {
            $val = ProductionProcess::findOrFail($request->input('p_ProductionProcess'));
            $criteria = $criteria." AND pp.Oid = '".$val->Oid."'";
            $filter = $filter."AND ProductionProcess = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_ItemGlass')) {
            $val = Item::findOrFail($request->input('p_ItemGlass'));
            $criteria = $criteria." AND i.Oid= '".$val->Oid."'";
            $filter = $filter."AND ItemGlass = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_ItemProduction')) {
            $val = Item::findOrFail($request->input('p_ItemProduction'));
            $criteria = $criteria." AND i.Oid= '".$val->Oid."'";
            $filter = $filter."AND ItemProduction = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_ProductionThickness')) {
            $val = ProductionThickness::findOrFail($request->input('p_ProductionThickness'));
            $criteria = $criteria." AND pt.Oid = '".$val->Oid."'";
            $filter = $filter."AND Thickness = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_Status')) {
            $val = Status::findOrFail($request->input('p_Status'));
            $criteria = $criteria." AND s.Oid = '".$val->Oid."'";
            $filter = $filter."AND Status = '".strtoupper($val->Name)."'; ";
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
            case 'reject':
                $reporttitle = "Report Production Reject Cause";
                $pdf = SnappyPdf::loadView('AdminApi\ReportProduction::pdf.rejectcause', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
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
            default:
                return "SELECT 
                co.Name AS Company,
                bp.Name AS Customer,
                DATE_FORMAT(pr.Date, '%d %b %Y') AS Date,
                DATE_FORMAT(prj.Date, '%d %b %Y') AS DateReject,
                pr.QuantityReject,
                pr.NoteReject,
                pp.Name AS Process,
                po.Code AS OrderNo,
                i1.Name AS itemGlass1,
                pt.Name AS Thickness,
                ip1.Name AS itemProduct1,
                u.Name AS User
                FROM prdproduction pr
                LEFT OUTER JOIN prdproduction prj ON pr.ProductionReject = prj.Oid
                LEFT OUTER JOIN prdprocess pp ON pr.ProductionProcess = pp.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON pr.ProductionOrderItemDetail = poid.Oid
                LEFT OUTER JOIN prdorderitem poi ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN prdorder po ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN company co ON pr.Company = co.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON po.Customer = bp.Oid
                LEFT OUTER JOIN mstitem i1 ON poi.ItemGlass1 = i1.Oid
                LEFT OUTER JOIN prditemglass ig1 ON i1.Oid = ig1.Oid
                LEFT OUTER JOIN prdthickness pt ON ig1.ProductionThickness = pt.Oid
                LEFT OUTER JOIN mstitem ip1 ON poi.ItemProduct1 = ip1.Oid
                LEFT OUTER JOIN user u ON pr.CreatedBy = u.Oid
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                WHERE pr.NoteReject IS NOT NULL AND 1=1
                ORDER BY Date ASC
                ";
            break;
        }
        return "";
    }
}
