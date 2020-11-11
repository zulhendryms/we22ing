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
use App\Core\Internal\Entities\AccountType;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Trading\Entities\PurchaseInvoice;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;



class PreReportPurchaseInvoiceController extends Controller
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
        $this->reportName = 'purchaseinvoice-prereport';
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

        $reportname = $request->has('report') ? $request->input('report') : 'purchaseinvoice';
        $Oid = $request->input('oid');
        $req = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        if ($req) {
            $reportname = $req->Report;
            $PaperSize = $req->PaperSize;
        }

        $query = $this->query($reportname, $Oid);
        // dd($query);
        $data = DB::select($query);
        switch ($reportname) {
            case 'purchaseinvoice':
                $reporttitle = "PreReport Purchase Invoice";
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_purchaseinvoice', compact('data','PaperSize'));
                break;
            case 'invoicepayment':
                $reporttitle = "PreReport Purchase Invoice";

                $data1 = PurchaseInvoice::whereNull('GCRecord')->where('Oid', $Oid)->first();
                $data2 = CashBankDetail::whereNull('GCRecord')->where('PurchaseInvoice', $Oid)->get();

                $result = [];
                $result[] = (object) [
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
                    "DebetAmount" => 0,
                    "CreditAmount" => $data1->TotalAmount,
                    "Status" => 'Invoice',
                ];
                foreach ($data2 as $row) {
                    $result[] = (object) [
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
                        "DebetAmount" => $row->InvoiceAmount,
                        "CreditAmount" => 0,
                        "Status" => 'Payment',
                    ];
                }
                $data = $result;
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_purchaseinvoice_' . $reportname, compact('data', 'reportname', 'reporttitle'));
        }

        $headerHtml = view('AdminApi\PreReport::headfoot.prereport_header', compact('data', 'reportname', 'reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\PreReport::headfoot.prereport_footer', compact('reportname'))
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
                    ->setOption('margin-top', 40)
                    ->setOption('margin-bottom', 14);
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
            case 'purchaseinvoice':
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                pi.Code AS Code,
                pi.Note,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                CONCAT(bp.Name, ' - ', bp.Code) AS Supplier,
                bp.FullAddress AS SupplierAddress,
                bp.PhoneNumber AS SupplierPhone,
                pi.CodeReff AS CodeReff,
                pm.Name AS PaymentTerm,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                pid.Note AS itemNote,
                IFNULL(pid.Quantity,0) AS Qty,
                IFNULL(pid.Price,0) AS Amount,
                ((IFNULL(pid.Price,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0)) AS TotalAmount,
                pi.AdditionalAmount,
                pi.DiscountAmount
                
                FROM trdpurchaseinvoice pi 
                LEFT OUTER JOIN trdpurchaseinvoicedetail pid ON pi.Oid = pid.PurchaseInvoice
                LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                LEFT OUTER JOIN mstpaymentterm pm ON pi.PaymentTerm = pm.Oid
                LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                LEFT OUTER JOIN company co ON pi.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                WHERE pi.GCRecord IS NULL AND pi.Oid =  '" . $Oid . "'
                ORDER BY pid.Sequence
                ";
                break;
            case 'invoicepayment':
                return "SELECT * FROM trdpurchaseinvoice
                    ";
        }
        return " ";
    }
}
