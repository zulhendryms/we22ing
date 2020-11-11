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



class PreReportETicketController extends Controller
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
        $this->reportName = 'eticket';
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
        $reportname = $request->has('report') ? $request->input('report') : 'gardensbythebay';

        $query = $this->query($reportname, $Oid);
        $data = DB::select($query);


        switch ($reportname) {
            case 'gardensbythebay':
                $reporttitle = "gardensbythebay";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'artsciencemuseum':
                $reporttitle = "artsciencemuseum";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'marinabaysands':
                $reporttitle = "marinabaysands";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'universalstudios':
                $reporttitle = "universalstudios";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'seaaquarium':
                $reporttitle = "seaaquarium";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'adventurecovewaterpark':
                $reporttitle = "adventurecovewaterpark";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'hellokittytown':
                $reporttitle = "hellokittytown";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));
            case 'woda':
                $reporttitle = "woda";
            // return view('AdminApi\PreReport::pdf.eticket_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.eticket_' . $reportname, compact('data', 'reportname'));

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
            case 'woda':
                return "SELECT 
                m.Name,
                p.DateValidFrom,
                p.DateExpiry,
                m.DescOperatingHourID,
                p.Type,
                p.Code,
                m.DescRedemptionID,
                m.DescTermConditionID,
                p.Oid
                FROM poseticket p
                LEFT OUTER JOIN mstitem i ON p.Item = i.Oid
                LEFT OUTER JOIN mstitemcontent m ON i.ItemContent = m.Oid
                LEFT OUTER JOIN trdpurchaseinvoice pi ON p.PurchaseInvoice = pi.Oid
                WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                ";
                break;
            default:
                return "SELECT 
                m.Name,
                p.DateValidFrom,
                p.DateExpiry,
                m.DescOperatingHourID,
                p.Type,
                p.Code,
                m.DescRedemptionID,
                m.DescTermConditionID,
                p.Oid
                FROM poseticket p
                LEFT OUTER JOIN mstitem i ON p.Item = i.Oid
                LEFT OUTER JOIN mstitemcontent m ON i.ItemContent = m.Oid
                LEFT OUTER JOIN trdpurchaseinvoice pi ON p.PurchaseInvoice = pi.Oid
                WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                ";
                break;
        }
        return " ";
    }
}
