<?php

namespace App\AdminApi\PreReport\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Internal\Entities\AccountType;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class PreReportCashBankController extends Controller
{
    protected $reportService;
    protected $reportName;
    private $crudController;

    /**
     * @param ReportService $reportService
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'prereport-cashbank';
        $this->reportService = $reportService;
        $this->crudController = new CRUDDevelopmentController();
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

        $reportname = $request->has('report') ? $request->input('report') : 'cashbank';
        $Oid = $request->input('oid');
        $req = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        if ($req) {
            $reportname = $req->Report;
            $PaperSize = $req->PaperSize;
        }

        $query = $this->query($reportname, $Oid);
        $data = DB::select($query);
        switch ($reportname) {
            case 'cashbank':
                $reporttitle = "PreReport CashBank";
                // return view('AdminApi\PreReport::pdf.prereportcashbank',  compact('data','reporttitle','reportname'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_cashbank', compact('data', 'reporttitle', 'reportname','PaperSize'));
                break;
            case 'paymentrequest':
                $reporttitle = "PreReport Payment Request";
                // return view('AdminApi\PreReport::pdf.prereportpurchaseorder_'. $reportname, compact('data', 'reportname', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_cashbank_' . $reportname, compact('data', 'reportname', 'reporttitle','PaperSize'));
                break;
            case 'prereport':
                $reporttitle = "PreReport CashBank"; //BKK
                $dataReport = (object) [
                    "reporttitle" => 'PreReport CashBank',
                    "reportname" => $request->has('report') ? $request->input('report') : 'cashbank',
                ];
                $data = $this->crudController->detail('acccashbank', $Oid);
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_cashbank_1', compact('data', 'dataReport'));
                break;
            case 'receipt':
                $reporttitle = "Prereport Receipt";
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_cashbank_officialreceipt', compact('data', 'reportname', 'reporttitle'));
                break;
        }

        $headerHtml = view('AdminApi\PreReport::headfoot.prereport_header', compact('data', 'reportname', 'reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\PreReport::headfoot.prereport_footer', compact('data', 'reportname'))
            ->render();

        switch ($PaperSize) {
            case 'A4':
                // PRINT STANDARD
                $pdf
                    ->setOption('header-html', $headerHtml)
                    // ->setOption('footer-html', $footerHtml)
                    // ->setOption('footer-right', "Page [page] of [toPage]")
                    // ->setOption('footer-font-size', 5)
                    // ->setOption('footer-line', true)
                    // ->setOption('page-width', '215.9')
                    ->setOption('page-height', '297')
                    ->setOption('margin-right', 15)
                    ->setOption('margin-bottom', 10);
                break;
            case 'Half':
                // PRINT HALF-CONTINUOUS
                $pdf
                    ->setOption('header-html', $headerHtml)
                    ->setOption('footer-html', $footerHtml)
                    ->setOption('page-width', '210.4')
                    ->setOption('page-height', '135.9')
                    ->setOption('margin-right', 5)
                    ->setOption('margin-left', 5)
                    ->setOption('margin-bottom', 15);
                break;
            case 'Full':
                //PRINT FULL-CONTINUOUS
                $pdf
                    ->setOption('header-html', $headerHtml)
                    ->setOption('footer-html', $footerHtml)
                    // ->setOption('footer-right', "Page [page] of [toPage]")
                    // ->setOption('footer-font-size', 5)
                    // ->setOption('footer-line', true)
                    ->setOption('page-width', '215.9')
                    ->setOption('page-height', '265')
                    ->setOption('margin-left', 10)
                    ->setOption('margin-right', 10)
                    ->setOption('margin-bottom', 2);
                break;
            case 'receipt':
                $pdf
                    ->setOption('page-width', '250')
                    ->setOption('page-height', '150');
        }


        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action') == 'download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action') == 'export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath), Response::HTTP_OK);
        if ($request->input('action') == 'email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath), Response::HTTP_OK);
        if ($request->input('action') == 'post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath), Response::HTTP_OK);
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
            case 'cashbank':
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                ac.Code AS CashBankCode,
                CONCAT(IFNULL(pi.Code,''),IFNULL(si.Code,'')) AS InvoiceNumber,
                ac.CodeReff,
                CASE WHEN ac.Type=0 THEN 'Income' 
                WHEN ac.Type=1 THEN 'Expense' 
                WHEN ac.Type=2 THEN 'Receipt' 
                WHEN ac.Type=3 THEN 'Payment'
                WHEN ac.Type=4 THEN 'Transfer'
                END AS Type,
                a.Name AS AccountName,
                c.Code AS CurrencyCode,
                ac.TotalAmount,
                acc3.Name AS TransferAccount,
                ac.TransferAmount AS TransferAmount,
                ac.TransferRateBase AS TransferRateBase,
                c1.Code AS TransferCur,
                DATE_FORMAT(ac.Date, '%b %e %Y') AS Date,
                CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
                acd.AmountInvoice AS AmountInvoice,
                acd.AmountCashBank AS AmountCashBank,
                acc1.Name AS AdditionalAccount, 
                ac.AdditionalAmount, 
                acc2.Name AS DiscountAccount, 
                ac.DiscountAmount,
                ac.Note

                FROM acccashbank ac 
                LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
                LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
                LEFT OUTER JOIN accaccount acc1 ON ac.AdditionalAccount = acc1.Oid
                LEFT OUTER JOIN accaccount acc2 ON ac.DiscountAccount = acc2.Oid
                LEFT OUTER JOIN accaccount acc3 ON ac.TransferAccount = acc3.Oid
                LEFT OUTER JOIN trdpurchaseinvoice pi ON pi.Oid = acd.PurchaseInvoice
                LEFT OUTER JOIN trdsalesinvoice si ON si.Oid = acd.SalesInvoice
                LEFT OUTER JOIN mstcurrency c ON ac.Currency = c.Oid
                LEFT OUTER JOIN mstcurrency c1 ON ac.TransferCurrency = c1.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN company co ON ac.Company = co.Oid
                WHERE ac.GCRecord IS NULL AND ac.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(acd.Sequence,acd.CreatedAt) ASC
                ";
                break;
            case 'paymentrequest':
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                DATE_FORMAT(ac.Date, '%e %b %Y') AS Date,
                ac.RequestCode,
                DATE_FORMAT(ac.RequestDate, '%d %M %Y') AS RequestDate,
                ac.Code AS Code,
                ac.CodeReff AS CodeReff,
                m.Name AS Department,
                ac.Note AS Note,
                acd.Note AS Notedetail,
                c.Code AS Cur,
                cc.Name AS CostCenter,
                a.Name AS Account,
                aca.Name AS AccountCashBank,
                tra.Name AS TransferAccount,
                trc.Code AS TransferCur,
                ac.TransferAmount AS TransferAmount,
                ac.TransferRateBase AS TransferRateBase,
                ac.TotalAmount AS TotalAmount,
                acd.Description AS Description,
                acd.AmountCashBank AS Amount,
                u1.Name AS Approval1,u2.Name AS Approval2,u3.Name AS Approval3,
                DATE_FORMAT(ap1.ActionDate, '%e/%m/%y') AS Approval1Date,
                DATE_FORMAT(ap2.ActionDate, '%e/%m/%y') AS Approval2Date,
                DATE_FORMAT(ap3.ActionDate, '%e/%m/%y') AS Approval3Date,
                DATE_FORMAT(ap1.ActionDate, '%h:%i:%s') AS Approval1Hour,
                DATE_FORMAT(ap2.ActionDate, '%h:%i:%s') AS Approval2Hour,
                DATE_FORMAT(ap3.ActionDate, '%h:%i:%s') AS Approval3Hour,
                CASE WHEN ac.Type=0 THEN 'Income' 
                WHEN ac.Type=1 THEN 'Expense' 
                WHEN ac.Type=2 THEN 'Receipt' 
                WHEN ac.Type=3 THEN 'Payment'
                WHEN ac.Type=4 THEN 'Transfer'
                END AS Type,
                CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
                ap1.Note Approval1Note,ap2.Note Approval2Note,ap3.Note Approval3Note,
                e1.Name AS Requestor1,
                e2.Name AS Requestor2,
                ep1.Name AS ap1,ep2.Name AS ap2
                
                FROM acccashbank ac 
                LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
                LEFT OUTER JOIN accaccount aca ON ac.Account = aca.Oid
                LEFT OUTER JOIN accaccount a ON acd.Account = a.Oid
                LEFT OUTER JOIN accaccount tra ON ac.TransferAccount = tra.Oid
                LEFT OUTER JOIN mstcostcenter cc ON cc.Oid = acd.CostCenter
                LEFT OUTER JOIN mstdepartment m ON ac.Department = m.Oid
                LEFT OUTER JOIN company co ON ac.Company = co.Oid
                LEFT OUTER JOIN mstcurrency c ON ac.Currency = c.Oid
                LEFT OUTER JOIN mstcurrency trc ON ac.TransferCurrency = trc.Oid
                LEFT OUTER JOIN pubapproval ap1 ON ap1.PublicPost = ac.Oid AND ap1.Sequence = 1
                LEFT OUTER JOIN pubapproval ap2 ON ap2.PublicPost = ac.Oid AND ap2.Sequence = 2
                LEFT OUTER JOIN pubapproval ap3 ON ap3.PublicPost = ac.Oid AND ap3.Sequence = 3
                LEFT OUTER JOIN mstemployee e1 ON ac.Requestor1 = e1.Oid
                LEFT OUTER JOIN mstemployee e2 ON ac.Requestor2 = e2.Oid
                LEFT OUTER JOIN user u1 ON ap1.User = u1.Oid
                LEFT OUTER JOIN user u2 ON ap2.User = u2.Oid
                LEFT OUTER JOIN user u3 ON ap3.User = u3.Oid
                LEFT OUTER JOIN mstemployeeposition ep1 ON e1.EmployeePosition = ep1.Oid
                LEFT OUTER JOIN mstemployeeposition ep2 ON e2.EmployeePosition = ep2.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
                WHERE ac.GCRecord IS NULL AND ac.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(acd.Sequence,acd.CreatedAt) ASC
                ";
                break;
            case 'prereport':
                return "SELECT
                ac.Type,
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                bp.Name AS BusinessPartner,
                bp.FullAddress,
                p.Name AS Project,
                DATE_FORMAT(ac.Date, '%b %e %Y') AS Date,
                ac.Code AS CashBankCode,
                ac.CodeReff,
                ac.TotalAmount,
                ac.TotalAmountWording,
                ac.Note AS Note,
                si.Code AS InvoiceNumber,
                a.Name AS AccountName,
                c.Code AS CurrencyCode,
                c.Name AS CurrencyName,
                IFNULL(acd.AmountCashbank,0) AS Amount,
                IFNULL(acd.Rate,0) AS Rate, b.Name AS BankCode,
                acd.Description AS `Description`,
                u.Name AS Receivedby

                FROM acccashbank ac 
                LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
                LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
                LEFT OUTER JOIN trdsalesinvoice si ON si.Oid = acd.SalesInvoice
                LEFT OUTER JOIN mstcurrency c ON ac.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
                LEFT OUTER JOIN company co ON ac.Company = co.Oid
                LEFT OUTER JOIN user u ON ac.CreatedBy = u.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstproject p ON ac.Project = p.Oid
                WHERE ac.GCRecord IS NULL AND ac.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(acd.Sequence,acd.CreatedAt) ASC
                ";
                break;
            case 'receipt':
                return "SELECT
                ac.Type,
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                bp.Name AS BusinessPartner,
                bp.FullAddress,
                p.Name AS Project,
                DATE_FORMAT(ac.Date, '%b %e %Y') AS Date,
                ac.Code AS CashBankCode,
                ac.CodeReff,
                ac.TotalAmount,
                ac.TotalAmountWording,
                ac.Note AS Note,
                si.Code AS InvoiceNumber,
                a.Name AS AccountName,
                c.Code AS CurrencyCode,
                c.Name AS CurrencyName,
                IFNULL(acd.AmountCashbank,0) AS Amount,
                IFNULL(acd.Rate,0) AS Rate, b.Name AS BankCode,
                acd.Description AS `Description`,
                u.Name AS Receivedby

                FROM acccashbank ac 
                LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
                LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
                LEFT OUTER JOIN trdsalesinvoice si ON si.Oid = acd.SalesInvoice
                LEFT OUTER JOIN mstcurrency c ON ac.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
                LEFT OUTER JOIN company co ON ac.Company = co.Oid
                LEFT OUTER JOIN user u ON ac.CreatedBy = u.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstproject p ON ac.Project = p.Oid
                WHERE ac.GCRecord IS NULL AND ac.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(acd.Sequence,acd.CreatedAt) ASC
                ";
        }
        return " ";
    }
}
