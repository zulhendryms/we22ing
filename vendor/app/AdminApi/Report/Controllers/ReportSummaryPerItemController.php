<?php

namespace App\AdminApi\Report\Controllers;

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

class ReportSummaryPerItemController extends Controller
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
        $this->reportName = 'fakturpossummaryperitem';
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
        $headertitle="SALES POS PER ITEM";
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $query = $this->query($reportname, $Session);
        
        logger($query);
        $data = DB::select($query);

        $query = $this->query("discount", $Session);
        $discount = DB::select($query);

        return view('AdminApi\Report::html.fakturpossummaryperitem', compact('data', 'headertitle','discount'));

        $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.fakturpossummaryperitem', compact('data'));
        
        $headerHtml = view('AdminApi\Report::pdf.headerfakturpos', compact('data','Date','headertitle'))
            ->render();
        $footerHtml = view('AdminApi\Report::pdf.footerfaktur')
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

    private function query($reportname, $Session) {
        switch ($reportname) {
            default:
                return "SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                IF(poses.Ended,date_format(poses.Ended, '%d/%m/%Y'),'Please End Session') AS Ended,
                u.Name AS Cashier,
                CONCAT(i.Name, ' - ', i.Code) AS Item,
                SUM(IFNULL(posd.Quantity,0)) AS Quantity,
                SUM(IFNULL(posd.Amount,0)) AS Amount,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS Subtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DiscountAmount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS TotalAmount,
                sts.Name AS StatusName
                FROM pospointofsale pos
                LEFT OUTER JOIN possession poses ON pos.POSSession = poses.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                WHERE pos.GCRecord IS NULL AND pos.POSSession = '".$Session."'
                GROUP BY i.Name, i.Code, u.Name, sts.Name";
            case "discount":
                return "SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                poses.Ended,
                u.Name AS Cashier,
                SUM(pos.DiscountAmount) + SUM(pos.DiscountPercentageAmount) AS Discount
                FROM pospointofsale pos
                LEFT OUTER JOIN possession poses ON pos.POSSession = poses.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                WHERE pos.GCRecord IS NULL AND pos.POSSession = '".$Session."'
                    GROUP BY u.Name ";
        }
        return " ";
    }

}