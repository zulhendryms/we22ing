<?php

namespace App\Core\Internal\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ERP\Master\Jobs\CreateLocaleContentFile;
use App\Core\Internal\Services\LocaleContentService;

class LocaleContentController extends Controller 
{
    protected $localeService;

    public function __construct(LocaleContentService $localeService)
    {
        $this->localeService = $localeService;
    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {
       $this->localeService->generate();
    }
}