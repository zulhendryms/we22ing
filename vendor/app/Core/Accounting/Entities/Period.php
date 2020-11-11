<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use Carbon\Carbon;
use App\Core\Base\Traits\BelongsToCompany;


class Period extends BaseModel {
    use BelongsToCompany;

    protected $table = 'accperiod';

    public function __get($key)
    {
        $period = parent::__get('DatePeriod');
        // $periodDate = Carbon::create(substr($period, 0, 4), substr($period, 4, 2));
        $periodDate = Carbon::create(substr($period, 0, 4), substr($period, 4, 2),1,0,0,0,'Asia/Jakarta');
        switch($key) {
            case "StartDate":
                return $periodDate->startOfMonth();
            case "EndDate":
                return $periodDate->endOfMonth();
            case "NextPeriodTxt":
                return $periodDate->addMonth(1)->format("Ym");
            case "NextPeriod":
                return $periodDate->addMonth(1);
        }
        return parent::__get($key);
    }
}