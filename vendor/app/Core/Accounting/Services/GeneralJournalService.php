<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\JournalService;
use App\Core\Internal\Entities\Status;
use App\Core\Accounting\Entities\GeneralJournal;
use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\JournalType;
use Illuminate\Support\Facades\Auth;

class GeneralJournalService extends JournalObjectService
{
    /** @var JournalService $this->journalService */
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function post($id)
    {
        DB::transaction(function() use ($id) {
            $user = Auth::user();

            $data = GeneralJournal::findOrFail($id);
            $company = $data->CompanyObj;

            if ($this->isPeriodClosed($data->Date)) {
                $this->throwPeriodIsClosedError($data->Date);
            }

            GeneralJournal::where('Oid', $id)
            ->update([
                'Status' => Status::posted()->value('Oid'),
            ]);
        });
    }

    public function unpost($id)
    {
        DB::transaction(function() use ($id) {
            $apInvoice = GeneralJournal::findOrFail($id);
            if ($this->isPeriodClosed($apInvoice->Date)) {
                $this->throwPeriodIsClosedError($apInvoice->Date);
            }
            GeneralJournal::where('Oid', $id)
            ->update([
                'Status' => Status::entry()->value('Oid'),
            ]);
        });
    }

    public function cancelled($id)
    {
        DB::transaction(function() use ($id) {
            $apInvoice = GeneralJournal::findOrFail($id);
            if ($this->isPeriodClosed($apInvoice->Date)) {
                $this->throwPeriodIsClosedError($apInvoice->Date);
            }
            GeneralJournal::where('Oid', $id)
            ->update([
                'Status' => Status::cancelled()->value('Oid'),
            ]);
        });
    }
}