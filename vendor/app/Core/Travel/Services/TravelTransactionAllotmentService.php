<?php

namespace App\Core\Travel\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Travel\Entities\TravelAllotment;
use App\Core\Master\Entities\Item;
use Carbon\Carbon;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\Travel\Exceptions\NoAllotmentException;
use App\Core\Travel\Entities\TravelTransactionAllotment;

class TravelTransactionAllotmentService
{
    private $allowMinusAllotment = false;

    /**
     * @return TravelTransactionAllotmentService
     */
    public function allowMinus($value) 
    {
        $this->allowMinusAllotment = $value;
        return $this;
    }

    /**
     * @param TravelTransactionDetail $detail
     * @param array $param
     */
    public function assignTransactionAllotment(TravelTransactionDetail $detail, $param = [])
    {
        $item = $detail->ItemObj;
        $parent = $item->ParentObj;
        if (is_null($parent) && !$item->IsAllotment || !is_null($parent) && !$parent->IsAllotment) return;
        if (!$item->ParentObj->IsAllotment) return;
        if ($detail->TravelTransactionObj->TravelTypeObj->Code == 'GIT') {
            $this->allowMinus(true);
        }
        $this->removeTransactionAllotment($detail);
        $this->assignAllotment([
            'Item' => $detail->Item,
            'From' => $detail->DateFrom,
            'To' => $detail->DateUntil,
            'TravelType' => $detail->TravelTransactionObj->TravelType,
            'Qty' => $item->ItemGroupObj->ItemTypeObj->Code == 'Hotel' ? $detail->Qty : $detail->Quantity,
            'TravelTransactionDetail' => $detail->Oid
        ]);
        return;

        // $item = $detail->ItemObj;
        // $parent = $item->ParentObj;
        // if (!$parent->IsAllotment) return;

        // $this->removeTransactionAllotment($detail); // Delete previous assignment

        // $from = $detail->DateFrom;
        // $to = $detail->DateUntil;
        // $qty = $detail->Quantity;

        // if ($item->ItemGroupObj->ItemTypeObj->Code == 'Hotel') {
        //     $qty = $detail->Qty;
        // }

        // if (!$item->hasAllotment($from, $to, $qty) && !$this->allowMinusAllotment) throw new NoAllotmentException("No allotment for item ".$item->Name);

        // $from = Carbon::parse($detail->DateFrom);
        // $to = Carbon::parse($detail->DateUntil);
        // $period = (clone $from)->format('Ym');

        // $param = array_merge($param, [
        //     'TravelTransactionDetail' => $detail->Oid,
        //     'Item' => $item->Oid,
        // ]);
        // $tempParam = $param;

        // while ($from->lt($to)) {
        //     $tempPeriod = (clone $from)->format('Ym');
        //     if ($tempPeriod != $period) {
        //         $tempParam['Period'] = $period;
        //         TravelTransactionAllotment::create($tempParam);
        //         $tempParam = $param;
        //         $period = $tempPeriod;
        //     }
        //     $tempParam['Day'. $from->day] = $qty;
        // }

        // TravelTransactionAllotment::create(array_merge($tempParam, [
        //     'Period' => $period
        // ]));

        // for ($i = $from; $i < $to; $i++) {
        //     // if (!empty($where)) $where .= ' AND ';
        //     // $where .= "(IFNULL(t.Day{$i}, 0) - IFNULL(t1.Day{$i}, 0)) >= {$qty}";
        //     $param['Day'.$i] = $qty;
        // }

        // $allotment = TravelAllotment::whereRaw("Oid = (SELECT t.Oid FROM trvallotment t 
        // LEFT JOIN trvtransactionallotment t1 ON t.Oid = t1.TravelAllotment AND t1.GCRecord IS NULL
        // WHERE t.Item = '{$item->Oid}' AND t.Period = '{$period}' AND {$where} LIMIT 1)")
        // ->first();

        // TravelTransactionAllotment::create(array_merge($param, [
        //     // 'TravelAllotment' => $allotment->Oid,
        //     'TravelTransactionDetail' => $detail->Oid,
        //     'Item' => $item->Oid,
        //     'Period' => $period
        // ]));
    }

