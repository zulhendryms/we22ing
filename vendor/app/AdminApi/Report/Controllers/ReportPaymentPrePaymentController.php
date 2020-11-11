<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;

use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;



class ReportPaymentPrePaymentController extends Controller
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
        $this->reportName = 'payment-prereport';
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

    public function report(Request $request, $Oid = null)
    {
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;

        $query = $this->query('parent', $Oid);
        $data = DB::select($query);

        $query = $this->query('detail', $Oid);
        $dataDetail = DB::select($query);

        // return view('AdminApi\Report::pdf.ReportLOA',  compact('data', 'dataDetail'));
        $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.ReportLOA', compact('data', 'dataDetail'));

        $pdf
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('margin-right', 15);

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

    private function query($reportname, $Oid)
    {
        switch ($reportname) {
            case "parent":
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.Email AS Email,
                co.FullAddress AS CompanyAddress,
                ac.Code AS Code,
                ac.Note AS Note,
                a.Name AS AccountName,
                ac.CodeReff,
                ac.TotalAmount AS TotalAmount,
                co.PhoneNo, co.PhoneNumber,
                DATE_FORMAT(ac.Date, '%e %b %Y') AS Date,
                bp.Name AS BusinessPartner,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                pi.Code AS InvoiceCode,
                pic.Code AS CurrencyCodeInvoice,
                pi.CodeReff AS InvoiceCodeReff,
                acd.AmountInvoice AS AmountInvoice,
                acd.AmountCashBank AS AmountCashBank,
                b.AccountNo AS CardNumber, 
                b.AccountName AS CardName,
                ac.Note AS Remark,
                b.FullAddress AS CardAddress,
                b.BankSwiftCode AS TypeCard,
                b.DueDateCard AS DueDateCard,
                b.ImageSignature,
                b.Image1, b.Image2 
                FROM acccashbank ac 
                LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
                LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstwarehouse w ON ac.Warehouse = w.Oid
                LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                LEFT OUTER JOIN company co ON ac.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON ac.Status = s.Oid
                LEFT OUTER JOIN trdpurchaseinvoice pi ON pi.Oid = acd.PurchaseInvoice
                LEFT OUTER JOIN mstcurrency pic ON pic.Oid = pi.Currency
                LEFT OUTER JOIN mstbank b ON b.Oid = a.Bank
                WHERE ac.GCRecord IS NULL AND ac.Oid =  '" . $Oid . "'
                GROUP BY c.Code, DATE_FORMAT(ac.Date, '%Y%m%d')
                ";

            case "detail":
                return "SELECT
                tt.Code AS LOACode,
                bp.Name AS MerchantName,
                DATE_FORMAT(ttd.DateConfirm, '%d/%m/%y') AS DateLOA,
                ttd.code AS ConfirmNo, 
                tt.Code AS TourCode,
                ttd.Amount AS AmountLOA,
                tt.Remark AS Remark,
                tt.CodeReff AS FinanceRef
                FROM acccashbank ac
                LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
                LEFT OUTER JOIN trvtransactiondetail ttd ON acd.TravelTransactionDetail = ttd.Oid
                LEFT OUTER JOIN traveltransaction tt ON ttd.TravelTransaction = tt.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON ttd.BusinessPartner = bp.Oid
                WHERE ttd.Oid IS NOT NULL AND ac.GCRecord IS NULL AND ac.oid = '".$Oid."'
                ";
        }
        return " ";
    }
}
