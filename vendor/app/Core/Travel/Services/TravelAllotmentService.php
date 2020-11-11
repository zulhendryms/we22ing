<?php

namespace App\Core\Travel\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Travel\Entities\TravelAllotment;
use Carbon\Carbon;

class TravelAllotmentService
{
    public function create($param)
    {
        if (!isset($param['Code'])) {
            $param['Code'] = now()->format('Ymdhis').str_random(3);
        }
        $qty = $param['Quantity'] ?? 1;
        // if (isset($param['DateStart']) && isset($param['DateEnd'])) {
        //     $start = $param['DateStart'];
        //     $end = $param['DateEnd'];
        //     while ($start <= $end) {
        //         $param['Day'.$start] = $param['Quantity'];
        //         $start++;
        //     }
        // }
        $dateFrom = Carbon::parse($param['DateFrom']);
        $dateEnd = Carbon::parse($param['DateEnd']);

        unset($param['DateFrom']);
        unset($param['DateEnd']);
        unset($param['Quantity']);

        $params = [];
        $tmpParam = $param;

        $period = $dateFrom->format('Ym');

        while ($dateFrom->lte($dateEnd)) {
            $nextPeriod = $dateFrom->format('Ym');
            if ($period != $nextPeriod) {
                $params[] = $tmpParam;
                $tmpParam = $param;
                $period = $nextPeriod;
            }
            $tmpParam['Period'] = $nextPeriod;
            $tmpParam['Day'.$dateFrom->day] = $qty;
            $dateFrom->addDay();
        }
        $params[] = $tmpParam;
        foreach ($params as $p) {
            $allotment = TravelAllotment::create($p);
            $this->updateAllotmentCutoff($allotment);
        }
    }

    /**
     * @param TravelAllotment|string $allotment
     * @param int $qty
     */
    public function updateQty($allotment, $qty)
    {
        $allotment = $this->getAllotment($allotment);
        $param = [];
        for ($i = 1; $i <= 31; $i++) {
            if (!empty($allotment->{'Day'.$i})) {
                $param['Day'.$i] = $qty;
            }
        }
        return $allotment->update($param);
    }

    public function updateCutoff($allotment, $cutoff)
    {
        $allotment = $this->getAllotment($allotment);
        $allotment->CutoffDay = $cutoff;
        $allotment->save();
        $this->updateAllotmentCutoff($allotment);
    }

    public function updateAllotmentCutoff($allotment)
    {
        $allotment = $this->getAllotment($allotment);
        $period = $allotment->Period;
        $param = [];
        for ($i = 1; $i <= 31; $i++) {
            $param['Date'.$i] = null;
            if (!empty($allotment->{'Day'.$i})) {
                $param['Date'.$i] = Carbon::create(substr($period, 0 ,4), substr($period, 4, 2), $i)->subDays($allotment->CutoffDay)->toDateString();
            }
        }
        if (is_null($allotment->TravelAllotmentCutoffObj)) {
            $allotment->TravelAllotmentCutoffObj()->create($param);
            return;
        }
        $allotment->TravelAllotmentCutoffObj()->update($param);
    }

    private function getAllotment($allotment)
    {
        if (is_string($allotment)) return TravelAllotment::findOrFail($allotment);
        return $allotment;
    }
}