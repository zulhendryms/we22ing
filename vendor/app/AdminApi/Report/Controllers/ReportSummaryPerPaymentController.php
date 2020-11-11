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

class ReportSummaryPerPaymentController extends Controller
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
        $this->reportName = 'fakturpossummaryperpayment';
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
        $headertitle="SALES POS PER PAYMENT";
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $query = $this->query($reportname, $Session);
        
        logger($query);
        $data = DB::select($query);

        return view('AdminApi\Report::html.fakturpossummaryperpayment', compact('data', 'headertitle'));

        $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.fakturpossummaryperpayment', compact('data'));
        
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
                salespos.CompanyLogo,
                salespos.CompanyName,
                salespos.NoTlp,
                IF(salespos.Ended,date_format(salespos.Ended, '%d/%m/%Y'),'Please End Session') AS Ended,
                salespos.Cashier,
                salespos.Item,
                salespos.CountBill,
                salespos.Currency,
                SUM(IFNULL(PaymentAmount,0)) AS PaymentAmount,
                SUM(IFNULL(PaymentAmountBase,0)) AS PaymentAmountBase, 
                SUM(IFNULL(PaymentRate,0)) AS PaymentRate 
                FROM 
                (SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                CONCAT(pm.Name, ' - ', pm.Code) AS Item,
                cur.Code AS Currency,
                COUNT(pos.Oid) AS CountBill,
                SUM(IFNULL(pos.PaymentAmount,0)) AS PaymentAmount,
                SUM(IFNULL(pos.PaymentAmountBase,0)) AS PaymentAmountBase,
                SUM(IFNULL(pos.PaymentAmountBase,0) / IFNULL(pos.PaymentAmount,0)) AS PaymentRate
                FROM pospointofsale pos
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                LEFT OUTER JOIN possession p ON p.Oid = pos.POSSession
                LEFT OUTER JOIN accaccount a ON a.Oid = pm.Account
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
                LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                WHERE pos.GCRecord IS NULL AND pos.PaymentAmount != 0 AND p.Oid = '".$Session."' AND
                sts.Name = 'PAID'
                GROUP BY pm.Name, u.Name, c.Code
                
                UNION ALL
                
                SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                CONCAT(pm.Name, ' - ', pm.Code) AS Item,
                cur.Code AS Currency,
                COUNT(pos.Oid) AS CountBill,
                SUM(IFNULL(pos.PaymentAmount2,0)) AS PaymentAmount,
                SUM(IFNULL(pos.PaymentAmountBase2,0)) AS PaymentAmountBase,
                SUM(IFNULL(pos.PaymentAmountBase2,0) / IFNULL(pos.PaymentAmount2,0)) AS PaymentRate
                FROM pospointofsale pos
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod2 = pm.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                LEFT OUTER JOIN possession p ON p.Oid = pos.POSSession
                LEFT OUTER JOIN accaccount a ON a.Oid = pm.Account
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
                LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                WHERE pos.GCRecord IS NULL AND pos.PaymentAmount2 != 0 AND p.Oid = '".$Session."' AND
                sts.Name = 'PAID'
                GROUP BY pm.Name, u.Name, c.Code
                
                UNION ALL
                
                SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                CONCAT(pm.Name, ' - ', pm.Code) AS Item,
                cur.Code AS Currency,
                COUNT(pos.Oid) AS CountBill,
                SUM(IFNULL(pos.PaymentAmount3,0)) AS PaymentAmount,
                SUM(IFNULL(pos.PaymentAmountBase3,0)) AS PaymentAmountBase,
                SUM(IFNULL(pos.PaymentAmountBase3,0) / IFNULL(pos.PaymentAmount3,0)) AS PaymentRate
                FROM pospointofsale pos
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod3 = pm.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                LEFT OUTER JOIN possession p ON p.Oid = pos.POSSession
                LEFT OUTER JOIN accaccount a ON a.Oid = pm.Account
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
                LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                WHERE pos.GCRecord IS NULL AND pos.PaymentAmount3 != 0 AND p.Oid = '".$Session."' AND
                sts.Name = 'PAID'
                GROUP BY pm.Name, u.Name, c.Code
                
                UNION ALL
                
                SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                CONCAT(pm.Name, ' - ', pm.Code) AS Item,
                cur.Code AS Currency,
                COUNT(pos.Oid) AS CountBill,
                SUM(IFNULL(pos.PaymentAmount4,0)) AS PaymentAmount,
                SUM(IFNULL(pos.PaymentAmountBase4,0)) AS PaymentAmountBase,
                SUM(IFNULL(pos.PaymentAmountBase4,0) / IFNULL(pos.PaymentAmount4,0)) AS PaymentRate
                FROM pospointofsale pos
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod4 = pm.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                LEFT OUTER JOIN possession p ON p.Oid = pos.POSSession
                LEFT OUTER JOIN accaccount a ON a.Oid = pm.Account
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
                LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                WHERE pos.GCRecord IS NULL AND pos.PaymentAmount4 != 0 AND p.Oid = '".$Session."' AND
                sts.Name = 'PAID'
                GROUP BY pm.Name, u.Name, c.Code
                
                UNION ALL
                
                SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                CONCAT(pm.Name, ' - ', pm.Code) AS Item,
                cur.Code AS Currency,
                COUNT(pos.Oid) AS CountBill,
                SUM(IFNULL(pos.PaymentAmount5,0)) AS PaymentAmount,
                SUM(IFNULL(pos.PaymentAmountBase5,0)) AS PaymentAmountBase,
                SUM(IFNULL(pos.PaymentAmountBase5,0) / IFNULL(pos.PaymentAmount5,0)) AS PaymentRate
                FROM pospointofsale pos
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod5 = pm.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN company c ON pos.Company = c.Oid
                LEFT OUTER JOIN possession p ON p.Oid = pos.POSSession
                LEFT OUTER JOIN accaccount a ON a.Oid = pm.Account
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
                LEFT OUTER JOIN sysstatus sts ON pos.Status = sts.Oid
                WHERE pos.GCRecord IS NULL AND pos.PaymentAmount5 != 0 AND p.Oid = '".$Session."' AND
                sts.Name = 'PAID'
                GROUP BY pm.Name, u.Name, c.Code
                
                UNION ALL
                
                SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                CONCAT(pm.Name, ' - ', pm.Code) AS Item,
                cur.Code AS Currency,
                0 AS CountBill,
                SUM(IFNULL(pa.Amount,0)) AS PaymentAmount,
                SUM(IFNULL(pa.AmountBase,0)) AS PaymentAmountBase,
                SUM(IFNULL(pa.AmountBase,0) / IFNULL(pa.Amount,0)) AS PaymentRate
                FROM possession p
                  LEFT OUTER JOIN possessionamount pa ON pa.POSSession = p.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pa.PaymentMethod = pm.Oid
                LEFT OUTER JOIN accaccount a ON a.Oid = pm.Account
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
                LEFT OUTER JOIN user u ON p.User = u.Oid
                LEFT OUTER JOIN company c ON p.Company = c.Oid
                WHERE p.GCRecord IS NULL AND p.Oid = '".$Session."' AND pa.Amount IS NOT NULL
                GROUP BY pm.Name, u.Name, c.Code
                
                UNION ALL
                
                SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                u.Name AS Cashier,
                p.Ended,
                'Saldo Awal' AS Item,
                cur.Code AS Currency,
                0 AS CountBill,
                p.Amount AS PaymentAmount,
                p.Amount AS PaymentAmountBase,
                1 AS PaymentRate
                FROM possession p
                LEFT OUTER JOIN user u ON p.User = u.Oid
                LEFT OUTER JOIN company c ON p.Company = c.Oid
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = c.Currency
                WHERE p.GCRecord IS NULL AND p.Oid = '".$Session."' ) salespos
                    GROUP BY
                    CompanyLogo,
                    CompanyName,
                    NoTlp,
                    Ended,
                    Cashier,
                    Currency,
                    Item";
        }
        return " ";
    }
}