    public function assignAllotment($param)
    {
        $from = Carbon::parse($param['From']);
        $to = Carbon::parse($param['To']);
        // if ($from->isSameDay($to)) return;
        $qty = $param['Qty'];
        $period = (clone $from)->format('Ym');

        $newParam = array_diff_key($param, array_flip([
            'From', 'To', 'Qty'
        ]));

        if (isset($newParam['Item'])) {
            $item = Item::where('Oid', $newParam['Item'])->whereNotNull('ParentOid')->firstOrFail();
            if (!$item->ParentObj->IsAllotment) return;
        }

        if (!isset($newParam['Code'])) {
            $newParam['Code'] = now()->format('Ymdhis').'-'.str_random(3);
        }

        $params = [];
        $tempParam = $newParam;
        $tmpFrom = (clone $from);
        while ($from->lte($to)) {
            $tempPeriod = (clone $from)->format('Ym');
            if ($tempPeriod != $period) {
                // TravelTransactionAllotment::create($tempParam);
                if (isset($item)) {
                    if (!($item)->hasAllotment(
                        $param['TravelType'],
                        $tmpFrom,
                        (clone $from),
                        $qty
                    ) && !$this->allowMinusAllotment) throw new NoAllotmentException("No allotment for item ".$item->Name);
                }
                $tmpFrom = (clone $from);
                $params[] = $tempParam;
                $tempParam = $newParam;
                $period = $tempPeriod;
            }
            $tempParam['Period'] = $period;
            $tempParam['Day'. $from->day] = $qty;
            $from->addDay(1);
        }
            $params[] = $tempParam;
        if (isset($item)) {
            if (!($item)->hasAllotment(
                $param['TravelType'],
                $tmpFrom,
                $to,
                $qty
            ) && !$this->allowMinusAllotment) throw new NoAllotmentException("No allotment for item ".$item->Name);
        }
        foreach ($params as $p) {
            TravelTransactionAllotment::create($p);
        }
    }

    /**
     * @param TravelTransactionDetail $detail
     */
    public function removeTransactionAllotment(TravelTransactionDetail $detail)
    {
        TravelTransactionAllotment::where('TravelTransactionDetail', $detail->Oid)->delete();
    }

    public function takeAllAllotment($item, $dateFrom, $dateTo, $type = null) 
    {
        $insertField = '';
        $select1Field = '';
        $select2Field = '';
        $select3Field = '';

        $period = (clone $dateFrom)->format('Ym');

        while($dateFrom->lt($dateTo)) {
            if (!empty($insertField)) $insertField .= ',';
            if (!empty($select1Field)) $select1Field .= ',';
            if (!empty($select2Field)) $select2Field .= ',';
            if (!empty($select3Field)) $select3Field .= ',';
            $i = $dateFrom->day;
            $insertField .= 'Day'.$i;
            $select1Field .= "SUM(t.Day{$i})";
            $select2Field .= "IFNULL(t.Day{$i}, 0) as Day{$i}";
            $select3Field .= "IFNULL(t.Day{$i}, 0) * -1 as Day{$i}";
            $dateFrom->addDay();
        }
        $company = company()->Oid;
        $query = "INSERT INTO trvtransactionallotment (Oid, {$insertField}, Item, Company, Period
        ".( !is_null($type) ? ", TravelType" : "" ).")
            SELECT UUID(), {$select1Field}, '{$item}', '{$company}', '{$period}'".
             (!is_null($type) ? ",'{$type}'" : "")." FROM (
                SELECT ${select2Field} FROM trvallotment t 
                WHERE t.Item = '{$item}' AND t.Period = '{$period}' 
                ".(!is_null($type) ?  "AND t.TravelType = '{$type}'" : "")."
                UNION ALL
                SELECT {$select3Field} FROM trvtransactionallotment t
                WHERE t.Item = '{$item}' AND t.Period = '{$period}' 
                ". (!is_null($type) ? "AND t.TravelType = '{$type}'" : "")." AND t.GCRecord IS NULL
            ) as t
        ";
        logger($query);
        DB::insert($query);
    }
}