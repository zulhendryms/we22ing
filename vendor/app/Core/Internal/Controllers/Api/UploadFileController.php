<?php

namespace App\Core\Internal\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\Internal\Services\FileService;

class UploadFileController extends Controller 
{
    /** @var FileService $fileService */
    protected $fileService;

    /**
     * @param FileService $fileService
     * @return void
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function upload(Request $request)
    {
        return $this->fileService->upload($request->all());
    }
}