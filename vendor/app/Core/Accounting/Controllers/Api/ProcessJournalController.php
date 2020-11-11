<?php

namespace App\Core\Accounting\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\Accounting\Services\CashBankService;
use App\Core\Accounting\Services\ProcessJournalService;


class ProcessJournalController extends Controller 
{
    /** @var ProcessJournalService $processJournalService */
    protected $processJournalService;

    /**
     * @param ProcessJournalService $processJournalService
     * @return void
     */
    public function __construct(ProcessJournalService $processJournalService)
    {
        $this->processJournalService = $processJournalService;
    }

    public function open(Request $request, $id) 
    {
        $this->processJournalService->open($id);
    }

    public function close(Request $request, $id)
    {
        $this->processJournalService->close($id);
    }

    public function calculate(Request $request, $id)
    {
        $this->processJournalService->process($id);
    }
}