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

class ReportDataProblemController extends Controller
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
        $this->reportName = 'dataproblem';
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
        $reportname = $request->input('report');
        $user = Auth::user();
        $query = $this->query($reportname);
        $data = DB::select($query);

        switch ($reportname) {
            case 'dataproblem1':
                $reporttitle = "Report Data Problem";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','reporttitle'));
            break;
        }

        $headerHtml = view('AdminApi\Report::pdf.headerdataproblem', compact('user', 'reportname', 'reporttitle'))
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
            default:
                return "SELECT 'Payment Method 1: IDR but Amount Zero' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                CONCAT(
                  'Payment1: ',IFNULL(pm.Name,''), 
                  '\nTotal: ',IFNULL(p.TotalAmount,0), 
                  '\nPayment: ',IFNULL(p.PaymentAmount,0), 
                  '\nRate: ',IFNULL(p.PaymentRate,0), 
                  '\nPaymentBase: ',IFNULL(p.PaymentAmountBase,0)
                  ) AS Description
                FROM pospointofsale p 
                LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                LEFT OUTER JOIN company co ON co.Oid = p.Company                
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                WHERE s.Code IN ('paid','posted','complete') AND p.TotalAmount != 0 AND c.Code = 'IDR'
                AND (IFNULL(p.PaymentAmount,0) = 0 OR IFNULL(p.PaymentRate,0) = 0 OR IFNULL(p.PaymentAmountBase,0) = 0) 
              
                UNION ALL
                
                SELECT 'Payment Method 1: NOT IDR but Amount Zero' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Payment1: ',IFNULL(pm.Name,''), 
                    '\nTotal: ',IFNULL(p.TotalAmount,0), 
                    '\nPayment: ',IFNULL(p.PaymentAmount,0), 
                    '\nRate: ',IFNULL(p.PaymentRate,0), 
                    '\nPaymentBase: ',IFNULL(p.PaymentAmountBase,0)
                    ) AS Description
                    FROM pospointofsale p 
                    LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN company co ON co.Oid = p.Company
                    LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                    WHERE s.Code IN ('paid','posted','complete') AND p.TotalAmount != 0 AND c.Code != 'IDR'
                    AND (IFNULL(p.PaymentAmount,0) = 0 OR IFNULL(p.PaymentRate,0) = 0 OR IFNULL(p.PaymentAmountBase,0) = 0) 
                
                UNION ALL
                
                SELECT 'Payment Method 2: Amount Zero' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Payment2: ',IFNULL(pm.Name,''), 
                    '\nTotal: ',IFNULL(p.TotalAmount,0), 
                    '\nPayment: ',IFNULL(p.PaymentAmount2,0), 
                    '\nRate: ',IFNULL(p.PaymentRate2,0), 
                    '\nPaymentBase: ',IFNULL(p.PaymentAmountBase2,0)
                    ) AS Description
                    FROM pospointofsale p 
                    LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN company co ON co.Oid = p.Company
                    LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                    WHERE s.Code IN ('paid','posted','complete') AND p.TotalAmount != 0 AND p.PaymentMethod2 IS NOT NULL
                    AND (IFNULL(p.PaymentAmount2,0) = 0 OR IFNULL(p.PaymentRate2,0) = 0 OR IFNULL(p.PaymentAmountBase2,0) = 0) 
                
                UNION ALL
                
                SELECT 'Payment Method 3: Amount Zero' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Payment3: ',IFNULL(pm.Name,''), 
                    '\nTotal: ',IFNULL(p.TotalAmount,0), 
                    '\nPayment: ',IFNULL(p.PaymentAmount3,0), 
                    '\nRate: ',IFNULL(p.PaymentRate3,0), 
                    '\nPaymentBase: ',IFNULL(p.PaymentAmountBase3,0)
                    ) AS Description
                    FROM pospointofsale p 
                    LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN company co ON co.Oid = p.Company                    
                    LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                    WHERE s.Code IN ('paid','posted','complete') AND  p.TotalAmount != 0 AND p.PaymentMethod3 IS NOT NULL
                    AND (IFNULL(p.PaymentAmount3,0) = 0 OR IFNULL(p.PaymentRate3,0) = 0 OR IFNULL(p.PaymentAmountBase3,0) = 0) 
                
                UNION ALL
                
                SELECT 'POS: versus Stock Posting Different' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Qty Order: ',SUM(IFNULL(pd.Quantity,0)), 'Qty Stock: ', SUM(IFNULL(st.Quantity,0))
                    ) AS Description
                    FROM pospointofsaledetail pd
                    LEFT OUTER JOIN pospointofsale p ON pd.PointOfSale = p.Oid
                    LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                    LEFT OUTER JOIN trdtransactionstock st ON p.Oid = st.PointOfSale
                    WHERE s.Code = 'paid' 
                    GROUP BY p.Oid, p.Code, p.Date
                    HAVING SUM(IFNULL(pd.Quantity,0)) != SUM(IFNULL(st.Quantity,0))                
                
                
                UNION ALL
                
                SELECT 'POS: Problem Calc Total' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Total Parent: ',IFNULL(p.TotalAmount,0), 'Calculated: ', p.SubtotalAmount - IFNULL(p.DiscountAmount,0) + IFNULL(p.DiscountPercentageAmount,0),
                    '\nPaymentBase: ',IFNULL(p.PaymentAmountBase,0)+IFNULL(p.PaymentAmountBase2,0)+IFNULL(p.PaymentAmountBase3,0)+IFNULL(p.PaymentAmountBase4,0)+IFNULL(p.PaymentAmountBase5,0)
                    ) AS Description
                    FROM pospointofsale p 
                    LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                    WHERE p.TotalAmount != p.SubtotalAmount - IFNULL(p.DiscountAmount,0) - IFNULL(p.DiscountPercentageAmount,0)
                
                UNION ALL
                
                SELECT 'POS: vs Payment Base Different' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Total Parent: ',IFNULL(p.TotalAmount,0), 'PaymentBase: ', IFNULL(p.PaymentAmountBase,0)+IFNULL(p.PaymentAmountBase2,0)+IFNULL(p.PaymentAmountBase3,0)+IFNULL(p.PaymentAmountBase4,0)+IFNULL(p.PaymentAmountBase5,0)
                    ) AS Description
                    FROM pospointofsale p 
                    LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                    WHERE s.Code IN ('paid','posted','complete') AND p.TotalAmount != (p.PaymentAmountBase + p.PaymentAmountBase2 + p.PaymentAmountBase3 + p.PaymentAmountBase4 + p.PaymentAmountBase5)
                
                UNION ALL                
                
                SELECT 'Purch Inv: Different Total' AS GroupName, 'PurchaseInvoice' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Total: ',IFNULL(p.TotalAmount,0), 
                    '\nCalculated Total: ', IFNULL(d.TotalDetail,0) + IFNULL(p.AdditionalAmount - p.DiscountAmount,0),
                    '\nJournal Total: ', IFNULL(j.TotalJournal,0)
                    ) AS Description
                FROM trdpurchaseinvoice p
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                LEFT OUTER JOIN (
                    SELECT d.PurchaseInvoice, SUM(IFNULL(d.Quantity,0) * IFNULL(d.Price,0)) TotalDetail 
                    FROM trdpurchaseinvoicedetail d
                ) d ON d.PurchaseInvoice = p.Oid
                LEFT OUTER JOIN (
                    SELECT j.PurchaseInvoice, SUM(IFNULL(j.DebetAmount,0) + IFNULL(j.CreditAmount,0)) TotalJournal
                    FROM accjournal j
                ) j ON j.PurchaseInvoice = p.Oid
                WHERE s.Code = 'posted' 
                AND (IFNULL(p.TotalAmount,0) != IFNULL(d.TotalDetail,0) + IFNULL(p.AdditionalAmount - p.DiscountAmount,0) 
                OR IFNULL(p.TotalAmount,0) != IFNULL(j.TotalJournal,0))
                
                UNION ALL

                SELECT 'POS: Different Total' AS GroupName, 'PurchaseInvoice' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'Total: ',IFNULL(p.TotalAmount,0), 
                    '\nCalculated Total: ', IFNULL(d.TotalDetail,0)-IFNULL(p.DiscountAmount,0)-IFNULL(p.DiscountPercentageAmount,0),
                    '\nJournal Total: ', IFNULL(j.TotalJournal,0)
                    ) AS Description
                FROM pospointofsale p
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                LEFT OUTER JOIN (
                    SELECT d.PointOfSale, SUM((IFNULL(d.Quantity,0) * IFNULL(d.Amount,0))-IFNULL(d.DiscountAmount,0)-IFNULL(d.DiscountPercentageAmount,0)) TotalDetail 
                    FROM pospointofsaledetail d
                ) d ON d.PointOfSale = p.Oid
                LEFT OUTER JOIN (
                    SELECT j.PointOfSale, SUM(IFNULL(j.DebetAmount,0) + IFNULL(j.CreditAmount,0)) TotalJournal
                    FROM accjournal j
                ) j ON j.PointOfSale = p.Oid
                WHERE s.Code = 'posted' 
                AND (IFNULL(p.TotalAmount,0) != IFNULL(d.TotalDetail,0)-IFNULL(p.DiscountAmount,0)-IFNULL(p.DiscountPercentageAmount,0)
                OR IFNULL(p.TotalAmount,0) != IFNULL(j.TotalJournal,0))

                UNION ALL

                SELECT 'POS not posted, but still have journal' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
                CONCAT(
                  'Total: ',IFNULL(p.TotalAmount,0), ' Status: ',s.Name
                  ) AS Description
                FROM accjournal j
                LEFT OUTER JOIN pospointofsale p ON j.PointOfSale = p.Oid
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                WHERE j.PointOfSale IS NOT NULL AND s.Code NOT IN ('paid','complete','posted')
                
                SELECT 'POS: Upload Failed' AS GroupName, 'PurchaseInvoice' AS TransactionType, p.Code, p.Date, 
                    CONCAT(
                    'TotalAmount: ',IFNULL(p.TotalAmount,0), ' Created: ', p.CreatedAt, ' User ',u.Name
                    ) AS Description
                FROM posupload p
                LEFT OUTER JOIN user u ON u.Oid = p.User
                WHERE p.UploadedAt IS NULL;

                SELECT * FROM posupload p LIMIT 10;";
        }
        return "";
    }
}


