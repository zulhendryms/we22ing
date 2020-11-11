<?php

namespace App\Core\Travel\Console\Commands;

use Illuminate\Console\Command;
use App\Core\Travel\Entities\TravelType;
use App\Core\Master\Entities\Item;
use App\Core\Travel\Entities\TravelAllotmentCutoff;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckAllotmentCutoffDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:check-allotment-cutoff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check allotment';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $items = Item::where('IsAllotment', true)->get();
        $svc = new \App\Core\Travel\Services\TravelTransactionAllotmentService;
        // $travelTypes = TravelType::select('Oid')->get();
        // foreach ($items as $item) {
        //     foreach ($item->Details as $detail) {
        //         $cutoffday = $detail->TravelItemHotelObj->CutoffDay;
        //         if (empty($cutoffday)) continue;
        //         $cutoffdate = now()->subDays($cutoffday);
        //         foreach ($travelTypes as $travelType) {
        //             $svc->takeAllAllotment($detail->Oid, (clone $cutoffdate), (clone $cutoffdate)->addDay(1), $travelType->Oid);   
        //         }
        //     }
        // }
        $now = now();
        $svc = new \App\Core\Travel\Services\TravelTransactionAllotmentService;
        $where = '';
        for ($i = 1; $i <= 31; $i++) {
            if ($where != '') $where .= ' OR ';
            $where .= "DATE_FORMAT(Date{$i}, '%Y-%m-%d') = '{$now->toDateString()}'";
        }
        $cutoff = TravelAllotmentCutoff::whereRaw($where)
        ->with('TravelAllotmentObj')
        ->join('trvallotment', 'trvallotmentcutoff.TravelAllotment', '=', 'trvallotment.Oid')
        ->orderBy('trvallotment.CreatedAt')->get();
        
        foreach ($cutoff as $c) {
            $allotment = $c->TravelAllotmentObj;
            $day = 1;
            for ($i = 1; $i <= 31; $i++) {
                if (isset($c->{'Date'.$i}) && Carbon::parse($c->{'Date'.$i})->toDateString() == $now->toDateString()) {
                    $day = $i;
                    break;
                }
            }
            $allotmentLeft = $this->getAllotmentLeft($allotment, $day);
            $year = substr($allotment->Period, 0, 4);
            $month = substr($allotment->Period, 4, 2);
            $date = Carbon::create($year, $month, $day);
            if ($allotmentLeft > 0) {
                $svc->assignAllotment([
                    'Item' => $allotment->Item,
                    'From' => $date,
                    'To' => (clone $date)->addDay(1),
                    'TravelType' => $allotment->TravelType,
                    'Qty' => $allotmentLeft,
                    'Period' => $allotment->Period
                ]);
            }
        }
    }

    private function getAllotmentLeft($allotment, $day)
    {
        $item = $allotment->Item;
        $query = "SELECT IFNULL(SUM(IFNULL(t.Day{$day}, 0)), 0) - (SELECT IFNULL(SUM(IFNULL(t1.Day{$day}, 0)), 0)
            FROM trvtransactionallotment t1 
            WHERE t1.Item = '{$allotment->Item}' AND t1.Period = '{$allotment->Period}' AND t1.TravelType = '{$allotment->TravelType}') AS Value 
            FROM trvallotment t 
            WHERE t.Item = '{$allotment->Item}' AND 
            t.Period = '{$allotment->Period}' AND t.TravelType = '{$allotment->TravelType}' AND t.CreatedAt <= '{$allotment->CreatedAt}';";
        $data = DB::select($query);
        if (count($data) == 0) return 0;
        return $data[0]->Value;
    }
}
