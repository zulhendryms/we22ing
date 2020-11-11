<?php

namespace App\Core\Accounting\Services;

use Carbon;
use App\Core\Accounting\Entities\Period;
use App\Core\Base\Exceptions\UserFriendlyException;

class JournalObjectService
{
    public function throwPeriodIsClosedError($period)
    {
        $period = $this->parsePeriod($period);
        throw new UserFriendlyException("Period {$period} is closed");
    }

    public function isPeriodClosed($period) 
    {
        $period = Period::where('DatePeriod', $this->parsePeriod($period))->first();
        if ($period) {
            if ($period->Status == 1) return true;
            return false;
        }
        throw new UserFriendlyException('Period not found');
    }

    private function parsePeriod($period)
    {
        if (strlen($period) > 6) return Carbon::parse($period)->format('Ym');
        return $period;
    }
}