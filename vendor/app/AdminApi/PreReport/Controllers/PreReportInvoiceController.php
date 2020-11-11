<?php

namespace App\AdminApi\PreReport\Controllers;

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



class PreReportInvoiceController extends Controller
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
        $this->reportName = 'invoice';
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
        $date = date_default_timezone_set("Asia/Jakarta");

        // $reportname = $this->reportName;
        $reportname = $request->has('report') ? $request->input('report') : 'acetoursinvoice';
        $Oid = $request->input('oid');

        $query = $this->query($reportname, $Oid);
        $data = DB::select($query);
        switch ($reportname) {
            case 'acetoursinvoice':
                $reporttitle = "Ace Tour Invoice";
                // return view('AdminApi\PreReport::pdf.invoice_'.$reportname, compact('data','user', 'reportname', 'filter', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.invoice_'.$reportname, compact('data','reportname','reporttitle'));
                break;
            case 'taxinvoice2':
                $reporttitle = "Tax Invoice";
                // return view('AdminApi\PreReport::pdf.invoice_'.$reportname, compact('data','user', 'reportname', 'filter', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.invoice_'.$reportname, compact('data','reportname'));
                break;
            }


        $pdf
            // ->setOption('header-html', $headerHtml)
            // ->setOption('footer-html', $footerHtml)
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('page-width', '200')
            ->setOption('page-height', '297')
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
            case 'acetoursinvoice':
                return "SELECT
                co.LogoPrint AS CompanyLogo,
                si.Code AS Code,
                si.Note AS Note,
                sid.Note AS NoteDetail,
                sid.Description AS Descriptions,
                si.BillingAddress,
                DATE_FORMAT(si.Date, '%e %b %Y') AS Date,
                si.TotalAmountWording,
                bp.Name AS Customer,
                bp.InvoiceAddress AS Address,
                bp.PhoneNumber AS PhoneNo,
                bp.FaxNumber AS FaxNo,
                bp.ContactPerson,
                pm.Name AS PaymentTerm,
                i.Name AS ItemName,
                IFNULL(sid.QtyAdult,0) AS QtyAdult,
                IFNULL(sid.QtyChild,0) AS QtyChild,
                IFNULL(sid.PriceAdult,0) AS PriceAdult,
                IFNULL(sid.PriceChild,0) AS PriceChild,
                IFNULL(sid.TotalAmount,0) AS TotalAmount,
                si.TotalAmountWording,
                cur.Code AS Cur,
                cur.Name AS CurName,
                b.AccountName AS AccountName,
                b.FullAddress AS bAddress,
                b.Name AS bName,
                b.AccountNo AS AccountNo,
                b.BankSwiftCode AS SwiftCode,
                b.Code AS bCode,
                b.BranchCode,
                tt.Code AS OurRef,
                si.CodeReff AS YourRef,
                p.Code AS TourCode,
                u.Name AS SalesPerson,
                si.AdditionalAmount,
                si.DiscountAmount,
                ac1.Name AS DiscountAccount,
                ac2.Name AS AdditionalAccount
                
                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetailtravel  sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstitem i ON sid.Item = i.Oid
                LEFT OUTER JOIN accaccount ac1 ON si.DiscountAccount = ac1.Oid 
                LEFT OUTER JOIN accaccount ac2 ON si.AdditionalAccount = ac2.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstpaymentterm pm ON si.PaymentTerm = pm.Oid
                LEFT OUTER JOIN mstcurrency cur ON si.Currency = cur.Oid
                LEFT OUTER JOIN mstwarehouse w ON si.Warehouse = w.Oid
                LEFT OUTER JOIN accaccount a ON si.AccountCashBank = a.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstemployee e ON si.Employee = e.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN traveltransaction tt ON si.PointOfSale = tt.Oid
                LEFT OUTER JOIN trvtransactiondetail ttd ON ttd.TravelTransaction = tt.Oid
                LEFT OUTER JOIN pospointofsale p ON si.PointOfSale = p.Oid
                LEFT OUTER JOIN user u ON u.Oid = si.CreatedBy
                WHERE si.GCRecord IS NULL AND (si.Oid = '" . $Oid . "' OR p.Oid = '" . $Oid . "')
                GROUP BY i.Name, i.Code, DATE_FORMAT(si.Date, '%Y%m%d'),si.Note, sid.QtyAdult, sid.QtyChild,sid.PriceAdult, sid.PriceChild
                ORDER BY IFNULL(sid.Sequence,sid.CreatedAt) ASC
                ";
            break;
            case 'taxinvoice2':
                return "SELECT
                co.LogoPrint AS CompanyLogo,
                si.Code AS Code,
                si.Note AS Note,
                si.BillingAddress,
                DATE_FORMAT(si.Date, '%e %b %Y') AS Date,
                bp.Name AS Customer,
                bp.InvoiceAddress AS Address,
                bp.PhoneNumber AS PhoneNo,
                bp.FaxNumber AS FaxNo,
                bp.ContactPerson,
                pm.Name AS PaymentTerm,
                i.Name AS ItemName,
                SUM(IFNULL(sid.Quantity,0)) AS Qty,
                SUM(IFNULL(sid.Price,0)) AS Amount,
                SUM((IFNULL(sid.Price,0) * IFNULL(sid.Quantity,0)) - IFNULL(sid.DiscountAmount,0) - IFNULL(sid.DiscountPercentage,0)) AS TotalAmount,
                  a.Name AS AccountName,
                  b.FullAddress AS bAddress,
                  b.Name AS bName,
                  b.AccountNo AS AccountNo,
                  b.BankSwiftCode AS SwiftCode,
                  b.Code AS bCode,
                  b.BranchCode,
                  ttd.Code AS OurRef,
                  si.CodeReff AS YourRef,
                  u.Name AS SalesPerson
                
                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetail  sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstitem i ON sid.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstpaymentterm pm ON si.PaymentTerm = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON si.Warehouse = w.Oid
                LEFT OUTER JOIN accaccount a ON si.Account = a.Oid
                LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
                LEFT OUTER JOIN mstemployee e ON si.Employee = e.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN traveltransaction tt ON si.TravelCommission = tt.Oid
                LEFT OUTER JOIN trvtransactiondetail ttd ON ttd.TravelTransaction = tt.Oid
                LEFT OUTER JOIN user u ON u.Oid = si.CreatedBy
                WHERE si.GCRecord IS NULL AND si.Oid =  '" . $Oid . "'
                GROUP BY i.Name, i.Code, DATE_FORMAT(si.Date, '%Y%m%d')
                ";
            break;
        }
        return " ";
    }
}
