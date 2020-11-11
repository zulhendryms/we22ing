<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Laravel\Http\Controllers\Controller;
use App\AdminApi\Report\Services\ReportService;
use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Core\Master\Entities\Company;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\ReportGeneratorController;


class ReportActivityListController extends Controller
{
    protected $reportService;
    protected $reportName;    
    private $reportGeneratorController;

    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'activitylist';
        $this->reportService = $reportService;
        $this->reportGeneratorController = new ReportGeneratorController();
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
        $user = Auth::user();

        // $reportname = $this->reportName;
        $reportname = $request->input('report');

        $query = $this->query($reportname);
        $criteria = "";
        $criteria2 = "";
        $criteria3 = "";
        $criteria4 = "";
        $filter = "";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $criteria = $criteria . " AND DATE_FORMAT(l.CreatedAtUTC, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
        $criteria = $criteria . " AND DATE_FORMAT(l.CreatedAtUTC, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";

        if ($filter) $filter = substr($filter, 0);
        if ($criteria) $query = str_replace(" AND 1=1", $criteria, $query);

        $data = DB::select($query);

        switch ($reportname) {
            case 'user_activity1':
                $reporttitle = "Activity List";
                // return view('AdminApi\Report::pdf.'.$reportname,  compact('data','reportname','reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.' . $reportname,  compact('data', 'reportname', 'reporttitle', 'user'));
                break;
            case 'user_activity2':
                $reporttitle = "Activity List";
                // return view('AdminApi\Report::pdf.'.$reportname,  compact('data','reportname','reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.' . $reportname,  compact('data', 'reportname', 'reporttitle', 'user'));
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
            ->setOption('page-width', '200')
            ->setOption('page-height', '297')
            ->setOption('margin-right', 15)
            ->setOption('margin-bottom', 10);

        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action') == 'dev') return view('AdminApi\Pub::pdf.'.$reportname, compact('data', 'reportname', 'reporttitle', 'user'));
        if ($request->input('action') == 'download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action')=='export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath),Response::HTTP_OK);
        if ($request->input('action')=='email') {
            $url = $this->reportGeneratorController->ReportActionExport($reportPath);
            $this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $url);
            return response()->json(null,Response::HTTP_OK);
        }
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

    private function query($reportname)
    {
        switch ($reportname) {
            case 'user_activity1':
                return "SELECT u.UserName, 
                u.Name, 
                DATE_FORMAT(MIN(l.CreatedAt), '%d-%b-%Y %h:%i:%s') DateFrom, 
                DATE_FORMAT(MAX(l.CreatedAt), '%d-%b-%Y %h:%i:%s') DateEnd, 
                DATE_FORMAT(l.CreatedAtUTC, '%W, %d %b %Y') Date,
                COUNT(l.Oid) AS Activities
                FROM mstlog l
                  LEFT OUTER JOIN user u ON u.Oid = l.CreatedBy
                  WHERE u.UserName IS NOT NULL AND 1=1
                GROUP BY DATE_FORMAT(l.CreatedAtUTC, '%Y-%m-%d'), u.UserName, u.Name;
                ";
                break;
            case 'user_activity2':
                return "SELECT u.UserName, 
                u.Name, 
                DATE_FORMAT(MIN(l.CreatedAt), '%d-%b-%Y %h:%i:%s') DateFrom, 
                DATE_FORMAT(MAX(l.CreatedAt), '%d-%b-%Y %h:%i:%s') DateEnd, 
                DATE_FORMAT(l.CreatedAtUTC, '%d %b %Y, %W') Date,
                COUNT(l.Oid) AS Activities
                FROM mstlog l
                  LEFT OUTER JOIN user u ON u.Oid = l.CreatedBy
                  WHERE u.UserName IS NOT NULL AND 1=1
                GROUP BY u.UserName, u.Name, DATE_FORMAT(l.CreatedAtUTC, '%Y-%m-%d');
                ";
                break;
        }
        return " ";
    }
}
