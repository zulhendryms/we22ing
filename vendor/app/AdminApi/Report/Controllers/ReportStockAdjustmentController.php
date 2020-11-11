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
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportStockAdjustmentController extends Controller
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
        $this->reportName = 'stockadjustment';
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

        $criteria = $criteria . " AND DATE_FORMAT(st.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
        $criteria = $criteria . " AND DATE_FORMAT(st.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";

        $criteria2 = $criteria2 . " AND st.Date < '" . $datefrom->format('Y-m-d') . "'";
        $criteria3 = $criteria3 . " AND st.Date < '" . $datefrom->format('Y-m-d') . "'";

        $criteria4 = $criteria4 . reportQueryCompany('trdtransactionstock');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_ItemGroup')) {
            $val = ItemGroup::find($request->input('p_ItemGroup'));
            $criteria3 = $criteria3 . " AND i.ItemGroup = '" . $val->Oid . "'";
            $criteria4 = $criteria4 . " AND i.ItemGroup = '" . $val->Oid . "'";
            $filter = $filter . "Item Group = '" . strtoupper($val->Name) . "'; ";
        }
        if ($request->input('p_Warehouse')) {
            $val = Warehouse::findOrFail($request->input('p_Warehouse'));
            $criteria = $criteria . " AND st.Warehouse = '" . $val->Oid . "'";
            $criteria2 = $criteria2 . " AND st.Warehouse = '" . $val->Oid . "'";
            $filter = $filter . "Warehouse = '" . strtoupper($val->Name) . "'; ";
        }
        if ($request->input('p_Item')) {
            $val = Item::findOrFail($request->input('p_Item'));
            $criteria = $criteria . " AND st.Item = '" . $val->Oid . "'";
            $criteria2 = $criteria2 . " AND st.Item = '" . $val->Oid . "'";
            $filter = $filter . "Item = '" . strtoupper($val->Name) . "'; ";
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
            case 1:
                $reporttitle = "Report Daily By Warehouse";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.stockad1', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 2:
                $reporttitle = "Report Daily by Item";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.stockad2', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 3:
                $reporttitle = "Stock Adjustment with sales price";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.stockad2', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
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
            case 1:
                return "SELECT
                co.Code AS Comp,
                CONCAT(w.Name, ' - ', w.Code) AS GroupName,
                st.Code AS Code,
                DATE_FORMAT(st.Date, '%e %b %Y') AS Date,
                st.Note AS Note,
                i.name AS Item,
                IFNULL(st.Quantity,0) AS Qty,
                IFNULL(st.StockCost,0) AS Amount,
                (IFNULL(st.Quantity,0) * IFNULL(st.StockCost,0)) AS Total

                FROM trdtransactionstock st 
                LEFT OUTER JOIN mstwarehouse w ON st.Warehouse = w.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = st.Item
                LEFT OUTER JOIN company co ON co.Oid = st.Company
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                WHERE st.GCRecord IS NULL AND 1=1 AND 4=4  AND jt.Code IN ('STIN','Stock','STOUT')
                GROUP BY w.Name, w.Code, st.Code, DATE_FORMAT(st.Date, '%Y%m%d')
                ";
                break;//1=1 4=4
    
            case 2:
                return "SELECT
                co.Code AS Comp,
                CONCAT(w.Name, ' - ', w.Code) AS Warehouse,
                st.Code AS Code,
                DATE_FORMAT(st.Date, '%e %b %Y') AS Date,
                (IFNULL(st.Note,'  -')) AS Note,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                IFNULL(st.Quantity,0) AS Qty,
                IFNULL(st.StockCost,0) AS Amount,
                (IFNULL(st.Quantity,0) * IFNULL(st.StockCost,0)) AS Total
                              
                FROM trdtransactionstock st 
                LEFT OUTER JOIN mstwarehouse w ON st.Warehouse = w.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = st.Item
                LEFT OUTER JOIN company co ON co.Oid = st.Company
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                WHERE st.GCRecord IS NULL AND 1=1 AND 4=4 AND jt.Code IN ('STIN','Stock','STOUT')
                GROUP BY i.name, i.Code, st.Code, DATE_FORMAT(st.Date, '%Y%m%d')
                ";
                break;

                case 3:
                    return "SELECT
                co.Code AS Comp,
                CONCAT(w.Name, ' - ', w.Code) AS Warehouse,
                st.Code AS Code,
                DATE_FORMAT(st.Date, '%e %b %Y') AS Date,
                (IFNULL(st.Note,'  -')) AS Note,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                IFNULL(st.Quantity,0) AS Qty,
                IFNULL(i.SalesAmount,0) AS Amount,
                (IFNULL(st.Quantity,0) * IFNULL(i.SalesAmount,0)) AS Total
                                                
                FROM trdtransactionstock st 
                LEFT OUTER JOIN mstwarehouse w ON st.Warehouse = w.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = st.Item
                LEFT OUTER JOIN company co ON co.Oid = st.Company
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                WHERE st.GCRecord IS NULL AND 1=1 AND 4=4 AND jt.Code IN ('STIN','Stock','STOUT')
                GROUP BY i.name, i.Code, st.Code, DATE_FORMAT(st.Date, '%Y%m%d')
                ";
                break;
        }
        return "";
    }
}
