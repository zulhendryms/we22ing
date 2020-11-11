<?php

namespace App\Core\Travel\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


trait HasTravelAllotment
{
    public function scopeHasAllotment($query, $type, $dateFrom = null, $dateTo = null, $qty = 1)
    {
        if (is_null($dateFrom)) $dateFrom = now()->toDateString();
        if (is_null($dateTo)) $dateTo = Carbon::parse($dateFrom)->addDay(1)->toDateString();

        $period = Carbon::parse($dateFrom)->format('Ym');

        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        return $query->where(function ($q1) use ($type, $from, $to, $qty, $period) {
            $select1 = '';
            $select2 = '';
            $having = '';

            while($from->lt($to)) {
                $i = $from->day;
                if (!empty($having)) $having .= ' AND ';
                $having .= "SUM(IFNULL(t.Day{$i}, 0)) >= {$qty}";
                $select1 .= ", IFNULL(t.Day{$i}, 0) as Day{$i}";
                $select2 .= ", IFNULL(t.Day{$i}, 0) * -1 as Day{$i}";
                $from->addDay();
            }
            $q1->whereRaw("(SELECT m1.IsAllotment FROM mstitem m1 WHERE m1.Oid = mstitem.ParentOid) = FALSE OR (
                (SELECT m2.IsAllotment FROM mstitem m2 WHERE m2.Oid = mstitem.ParentOid) = TRUE AND EXISTS (
                    SELECT 1 FROM (
                        SELECT t.Item {$select1} FROM trvallotment t WHERE t.Period = '{$period}' AND t.TravelType = '{$type}'
                        UNION ALL
                        SELECT t.Item {$select2} FROM trvtransactionallotment t
                        WHERE t.Period = '{$period}' AND t.TravelType = '{$type}' AND t.GCRecord IS NULL
                      ) AS t WHERE t.Item = mstitem.Oid HAVING {$having}
                )
            )");

            // $q1->whereRaw('(SELECT m1.IsAllotment FROM mstitem m1 WHERE m1.Oid = mstitem.ParentOid) = FALSE')->orWhere(function ($q2) use ($from, $to, $qty, $period) {
            //     $q2->whereRaw('(SELECT m2.IsAllotment FROM mstitem m2 WHERE m2.Oid = mstitem.ParentOid) = TRUE')->whereExists(function ($q3) use ($from, $to, $qty, $period) {
                    // $having = '';
                    // for ($i = $from; $i < $to; $i++) {
                    //     if (!empty($having)) $having .= ' AND ';
                    //     $having .= "SUM(IFNULL(t.Day{$i}, 0) - IFNULL(t1.Day1, 0)) >= {$qty}";
                    // }
                    // $q3->select(DB::raw("1 FROM trvallotment t
                    // LEFT JOIN trvtransactionallotment t1 ON t.Oid = t1.TravelAllotment where t.Period = '{$period}' AND t.Item = mstitem.Oid
                    // HAVING ${having}"));

                    // for ($i = $from; $i < $to; $i++) {
                    //     if (!empty($having)) $having .= ' AND ';
                    //     $having .= "SUM(IFNULL(t.Day{$i}, 0) - IFNULL(t1.Day1, 0)) >= {$qty}";
                    // }

                //     $q3->select(DB::raw("1 FROM (
                //         SELECT FROM trvallotment t WHERE t.Item = ''
                //         UNION
                //         SELECT FROM trvtransactionallotment t INNER JOIN trvallotment 
                //     )"));
                // });
        //     });
        });
    }    

    public function hasAllotment($type, $dateFrom = null, $dateTo = null, $qty = 1)
    {
        if (is_null($dateFrom)) $dateFrom = now()->toDateString();
        if (is_null($dateTo)) $dateTo = Carbon::parse($dateFrom)->addDay(1)->toDateString();

        $period = Carbon::parse($dateFrom)->format('Ym');

        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        $having = '';
        $select1 = '';
        $select2 = '';

        while($from->lte($to)) { //BY EKA 20191008 diganti dari lt, dikarenakan perlu sameday
            $i = $from->day;
            if (!empty($having)) $having .= ' AND ';
            if (!empty($select1)) $select1 .= ', ';
            if (!empty($select2)) $select2 .= ', ';
            $having .= "SUM(IFNULL(t.Day{$i}, 0)) >= {$qty}";
            $select1 .= "IFNULL(t.Day{$i}, 0) as Day{$i}";
            $select2 .= "IFNULL(t.Day{$i}, 0) * -1 as Day{$i}";
            $from->addDay();
        }

        // for ($i = $from; $i < $to; $i++) {
        //     if (!empty($having)) $having .= ' AND ';
        //     if (!empty($select1)) $select1 .= ', ';
        //     if (!empty($select2)) $select2 .= ', ';
        //     $having .= "SUM(IFNULL(t.Day{$i}, 0)) >= {$qty}";
        //     $select1 .= "IFNULL(t.Day{$i}, 0) as Day{$i}";
        //     $select2 .= "IFNULL(t.Day{$i}, 0) * -1 as Day{$i}";
        // }

        $query = "SELECT 1 FROM (
            SELECT {$select1} FROM trvallotment t 
            WHERE t.Item = '{$this->Oid}' AND t.Period = '{$period}' AND t.TravelType = '{$type}'
            UNION ALL
            SELECT {$select2} FROM trvtransactionallotment t
            WHERE t.Item = '{$this->Oid}' AND t.Period = '{$period}' AND t.TravelType = '{$type}' AND t.GCRecord IS NULL
        ) as t
        HAVING {$having}";

        // try {
        //     $data = DB::select($query);
        // } catch (\Exception $e) {
        //     die(nl2br($query));
        // }
        $data = DB::select($query);

        return count($data) != 0;
    }
}