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

class PrereportCashBankSubmissionController extends Controller
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
        $this->reportName = 'prereport-cashbank-submission';
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

        $reportname = $request->has('report') ? $request->input('report') : 'cashbanksubmission';
        $Oid = $request->input('oid');
        
        $query = $this->query($reportname, $Oid);
        $data = DB::select($query);
        // dd($query);
        switch ($reportname) {
            case 'cashbanksubmission':
                    $reporttitle = "PreReport CashBank";
                    // return view('AdminApi\PreReport::pdf.prereportcashbank',  compact('data','reporttitle','reportname'));
                    $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_'. $reportname, compact('data', 'reporttitle', 'reportname'));
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
            case 'cashbanksubmission':
                return "SELECT
                ac.Oid,
                co.Image AS CompanyLogo,
                co.LogoPrint AS LogoPrint,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                d.Name AS Department,
                DATE_FORMAT(ac.DatePayment, '%d %M %Y') AS DatePayment,
                DATE_FORMAT(ac.Date, '%d %M %Y') AS Date,
                acd.Description AS `Description`,
                ac.Code AS Code,
                ac.TotalAmount,
                bp.Name AS BusinessPartner,
                c.Code AS CurrencyCode,
                c.Name AS CurrencyName,
                acd.Period AS Period,
                IFNULL(acd.InvoiceAmount,0) AS Amount,
                acd.InvoiceNote AS InvoiceNote,
                u.Name AS Receivedby,
                pt.Name AS PaymentTerm,
                pu.Name AS Requestor,
                u1.Name AS Approval1,u2.Name AS Approval2,u3.Name AS Approval3,
                DATE_FORMAT(ap1.ActionDate, '%e/%m/%y') AS Approval1Date,
                DATE_FORMAT(ap2.ActionDate, '%e/%m/%y') AS Approval2Date,
                DATE_FORMAT(ap3.ActionDate, '%e/%m/%y') AS Approval3Date,
                DATE_FORMAT(ap1.ActionDate, '%h:%i:%s') AS Approval1Hour,
                DATE_FORMAT(ap2.ActionDate, '%h:%i:%s') AS Approval2Hour,
                DATE_FORMAT(ap3.ActionDate, '%h:%i:%s') AS Approval3Hour
                
                FROM acccashbanksubmission ac 
                LEFT OUTER JOIN acccashbanksubmissiondetail acd ON ac.Oid = acd.CashBankSubmission
                LEFT OUTER JOIN mstbusinesspartner bp ON acd.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstpaymentterm pt ON bp.PaymentTerm = pt.Oid
                LEFT OUTER JOIN mstdepartment d ON ac.Department = d.Oid
                LEFT OUTER JOIN mstcurrency c ON ac.Currency = c.Oid
                LEFT OUTER JOIN company co ON ac.Company = co.Oid
                LEFT OUTER JOIN user u ON ac.CreatedBy = u.Oid
                LEFT OUTER JOIN pubapproval ap1 ON ap1.PublicPost = ac.Oid AND ap1.Sequence = 1
                LEFT OUTER JOIN pubapproval ap2 ON ap2.PublicPost = ac.Oid AND ap2.Sequence = 2
                LEFT OUTER JOIN pubapproval ap3 ON ap3.PublicPost = ac.Oid AND ap3.Sequence = 3
                LEFT OUTER JOIN user u1 ON ap1.User = u1.Oid
                LEFT OUTER JOIN user u2 ON ap2.User = u2.Oid
                LEFT OUTER JOIN user u3 ON ap3.User = u3.Oid
                LEFT OUTER JOIN user pu ON ac.Requestor = pu.Oid
                WHERE ac.GCRecord IS NULL AND acd.Submit=1 AND ac.Oid =  '" . $Oid . "'
                ORDER BY acd.Description, acd.Period ASC
                ";
        }
        return " ";
    }
}
