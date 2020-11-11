<?php

namespace App\AdminApi\Report\Controllers;

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

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportStockEticketController extends Controller
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
        $this->reportName = 'report-stock-eticket';
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
        $criteria = ""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; 
        $filter="";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $criteria = $criteria." AND DATE_FORMAT(et.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
        $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
        $criteria = $criteria." AND DATE_FORMAT(et.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
        $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

        $criteria2 = $criteria2." AND et.Date < '".$datefrom->format('Y-m-d')."'";
        $criteria3 = $criteria3." AND beg.Date < '".$datefrom->format('Y-m-d')."'";

        if ($request->input('p_ItemGroup')) {
            $val = ItemGroup::find($request->input('p_ItemGroup'));
            $criteria3 = $criteria3." AND i.ItemGroup = '".$val->Oid."'";
            $criteria4 = $criteria4." AND i.ItemGroup = '".$val->Oid."'";
            $filter = $filter."Item Group = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_Warehouse')) {
            $val = Warehouse::findOrFail($request->input('p_Warehouse'));
            $criteria = $criteria." AND et.Warehouse = '".$val->Oid."'";
            $criteria2 = $criteria2." AND et.Warehouse = '".$val->Oid."'";
            $filter = $filter."Warehouse = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_Item')) {
            $val = Item::findOrFail($request->input('p_Item'));
            $criteria = $criteria." AND et.Item = '".$val->Oid."'";
            $criteria2 = $criteria2." AND et.Item = '".$val->Oid."'";
            $filter = $filter."Item = '".strtoupper($val->Name)."'; ";
        }
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);
        if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
        if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
        if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);
        
        logger($query);
        $data = DB::select($query);

        switch ($reportname) {
            case 1:
                $reporttitle = "Report Stock E-Ticket Detail";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.stocketicket1', compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 2:
                $reporttitle = "Report Stock E-Ticket Summary";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.stocketicket2', compact('data', 'user', 'reportname','filter', 'reporttitle'));
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
                route('AdminApi\Report::view',
                ['reportName' => $reportPath]), Response::HTTP_OK
            );
    }

    private function query($reportname) {
        switch ($reportname) {
            case 1:
                return "SELECT 
                co.Code AS Comp,
                i.Oid AS Item,
                CONCAT(ic.Name,' - ',ic.Code) AS GroupName,
                i.Code, IFNULL(i.Initial,i.Subtitle) AS Name,
                COUNT(*) AS Stock, et.CostPrice, et.Type,
                COUNT(*) * et.CostPrice AS TotalCost,
                DATE_FORMAT(IFNULL(et.DateExpiry,'3000-01-01'), '%Y-%m-%d') AS DateExpiry,
                MAX(DATE_FORMAT(et.CreatedAt, '%Y-%m-%d')) AS Uploaded
                FROM poseticket et 
                LEFT OUTER JOIN mstitem i ON i.Oid = et.Item
                LEFT OUTER JOIN mstitemcontent ic ON ic.Oid = i.ItemContent
                LEFT OUTER JOIN company co ON co.Oid = et.Company
                WHERE et.PointOfSale IS NULL AND i.APIType = 'auto_stock'
                GROUP BY i.Oid, i.Code, i.Name, et.CostPrice, et.Type,DATE_FORMAT(IFNULL(et.DateExpiry,'3000-01-01'), '%Y-%m-%d')
                ORDER BY i.Name";
            break;
            case 2:
                return "SELECT
                co.Code AS Comp,
                i.Oid AS Item,
                CONCAT(ic.Name,' - ',ic.Code) AS GroupName,
                i.Code,
                IFNULL(i.Initial,i.Subtitle) AS Name,
                COUNT(*) AS Stock, SUM(et.CostPrice) AS TotalCost,
                SUM(et.CostPrice)/COUNT(*) AS CostPrice, et.Type,
                MIN(DATE_FORMAT(IFNULL(et.DateExpiry,'3000-01-01'), '%Y-%m-%d')) AS DateExpiry,
                MAX(DATE_FORMAT(et.CreatedAt, '%Y-%m-%d')) AS Uploaded
                FROM poseticket et
                LEFT OUTER JOIN mstitem i ON i.Oid = et.Item
                LEFT OUTER JOIN mstitemcontent ic ON ic.Oid = i.ItemContent
                LEFT OUTER JOIN company co ON co.Oid = et.Company
                WHERE et.PointOfSale IS NULL AND i.APIType = 'auto_stock'
                GROUP BY i.Oid, i.Code, i.Name, et.Type
                ORDER BY i.Name";
            break;
        }
        return "";
    }


}