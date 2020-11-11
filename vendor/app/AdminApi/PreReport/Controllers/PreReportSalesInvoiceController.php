<?php

namespace App\AdminApi\PreReport\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\AccountSection;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Trading\Entities\SalesInvoice;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;



class PreReportSalesInvoiceController extends Controller
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
        $this->reportName = 'prereport-salesinvoice';
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

        // $reportname = $this->reportName;
        $reportname = $request->has('report') ? $request->input('report') : 'salesinvoice';
        $Oid = $request->input('oid');

        $query = $this->query($reportname, $Oid);

        $data = DB::select($query);

        switch ($reportname) {
            case 'salesinvoice':
                $reporttitle = "PreReport Sales Invoice";
                // return view('AdminApi\PreReport::pdf.prereportsalesinvoice', compact('data','reporttitle','reportname'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_salesinvoice', compact('data', 'reporttitle', 'reportname'));
                $pdf
                    ->setOption('footer-right', "Page [page] of [toPage]")
                    ->setOption('footer-font-size', 5)
                    ->setOption('footer-line', true)
                    // ->setOption('page-width', '215.9')
                    ->setOption('page-height', '297')
                    ->setOption('margin-right', 15)
                    ->setOption('margin-bottom', 10);
                break;
            case 'invoicebilling':
                $reporttitle = "Prereport Billing";
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_salesinvoice_' . $reportname, compact('data', 'reportname', 'reporttitle'));
                $pdf->setOption('page-width', '250')
                    ->setOption('page-height', '150');
                break;
            case 'invoicebillingtravel':
                $reporttitle = "Prereport Billing";
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_salesinvoice_' . $reportname, compact('data', 'reportname', 'reporttitle'));
                $pdf->setOption('page-width', '250')
                    ->setOption('page-height', '150');
                break;
            case 'invoicereceipt':
                $reporttitle = "Prereport Invoice Receipt";
                $data1 = SalesInvoice::whereNull('GCRecord')->where('Oid', $Oid)->first();
                $data2 = CashBankDetail::whereNull('GCRecord')->where('SalesInvoice', $Oid)->get();

                $result = [];
                $result[] = (object)[
                    "Image" => $data1->CompanyObj->Image,
                    "CompanyName" => $data1->CompanyObj->Name,
                    "CompanyAddress" => $data1->CompanyObj->FullAddress,
                    "CompanyPhone" => $data1->CompanyObj->PhoneNo,
                    "CompanyEmail" => $data1->CompanyObj->Email,
                    "Oid" => $data1->Oid,
                    "Code" => $data1->Code,
                    "Date" => $data1->Date,
                    "BusinessPartner" => $data1->BusinessPartnerObj->Name,
                    "Account" => $data1->AccountObj->Name,
                    "Currency" => $data1->CurrencyObj->Code,
                    "DebetAmount" => $data1->TotalAmount,
                    "CreditAmount" => 0,
                    "Status" => 'Invoice',
                ];
                foreach ($data2 as $row) {
                    $result[] = (object)[
                        "Image" => $data1->CompanyObj->Image,
                        "CompanyName" => $data1->CompanyObj->Name,
                        "CompanyAddress" => $data1->CompanyObj->FullAddress,
                        "CompanyPhone" => $data1->CompanyObj->PhoneNo,
                        "CompanyEmail" => $data1->CompanyObj->Email,
                        "Oid" => $row->Oid,
                        "Code" => $row->CashBankObj->Code,
                        "Date" => $row->Date,
                        "BusinessPartner" => $row->CashBankObj->BusinessPartnerObj->Name,
                        "Account" => $row->AccountObj->Name,
                        "Currency" => $row->CurrencyObj->Code,
                        "DebetAmount" => 0,
                        "CreditAmount" => $row->InvoiceAmount,
                        "Status" => 'Receipt',
                    ];
                }
                $data = $result;
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_salesinvoice_' . $reportname, compact('data', 'reportname', 'reporttitle'));
                $pdf
                    ->setOption('footer-right', "Page [page] of [toPage]")
                    ->setOption('footer-font-size', 5)
                    ->setOption('footer-line', true)
                    // ->setOption('page-width', '215.9')
                    ->setOption('page-height', '297')
                    ->setOption('margin-right', 15)
                    ->setOption('margin-bottom', 10);
        }

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
            case 'salesinvoice':
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                  co.FullAddress AS CompanyAddress,
                  co.PhoneNo AS CompanyPhone,
                  co.Email AS CompanyEmail,
                si.Code AS Code,
                p.Code AS TourCode,
                si.Note AS Note,
                si.BillingAddress,
                DATE_FORMAT(si.Date, '%e %b %Y') AS Date,
                CONCAT(bp.Name, ' - ', bp.Code) AS Customer,
                  bp.FullAddress AS CustomerAddress,
                  bp.PhoneNumber AS CostumerPhone,
                  si.CodeReff AS CodeReff,
                  pm.Name AS PaymentTerm,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                SUM(IFNULL(sid.Quantity,0)) AS Qty,
                SUM(IFNULL(sid.Price,0)) AS Amount,
                SUM((IFNULL(sid.Price,0) * IFNULL(sid.Quantity,0)) - IFNULL(sid.DiscountAmount,0) - IFNULL(sid.DiscountPercentage,0)) AS TotalAmount,
                si.AdditionalAmount,
                si.DiscountAmount,
                ac1.Name AS DiscountAccount,
                ac2.Name AS AdditionalAccount
                
                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetail  sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN accaccount ac1 ON si.DiscountAccount = ac1.Oid 
                LEFT OUTER JOIN accaccount ac2 ON si.AdditionalAccount = ac2.Oid
                LEFT OUTER JOIN pospointofsale p ON si.PointOfSale = p.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON sid.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstpaymentterm pm ON si.PaymentTerm = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON si.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON si.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON si.Currency = c.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON si.Status = s.Oid
                WHERE si.GCRecord IS NULL AND si.Oid =  '" . $Oid . "'
                GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(si.Date, '%Y%m%d')
                ";
                break;
            case 'invoicebilling':
                return "SELECT
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                bp.Name AS BusinessPartner,
                bp.FullAddress,
                p.Name AS Project,
                DATE_FORMAT(si.Date, '%b %e %Y') AS Date,
                si.Code AS CashBankCode,
                si.CodeReff,
                si.TotalAmount,
                si.TotalAmountWording,
                si.Note AS Note,
                a.Name AS AccountName,
                c.Code AS CurrencyCode,
                sid.Price AS Amount,
                i.Name AS Item,
                sid.Quantity AS Qty,
                sid.Note AS Note,
                IFNULL(si.RateCommercial,0) AS Rate,
                b.Name AS BankCode,
                u.Name AS Receivedby

                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetail sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN mstitem i ON sid.Item = i.Oid
                LEFT OUTER JOIN accaccount a ON si.Account = a.Oid
                LEFT OUTER JOIN mstcurrency c ON si.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN user u ON si.CreatedBy = u.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstproject p ON si.Project = p.Oid
                WHERE si.GCRecord IS NULL AND si.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(sid.Sequence,sid.CreatedAt) ASC
                ";
                break;
            case 'invoicebillingtravel':
                return "SELECT
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                bp.Name AS BusinessPartner,
                bp.FullAddress,
                p.Name AS Project,
                DATE_FORMAT(si.Date, '%b %e %Y') AS Date,
                si.Code AS CashBankCode,
                si.CodeReff,
                si.TotalAmount,
                si.TotalAmountWording,
                si.Note AS Note,
                a.Name AS AccountName,
                c.Code AS CurrencyCode,
                c.Name AS CurrencyName,
                sid.Price AS Amount,
                IFNULL(si.Rate,0) AS Rate,
                b.Name AS BankCode,
                sid.Description AS `Description`,
                u.Name AS Receivedby

                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetailtravel sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN accaccount a ON si.Account = a.Oid
                LEFT OUTER JOIN mstcurrency c ON si.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN user u ON si.CreatedBy = u.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstproject p ON si.Project = p.Oid
                WHERE si.GCRecord IS NULL AND si.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(sid.Sequence,sid.CreatedAt) ASC
                ";
                break;
            case 'invoicereceipt':
                return "SELECT
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                bp.Name AS BusinessPartner,
                bp.FullAddress,
                p.Name AS Project,
                DATE_FORMAT(si.Date, '%b %e %Y') AS Date,
                si.Code AS CashBankCode,
                si.CodeReff,
                si.TotalAmount,
                si.TotalAmountWording,
                si.Note AS Note,
                a.Name AS AccountName,
                c.Code AS CurrencyCode,
                c.Name AS CurrencyName,
                sid.Price AS Amount,
                IFNULL(si.Rate,0) AS Rate,
                b.Name AS BankCode,
                sid.Description AS `Description`,
                u.Name AS Receivedby

                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetail sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN accaccount a ON si.Account = a.Oid
                LEFT OUTER JOIN mstcurrency c ON si.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN user u ON si.CreatedBy = u.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstproject p ON si.Project = p.Oid
                WHERE si.GCRecord IS NULL AND si.Oid =  '" . $Oid . "'
                ORDER BY IFNULL(sid.Sequence,sid.CreatedAt) ASC
                ;";
        }
        return " ";
    }
}
