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
use App\Core\Internal\Entities\PointOfSaleType;
use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportSummaryPerBillController extends Controller
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
        $this->reportName = 'fakturpossummaryperbill';
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
        $headertitle="SALES POS PER DAY";
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $query = $this->query("amount", $Session);
        $dataamount = DB::select($query);
         logger($dataamount);
        //  dd($query);
        //  die();
        $query = $this->query($reportname, $Session);
        $data = DB::select($query);

        return view('AdminApi\Report::html.fakturpossummaryperbill', compact('data', 'dataamount', 'headertitle'));

        // Below is old code (SnappyPdf)
        $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.fakturpossummaryperbill', compact('data','dataamount'));
        
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
        $return = PointOfSaleType::where('Code','SRETURN')->first()->Oid;
        switch ($reportname) {
            case "amount":
                return "SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                IF(poses.Ended,date_format(poses.Ended, '%d/%m/%Y'),'Please End Session') AS Ended,
                u.Name AS Cashier,
                pm.Name AS PaymentMethod,
                CASE WHEN posa.Type = 2 THEN posa.Amount * -1 ELSE posa.Amount END As Amount,
                CASE WHEN posa.Type = 2 THEN posa.AmountBase * -1 ELSE posa.AmountBase END As AmountBase,
                CASE WHEN posa.Type = 2 THEN 'Cash Out' ELSE 'Cash In' END As Type,
                posa.Date, 
                posa.Note AS Note
                FROM possession poses 
                LEFT OUTER JOIN possessionamount posa ON posa.POSSession = poses.Oid
                LEFT OUTER JOIN user u ON poses.User = u.Oid
                LEFT OUTER JOIN company c ON poses.Company = c.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON posa.PaymentMethod = pm.Oid
                LEFT OUTER JOIN possessionamountType posat ON posa.POSSessionAmountType = posat.Oid
                WHERE posa.GCRecord IS NULL AND posa.POSSession = '".$Session."'
                ORDER BY posa.CreatedAt";
            default:
                return "SELECT          
                    c.Image AS CompanyLogo,
                    c.Name AS CompanyName,
                    c.PhoneNo AS NoTlp,
                    IF(poses.Ended,date_format(poses.Ended, '%d/%m/%Y'),'Please End Session') AS Ended,
                    u.Name AS Cashier,
                    pos.Code,
                    pos.Date,
                    bp.Name AS Customer,
                    pt.Name AS TableRoom,
                    e.Name AS Employee,
                    e2.Name AS Employee2,
                    p.name AS Project,
                    pos.note AS Note,
                    pos.DiscountPercentageAmount + pos.DiscountAmount * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END AS DiscountAmount,
                    IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END AS TotalAmount,
                    pm1.Name AS PaymentMethod1,
                    pm2.Name AS PaymentMethod2,
                    pm3.Name AS PaymentMethod3,
                    pm4.Name AS PaymentMethod4,
                    pm5.Name AS PaymentMethod5,
                    pmc.Name AS PaymentMethodChanges,
                    pos.PaymentAmount * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END AS PaymentAmount1,
                    pos.PaymentAmount2 * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END AS PaymentAmount2,
                    pos.PaymentAmount3 * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END AS PaymentAmount3,
                    pos.PaymentAmount4 * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END AS PaymentAmount4,
                    pos.PaymentAmount5 * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END AS PaymentAmount5,
                    pos.ChangesAmount AS PaymentAmountChanges,
                    sts.Name AS StatusName
                    FROM pospointofsale pos
                    LEFT OUTER JOIN possession poses ON pos.POSSession = poses.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN mstemployee e2 ON pos.Employee2 = e2.Oid  
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN company c ON pos.Company = c.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm1 ON pos.PaymentMethod = pm1.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm2 ON pos.PaymentMethod2 = pm2.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm3 ON pos.PaymentMethod3 = pm3.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm4 ON pos.PaymentMethod4 = pm4.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm5 ON pos.PaymentMethod5 = pm5.Oid
                    LEFT OUTER JOIN mstpaymentmethod pmc ON pos.PaymentMethodChanges = pmc.Oid
                    LEFT OUTER JOIN syspointofsaletype pty ON pty.Oid = pos.PointOfSaleType
                    LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                    LEFT OUTER JOIN mstproject p ON pos.Project = p.Oid
                    WHERE pos.GCRecord IS NULL AND pos.POSSession = '".$Session."'
                    ORDER BY pos.CreatedAt";
        }
        return " ";
    }

}