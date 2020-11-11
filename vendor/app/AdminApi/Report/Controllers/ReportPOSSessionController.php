<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Entities\User;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportPOSSessionController extends Controller
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
        $this->reportName = 'possession';
        $this->reportService = $reportService;
    }

    public function view(Request $request, $reportName) {
        return response()
            ->file($this
                ->reportService
                ->setFileName($reportName)
                ->getFilePath())
            ->deleteFileAfterSend(true);
    }

    public function report(Request $request) {
        $reportname = $request->input('report');
        $user = Auth::user();

        $query = $this->query($reportname);
        $criteria1 = "";
        $filter="";
        
        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
        $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

        $criteria1 = $criteria1." AND DATE_FORMAT(p.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
        $criteria1 = $criteria1." AND DATE_FORMAT(p.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";

        $criteria1 = $criteria1 . reportQueryCompany('accjournal');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria1 = $criteria1 . " AND co.CompanySource = '" . $val->CompanySource . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('User')) {
            $val = User::findOrFail($request->input('User'));
            $criteria1 = $criteria1." AND p.User = '".$val->Oid."'";
            $filter = $filter."Account = '".strtoupper($val->Name)."'; ";
        }

        if ($filter) $filter = substr($filter,0);
        if ($criteria1) $query = str_replace(" AND 1=1",$criteria1,$query);
        
        logger($query);
        $data = DB::select($query);

        switch ($reportname) {
            case 'possession1':
                $reporttitle = "Report POS Session Detail";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'possession2':
                $reporttitle = "Report POS Session Summary";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
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

    private function query($reportname) {
        switch ($reportname) {
            case 'possession1':
                return 
                    "SELECT pos.Comp, pos.Type,
                    pos.Code,
                    pos.Date,
                    pos.PaymentMethod,
                    pos.PaymentAmount,
                    pos.Currency,
                    pos.PaymentBase
                    FROM
                    (SELECT co.Code AS Comp, 'Sales' AS Type,
                        p.Oid, 
                        p.Code, 
                        p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, 'Sales' AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount2,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase2,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod2 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod2 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, 'Sales' AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount3,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase3,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod3 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod3 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, 'Sales' AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount4,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase4,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod4 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod4 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, 'Sales' AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount5,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase5,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod5 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod5 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, 'Cash Trans.' AS Type, p.Oid, CONCAT(DATE_FORMAT(p.Date, '%Y-%m-%d'),' ',u.UserName) AS Code, p.Date, CONCAT(pm.Name, IFNULL(psat.Name,'')) AS PaymentMethod,
                        IFNULL(d.Amount,0) * CASE WHEN d.Type = 2 THEN -1 ELSE 1 END AS PaymentAmount, 
                        c.Code AS Currency, 
                        IFNULL(d.AmountBase,0) * CASE WHEN d.Type = 2 THEN -1 ELSE 1 END AS PaymentBase
                    FROM possessionamount d
                        LEFT OUTER JOIN possession p ON p.Oid = d.POSSession
                        LEFT OUTER JOIN mstpaymentmethod pm ON d.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN possessionamounttype psat ON d.POSSessionAmountType = psat.Oid
                        LEFT OUTER JOIN user u ON u.Oid = p.User
                        LEFT OUTER JOIN company co ON d.Company = co.Oid
                    WHERE 1=1) pos  
                    ORDER BY pos.Type, pos.Code, pos.Currency, pos.PaymentMethod
                    ";
                break;
            case 'possession2':
                return
                    "SELECT Comp,'POS' AS Type, PaymentMethod,
                        SUM(PaymentAmount) AS PaymentAmount,
                        Currency, SUM(PaymentBase) AS PaymentBase
                    FROM (
                    SELECT co.Code AS Comp, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount2,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase2,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod2 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod2 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount3,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase3,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod3 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod3 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount4,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase4,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod4 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod4 IS NOT NULL AND 1=1
                    UNION ALL
                    SELECT co.Code AS Comp, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount5,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase5,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod5 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod5 IS NOT NULL AND 1=1
                        ) AS Result
                    UNION ALL
                    SELECT co.Code AS Comp, 'Cash Trans.' AS Type, pm.Name AS PaymentMethod,
                        SUM(IFNULL(d.Amount,0)) AS PaymentAmount, c.Code AS Currency, SUM(IFNULL(d.AmountBase,0)) AS PaymentBase
                    FROM possessionamount d
                        LEFT OUTER JOIN possession p ON p.Oid = d.POSSession
                        LEFT OUTER JOIN mstpaymentmethod pm ON d.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN company co ON d.Company = co.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    WHERE 1=1
                    GROUP BY pm.Name, c.Code";
            break;
        }
        return "";
    }
}