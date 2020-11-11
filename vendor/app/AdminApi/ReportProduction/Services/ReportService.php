<?php

namespace App\AdminApi\ReportProduction\Services;

use Carbon\Carbon;

use Illuminate\Support\Facades\Storage;

class ReportService
{
    protected $disk;
    protected $fileName;
    protected $fileUrl;

    public function __construct()
    {
        $this->disk = Storage::disk('report');
    }

    public function create($reportName, $pdf)
    {
        $this->generateReportName($reportName);
        $fullFileName = $this->getFileName();
        $this->putFile($fullFileName, $pdf->output());
        return $this;
    }

    public function getFileUrl()
    {
        return $this->fileUrl;
    }

    public function setFileUrl($filePath)
    {
        $this->fileUrl = $this->disk->url($filePath);
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function generateReportName($reportName)
    {
        $datetimeNow = Carbon::now();
        $fileName = $datetimeNow->format('Ymd_Hisu') . '_' . $reportName . '_' . str_random(10) . '.pdf';
        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath()
    {
        return $this->disk->getAdapter()->getPathPrefix() . $this->getFileName();
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function putFile($fileName, $file)
    {
        $this->disk->put($fileName, $file);
        $this->setFileUrl($fileName);
        try {
            chmod($this->getFilePath(), 0644);
        } catch (\Exception $e) {}
    }
}