// UNION ALL
                
// SELECT 'POS: Problem Calc Subtotal' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
//     CONCAT(
//     'Subtotal Parent: ',IFNULL(p.SubtotalAmount,0), 'Calculated: ', SUM(IFNULL(pd.Subtotal,0))
//     ) AS Description
//     FROM pospointofsale p 
//     LEFT OUTER JOIN (
//         SELECT PointOfSale, SUM((IFNULL(p.Quantity,0)*IFNULL(p.Amount,0))-IFNULL(p.DiscountAmount,0)-IFNULL(p.DiscountPercentageAmount,0)) AS Subtotal 
//         FROM pospointofsaledetail p 
//         GROUP BY p.PointOfSale) pd ON p.Oid = pd.PointOfSale                    
//     LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
//     WHERE s.Code IN ('paid','posted','complete') AND p.SubtotalAmount != pd.Subtotal


// SELECT 'POS: Subtotal Problem 2' AS GroupName, 'POS' AS TransactionType, p.Code, p.Date, 
// CONCAT(
// 'Calculated Subtotal: ', SUM(pd.Quantity*pd.Amount), 
// '\nDisc Detail Sum: ', SUM(IFNULL(pd.DiscountAmount,0) + IFNULL(pd.DiscountPercentageAmount,0)), 
// '\nSubtotal Amt: ', p.SubtotalAmount, ' Discount ', IFNULL(p.DiscountAmount,0) + IFNULL(p.DiscountAmountBase,0),
// '\nCalculatedTotal: ', p.SubtotalAmount - IFNULL(p.DiscountAmount,0) + IFNULL(p.DiscountAmountBase,0),
// '\nPaymentBase: ', IFNULL(p.PaymentAmountBase,0)+IFNULL(p.PaymentAmountBase2,0)+IFNULL(p.PaymentAmountBase3,0)+IFNULL(p.PaymentAmountBase4,0)+IFNULL(p.PaymentAmountBase5,0)
// ) AS Description
// FROM pospointofsale p 
// LEFT OUTER JOIN pospointofsaledetail pd ON p.Oid = pd.PointOfSale
// LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
// WHERE s.Code IN ('paid','posted','complete') 
// GROUP BY p.SubtotalAmount, p.Code, p.Date
// HAVING p.SubtotalAmount != SUM(pd.Quantity*pd.Amount) - SUM(IFNULL(pd.DiscountAmount,0) + IFNULL(pd.DiscountPercentageAmount,0))

// UNION ALL
