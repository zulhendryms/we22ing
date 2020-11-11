<?php

namespace App\AdminApi\Travel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Base\Exceptions\UserFriendlyException;
use App\Core\Security\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Item;
use App\Core\Travel\Services\TravelAllotmentService;
use App\Core\Travel\Entities\TravelAllotment;
use App\Core\Travel\Services\TravelTransactionAllotmentService;
use Carbon\Carbon;
use App\Core\Travel\Entities\TravelType;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\Internal\Entities\Status;
use App\Core\Travel\Entities\TravelTransactionAllotment;
use App\Core\Travel\Services\TravelTransactionService;

class AllotmentController extends Controller 
{
    /** @var AuthService $authService */
    protected $authService;
    /** @var TravelAllotmentService $allotmentService */
    protected $allotmentService;
    /** @var TravelTransactionAllotmentService $allotmentTransactionService */
    protected $allotmentTransactionService;
    /** @var TravelTransactionService $travelTransactionService */
    protected $travelTransactionService;

    /**
     * @param AuthService $authService
     * @return void
     */
    public function __construct(
        AuthService $authService,
        TravelAllotmentService $allotmentService,
        TravelTransactionAllotmentService $allotmentTransactionService,
        TravelTransactionService $travelTransactionService
    )
    {
        $this->authService = $authService;
        $this->allotmentService = $allotmentService;
        $this->allotmentTransactionService = $allotmentTransactionService;
        $this->travelTransactionService = $travelTransactionService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) { return response()->json('Invalid User', Response::HTTP_NOT_FOUND); }
        // $permission = 'Travel.TravelAllotment';
        // if (!$user->allowNavigate($permission)) throw new UserFriendlyException("You're not allowed to access this content");
       
        // $start = now()->startOfMonth();
        // $end = now()->addYear(2);
        // $periods = [];
        // while($start->lte($end)) {
        //     $periods[] = $start->format('Ym');
        //     $start->addMonth(1);
        // }
        // if ($request->query('item')) {
        //     $item = Item::whereNull('ParentOid')->findOrFail($request->query('item'));
        // }
        // $type = $request->query('type');
        // $travelTypes = TravelType::all();
        // $allowAdd = $user->allowAdd($permission);
        // $allowEdit = $user->allowEdit($permission);
        // $allowDelete = $user->allowDelete($permission);
        // return view('Travel\Admin::allotment', compact('periods', 'item', 'travelTypes', 'type', 'allowAdd', 'allowEdit', 'allowDelete')); 

        $item = $request->query('item');
        $period = $request->query('period');
        $type = $request->query('type');

        // $query = "SELECT m.Oid, m.Subtitle as Description %s
        // FROM trvallotment t 
        // LEFT JOIN trvtransactionallotment t1 ON t1.TravelAllotment = t.Oid
        // INNER JOIN mstitem m ON t.Item = m.Oid
        // WHERE m.ParentOid = ? AND t.Period = ? GROUP BY m.Oid;";

        $allotments = $this->getAllotments($period, $item, $type);
        $nextAllotments = $this->getAllotments(
            Carbon::create(substr($period, 0, 4), intval(substr($period, 4, 2)), 1)->addMonth()->format('Ym'), 
            $item, 
            $type,
            5
        );
        $data = $this->mergeAllotmentsData($allotments, $nextAllotments);
        // $html = $this->getTransactionsHTML($data, $period);
        return $data;
    }

    private function getAllotments($period, $item, $type, $day = 31)
    {
        $criteria = '';
        $user = Auth::user();
        if ($user->BusinessPartner) $criteria = " AND m.PurchaseBusinessPartner = '".$user->BusinessPartner."' ";
        $query = "SELECT t.Oid, t.Description %s FROM (
            SELECT m.Oid, m.Subtitle AS Description %s 
                FROM trvallotment t 
                INNER JOIN mstitem m ON t.Item = m.Oid
                WHERE m.ParentOid = ? 
                AND t.Period = ?
                AND t.TravelType = ?
                {$criteria}
            UNION ALL
            SELECT m.Oid, m.Subtitle AS Description %s 
                FROM trvtransactionallotment t 
                INNER JOIN mstitem m ON t.Item = m.Oid 
                WHERE m.ParentOid = ? 
                AND t.Period = ? 
                AND t.TravelType = ? 
                AND t.GCRecord IS NULL
                {$criteria}
            ) AS t GROUP BY t.Oid";

        $select = '';
        $select1 = '';
        $select2 = '';
        for ($i = 1; $i <= $day; $i++) {
            $select .= ", SUM(IFNULL(t.Day{$i}, 0)) as Day{$i}";
            $select1 .= ", IFNULL(t.Day{$i}, 0) as Day{$i}";
            $select2 .= ", IFNULL(t.Day{$i}, 0) * -1 as Day{$i}";
        }
        $query = sprintf($query, $select, $select1, $select2);
        return DB::select($query, [ $item, $period, $type, $item, $period, $type ]);
    }

    private function getAllotments2($period, $item, $type, $day = 31)
    {
        $query = "SELECT t.Oid, t.Description %s,'Total' as `Code` FROM (
            SELECT m.Oid, m.Subtitle AS Description %s 
                FROM trvallotment t 
                INNER JOIN mstitem m ON t.Item = m.Oid
                WHERE t.Item = ? 
                AND t.Period = ?
                AND t.TravelType = ?
            UNION ALL
            SELECT m.Oid, m.Subtitle AS Description %s 
                FROM trvtransactionallotment t 
                INNER JOIN mstitem m ON t.Item = m.Oid 
                WHERE t.Item = ? 
                AND t.Period = ? 
                AND t.TravelType = ? 
                AND t.GCRecord IS NULL
            ) AS t GROUP BY t.Oid";

        $select = '';
        $select1 = '';
        $select2 = '';
        for ($i = 1; $i <= $day; $i++) {
            $select .= ", SUM(IFNULL(t.Day{$i}, 0)) as Day{$i}";
            $select1 .= ", IFNULL(t.Day{$i}, 0) as Day{$i}";
            $select2 .= ", IFNULL(t.Day{$i}, 0) * -1 as Day{$i}";
        }
        $query = sprintf($query, $select, $select1, $select2);
        return DB::select($query, [ $item, $period, $type, $item, $period, $type ]);
    }

    private function returnArrayFromAllotment($data) {
        $index = 1;
        $temp = [];
        foreach($data as $d => $value) {
            if ($d === 'Day'.$index) {
                $temp[$index] = $value;
                $index++;
            }
        }
        return $temp;
    }

    private function returnArrayEmptyFromAllotment($data) {
        $index = 1;
        $temp = [];
        foreach($data as $d => $value) {
            if ($d === 'Day'.$index) {
                $temp[$index] = 0;
                $index++;
            }
        }
        return $temp;
    }

    private function mergeAllotmentsData($data1, $data2)
    {
        $data = [];
        foreach ($data1 as $d) {
            if (empty($d->Oid)) continue;
            // TODO: WS 20190820 belum tau cara duplikasi utk 2 periode
            $d->Details = $this->returnArrayFromAllotment($d);
            $d2 = (clone $d);
            $d2->Details = $this->returnArrayEmptyFromAllotment($d2);
            $d2->Day1 = 0;
            $d2->Day2 = 0;
            $d2->Day3 = 0;
            $d2->Day4 = 0;
            $d2->Day5 = 0;
            $d2->Day6 = 0;
            $d2->Day7 = 0;
            $d2->Day8 = 0;
            $d2->Day9 = 0;
            $d2->Day10 = 0;
            $d2->Day11 = 0;
            $d2->Day12 = 0;
            $d2->Day13 = 0;
            $d2->Day14 = 0;
            $d2->Day15 = 0;
            $d2->Day16 = 0;
            $d2->Day17 = 0;
            $d2->Day18 = 0;
            $d2->Day19 = 0;
            $d2->Day20 = 0;
            $d2->Day21 = 0;
            $d2->Day22 = 0;
            $d2->Day23 = 0;
            $d2->Day24 = 0;
            $d2->Day25 = 0;
            $d2->Day26 = 0;
            $d2->Day27 = 0;
            $d2->Day28 = 0;
            $d2->Day29 = 0;
            $d2->Day30 = 0;
            $d2->Day31 = 0;
            $data[$d->Oid] = [ $d, $d2 ];
            // $data[$d->Oid] = [ $d ];
        }
        foreach ($data2 as $d) {
            if (empty($d->Oid)) continue;
            $d->Details = $this->returnArrayFromAllotment($d);
            if (isset($data[$d->Oid])) {
                $data[$d->Oid][1] = $d;
                continue;
            }
            $data[$d->Oid] = [ (object)[ 'Oid' => $d->Oid, 'Description' => $d->Description ], $d ];
        }
        return $data;
    }

    private function getAllotmentsHTML($data)
    {
        $html = '';
        foreach ($data as $d) {
            $d1 = $d[0];
            $d2 = $d[1];
            $html .= "<tr style='cursor:pointer' data-id='{$d1->Oid}'><td>{$d1->Description}</td>";
            for ($i = 1; $i <= 31; $i++) {
                $value = $d1->{'Day'.$i} ?? 0;
                $html .= "<td data-day='{$i}'>{$value}</td>";
            }
            for ($i = 1; $i <= 5; $i++) {
                $value = $d2->{'Day'.$i} ?? 0;
                $html .= "<td data-day='{$i}'>{$value}</td>";
            }
            $html .= '</tr>';
        }
        return $html;
    }

    public function getTransactions(Request $request)
    {
        $period = $request->query('period');
        $type = $request->query('type');
        $data = $this->getTransactionsData($period, $type);
        $nextData = $this->getTransactionsData(
            Carbon::create(substr($period, 0, 4), substr($period, 4, 2))->addMonth()->format('Ym'),
            $type
        );
        $data = $this->mergeAllotmentsData($data, $nextData);

        return $data;
    }

    private function getTransactionsData($period, $type, $day = 31)
    {
        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);

        $select1 = '';
        $select2 = '';
        $select3 = '';

        for ($i = 1; $i <= $day; $i++) {
            if (!empty($select1)) $select1 .= ", ";
            if (!empty($select2)) $select2 .= ", ";
            if (!empty($select3)) $select3 .= ", ";
            $select1 .= "SUM(IFNULL(t.Day{$i}, 0)) as Day{$i}";
            // $select2 .= "SUM(IF('{$year}-{$month}-{$i}' BETWEEN t.DateFrom AND t.DateUntil,IFNULL(t.Qty, 0), 0)) as Day{$i}";
            $select2 .= "SUM(IF('{$year}-{$month}-{($i)}' >= DATE(t.DateFrom) AND '{$year}-{$month}-{$i}' < DATE(t.DateUntil), IFNULL(t.Qty, 0), 0)) as Day{$i}";
            $select3 .= "SUM(IFNULL(t.Day{$i}, 0)) * -1  as Day{$i}";
        }

        // $query = "SELECT t.Oid, t.Description, {$select1} FROM (
        //     SELECT t.Oid, CONCAT(p.Code, '-', m1.Name, ' ', m.Subtitle) as Description, {$select2} FROM trvtransactiondetail t 
        //       INNER JOIN traveltransaction t1 ON t.TravelTransaction = t1.Oid
        //       INNER JOIN pospointofsale p ON p.Oid = t1.Oid
        //       INNER JOIN mstitem m ON t.Item = m.Oid
        //       INNER JOIN mstitem m1 ON m1.Oid = m.ParentOid
        //       WHERE m.ParentOid IS NOT NULL
        //       AND ( DATE_FORMAT(t.DateFrom, '%Y%m') = '{$period}' OR DATE_FORMAT(t.DateUntil, '%Y%m') = '{$period}' )
        //       AND ( m.IsAllotment = 1 OR m1.IsAllotment = 1 )
        //       AND t1.TravelType = '{$type}' GROUP BY t1.Oid  ) AS t GROUP BY t.Oid, t.Description;";

        $query = "SELECT t.Oid, t.Description, {$select1} FROM (
            SELECT t.Oid, CONCAT(p.Code, '-', m1.Name, ' ', m.Subtitle) as Description, {$select2} FROM trvtransactiondetail t 
            INNER JOIN traveltransaction t1 ON t.TravelTransaction = t1.Oid
            INNER JOIN pospointofsale p ON p.Oid = t1.Oid
            INNER JOIN mstitem m ON t.Item = m.Oid
            INNER JOIN mstitem m1 ON m1.Oid = m.ParentOid
            WHERE m.ParentOid IS NOT NULL 
            AND ( DATE_FORMAT(t.DateFrom, '%Y%m') = '{$period}' OR DATE_FORMAT(t.DateUntil, '%Y%m') = '{$period}' )
            AND ( m.IsAllotment = 1 OR m1.IsAllotment = 1 )
            AND t1.TravelType = '{$type}' GROUP BY t.Oid
            UNION ALL
            SELECT t1.Oid, CONCAT(p.Code, '-', m1.Name, ' ', m.Subtitle) as Description, {$select3} FROM trvtransactionallotment t
            INNER JOIN trvtransactiondetail t1 ON t.TravelTransactionDetail = t1.Oid
            INNER JOIN traveltransaction t2 ON t2.Oid = t1.TravelTransaction
            INNER JOIN pospointofsale p ON p.Oid = t2.Oid
            INNER JOIN mstitem m ON t1.Item = m.Oid
            INNER JOIN mstitem m1 ON m1.Oid = m.ParentOid
            AND t2.TravelType = '{$type}'
            AND ( t.Period = '{$period}' )
            AND m.ParentOid IS NOT NULL
            AND ( m.IsAllotment = 1 OR m1.IsAllotment = 1 ) GROUP BY t1.Oid
            ) AS t WHERE t.Day1 > 0 OR t.Day2 > 0 OR t.Day3 > 0 OR t.Day4 > 0 OR t.Day5 > 0 OR t.Day6 > 0 OR t.Day7 > 0 OR t.Day8 > 0 OR t.Day9 > 0 OR t.Day10 > 0 
            OR t.Day11 > 0 OR t.Day12 > 0 OR t.Day13 > 0 OR t.Day14 > 0 OR t.Day15 > 0 OR t.Day16 > 0 OR t.Day17 > 0 OR t.Day18 > 0 OR t.Day19 > 0 OR t.Day20 > 0 OR t.Day21 > 0
            OR t.Day22 > 0 OR t.Day23 > 0 OR t.Day24 > 0 OR t.Day25 > 0 OR t.Day26 > 0 OR t.Day27 > 0 OR t.Day28 > 0 OR t.Day29 > 0 OR t.Day30 > 0 OR t.Day31 > 0
            GROUP BY t.Oid, t.Description;";

        return DB::select($query);
    }

    private function getTransactionsHTML($data, $period)
    {
        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);
        $nextPeriod = Carbon::create($year, $month)->addMonth();
        $nextYear = $nextPeriod->year;
        $nextMonth = $nextPeriod->month;
        $html = '';
        foreach ($data as $d) {
            $d1 = $d[0];
            $d2 = $d[1];
            $html .= "<tr style='cursor:pointer' data-id='{$d1->Oid}'><td>{$d1->Description}</td>";
            for ($i = 1; $i <= 31; $i++) {
                $value = $d1->{'Day'.$i} ?? 0;
                $date = "{$year}-{$month}-{$i}";
                $html .= "<td data-day='{$i}' data-date='{$date}'>{$value}</td>";
            }
            for ($i = 1; $i <= 5; $i++) {
                $value = $d2->{'Day'.$i} ?? 0;
                $date = "{$nextYear}-{$nextMonth}-{$i}";
                $html .= "<td data-day='{$i}' data-date='{$date}'>{$value}</td>";
            }
            $html .= '</tr>';
        }
        return $html;
    }

    public function assignAllotment(Request $request)
    {
        $request = object_to_array(json_decode($request->getContent()));
        
        $from = Carbon::parse($request['DateFrom']);
        $until = Carbon::parse($request['DateUntil']);
        $item = Item::findOrFail($request['Item']);
        $detail = TravelTransactionDetail::findOrFail($request['TravelTransactionDetail']);
        $qty = $request['Quantity'];
        $type = $request['TravelType'];
        // if ($detail->Item != $item->Oid) {
            // $transAllotment = TravelTransactionAllotment::where('TravelTransactionDetail', $detail->Oid)->first();
            // if (isset($transAllotment)) {
            //     throw new UserFriendlyException("Transaction is already linked with allotment {$transAllotment->Code}");
            // } else {
                // $this->allotmentTransactionService->removeTransactionAllotment($detail);
                $detail->ItemObj()->associate($item);
                $detail->save();
                $this->travelTransactionService->calculateDetailAmount($detail);
                $this->travelTransactionService->calculateAmount($detail->PointOfSaleObj);
            // }
        // } 

        $this->allotmentTransactionService->assignAllotment([
            'From' => $from,
            'To' => $until,
            'Qty' => $qty,
            'Item' => $item->Oid,
            'TravelType' => $type,
            'TravelTransactionDetail' => $detail->Oid
        ]);

        $data = [ 'item' => $item->ParentOid, 'period' => $from->format('Ym'), 'type' => $type ];

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }



    public function items(Request $request)
    {
        $name = $request->query('name');
        $parent = $request->query('parent');

        $data = Item::query();

        if (!empty($name)) {
            $data->where('Name', 'LIKE', "%{$name}%")->take(10);
        } else {
            $data->take(10);
        }

        $user = Auth::user();
        if ($user->BusinessPartner) $data = $data->where('ItemObj.PurchaseBusinessPartner', $user->BusinessPartner);

        if (empty($parent)) {
            $data->has('POSItemServiceObj')
            ->where('IsAllotment', true)
            ->where('IsParent', true)
            ->select('Oid', 'Name');
        } else {
            $data->where('ItemContent', $parent)
            ->select('Oid', 'Subtitle as Name');
        }

        $data = $data->get();

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function show(Request $request, $id)
    {
        // IF(t1.TravelTransactionDetail IS NULL, 1, 0) as CanDelete,  DIHAPUS KARENA UTK HAPUS SET DAN TAKE MEMANG PERBOLEHKAN DELETE
        $adminUrl = config('app.admin_url');
        $query = "SELECT t.Oid, t.Code, t.CreatedAt {{s1}}, t.CutoffDay, 'TravelAllotment' as `Table`, 1 as CanEdit, 1 as CanDelete, null as URL FROM trvallotment t 
        WHERE t.Item = '{$id}' AND t.Period = '{$request->input('period')}' AND t.TravelType = '{$request->input('type')}'
        UNION
        SELECT t1.Oid, IF(p.Code IS NULL, t1.Code, CONCAT(p.Code,' ', DATE_FORMAT(t2.DateFrom, '%m/%d'),'-',  DATE_FORMAT(t2.DateUntil, '%m/%d'))) as Code, t1.CreatedAt {{s2}}, 0 as CutoffDay, 'TravelTransactionAllotment' as `Table`, 0 as CanEdit, 
        1 as CanDelete, 
        IF(t1.TravelTransactionDetail IS NULL, NULL, CONCAT('{$adminUrl}/#ViewID=TravelTransaction_DetailView&ObjectKey=', t2.TravelTransaction,'&ObjectClassName=Cloud_ERP.Module.BusinessObjects.Travel.TravelTransaction&mode=Edit')) as URL 
        -- NULL as URL
        FROM trvtransactionallotment t1 
        LEFT JOIN trvtransactiondetail t2 ON t1.TravelTransactionDetail = t2.Oid
        LEFT JOIN pospointofsale p ON t2.TravelTransaction = p.Oid
        WHERE t1.Item = '{$id}' AND t1.Period = '{$request->input('period')}' AND t1.TravelType = '{$request->input('type')}' AND t1.GCRecord IS NULL ORDER BY CreatedAt";

        $select1 = '';
        $select2 = '';
        for ($i = 1; $i <= 31; $i++) {
            // $select1 .= ", SUM(IFNULL(t.Day{$i}, 0)) as Day{$i}";
            $select1 .= ", IFNULL(t.Day{$i}, 0) as Day{$i}";
            $select2 .= ", IFNULL(t1.Day{$i}, 0) * -1 as Day{$i}";
        }
        
        // $query = sprintf($query, $select1, $select2);
        $query = str_replace('{{s1}}', $select1, $query);
        $query = str_replace('{{s2}}', $select2, $query);
        $data = DB::select($query);

        $item = $id;
        $period = $request->query('period');
        $type = $request->query('type');

        $allotments = $this->getAllotments2($period, $item, $type);
        foreach ($allotments as $rowallotment) {
            $rowallotment->Details = [
                '1' => $rowallotment->Day1,
                '2' => $rowallotment->Day2,
                '3' => $rowallotment->Day3,
                '4' => $rowallotment->Day4,
                '5' => $rowallotment->Day5,
                '6' => $rowallotment->Day6,
                '7' => $rowallotment->Day7,
                '8' => $rowallotment->Day8,
                '9' => $rowallotment->Day9,
                '10' => $rowallotment->Day10,
                '11' => $rowallotment->Day11,
                '12' => $rowallotment->Day12,
                '13' => $rowallotment->Day13,
                '14' => $rowallotment->Day14,
                '15' => $rowallotment->Day15,
                '16' => $rowallotment->Day16,
                '17' => $rowallotment->Day17,
                '18' => $rowallotment->Day18,
                '19' => $rowallotment->Day19,
                '20' => $rowallotment->Day20,
                '21' => $rowallotment->Day21,
                '22' => $rowallotment->Day22,
                '23' => $rowallotment->Day23,
                '24' => $rowallotment->Day24,
                '25' => $rowallotment->Day25,
                '26' => $rowallotment->Day26,
                '27' => $rowallotment->Day27,
                '28' => $rowallotment->Day28,
                '29' => $rowallotment->Day29,
                '30' => $rowallotment->Day30,
                '31' => $rowallotment->Day31,
            ];
            unset($rowallotment->Day1);
            unset($rowallotment->Day2);
            unset($rowallotment->Day3);
            unset($rowallotment->Day4);
            unset($rowallotment->Day5);
            unset($rowallotment->Day6);
            unset($rowallotment->Day7);
            unset($rowallotment->Day8);
            unset($rowallotment->Day9);
            unset($rowallotment->Day10);
            unset($rowallotment->Day11);
            unset($rowallotment->Day12);
            unset($rowallotment->Day13);
            unset($rowallotment->Day14);
            unset($rowallotment->Day15);
            unset($rowallotment->Day16);
            unset($rowallotment->Day17);
            unset($rowallotment->Day18);
            unset($rowallotment->Day19);
            unset($rowallotment->Day20);
            unset($rowallotment->Day21);
            unset($rowallotment->Day22);
            unset($rowallotment->Day23);
            unset($rowallotment->Day24);
            unset($rowallotment->Day25);
            unset($rowallotment->Day26);
            unset($rowallotment->Day27);
            unset($rowallotment->Day28);
            unset($rowallotment->Day29);
            unset($rowallotment->Day30);
            unset($rowallotment->Day31);
        }

        foreach ($data as $row) {
            $row->Details = [
                '1' => $row->Day1,
                '2' => $row->Day2,
                '3' => $row->Day3,
                '4' => $row->Day4,
                '5' => $row->Day5,
                '6' => $row->Day6,
                '7' => $row->Day7,
                '8' => $row->Day8,
                '9' => $row->Day9,
                '10' => $row->Day10,
                '11' => $row->Day11,
                '12' => $row->Day12,
                '13' => $row->Day13,
                '14' => $row->Day14,
                '15' => $row->Day15,
                '16' => $row->Day16,
                '17' => $row->Day17,
                '18' => $row->Day18,
                '19' => $row->Day19,
                '20' => $row->Day20,
                '21' => $row->Day21,
                '22' => $row->Day22,
                '23' => $row->Day23,
                '24' => $row->Day24,
                '25' => $row->Day25,
                '26' => $row->Day26,
                '27' => $row->Day27,
                '28' => $row->Day28,
                '29' => $row->Day29,
                '30' => $row->Day30,
                '31' => $row->Day31,
            ];
            unset($row->Day1);
            unset($row->Day2);
            unset($row->Day3);
            unset($row->Day4);
            unset($row->Day5);
            unset($row->Day6);
            unset($row->Day7);
            unset($row->Day8);
            unset($row->Day9);
            unset($row->Day10);
            unset($row->Day11);
            unset($row->Day12);
            unset($row->Day13);
            unset($row->Day14);
            unset($row->Day15);
            unset($row->Day16);
            unset($row->Day17);
            unset($row->Day18);
            unset($row->Day19);
            unset($row->Day20);
            unset($row->Day21);
            unset($row->Day22);
            unset($row->Day23);
            unset($row->Day24);
            unset($row->Day25);
            unset($row->Day26);
            unset($row->Day27);
            unset($row->Day28);
            unset($row->Day29);
            unset($row->Day30);
            unset($row->Day31);
        }

        $result = array_merge($data,$allotments);

        return response()->json(
            $result,
            Response::HTTP_OK
        );
    }

    public function viewTransaction(Request $request, $id)
    {
        $data = TravelTransactionAllotment::where('TravelTransactionDetail', $id)->where('Period', $request->input('period'))->where('TravelType', $request->input('type'))->with('ItemObj')->get();
       
        foreach ($data as $row) {
            $row->CanEdit = 0;
            $row->CanDelete = 1;
            $row->Details = [
                '1' => $row->Day1 * -1,
                '2' => $row->Day2 * -1,
                '3' => $row->Day3 * -1,
                '4' => $row->Day4 * -1,
                '5' => $row->Day5 * -1,
                '6' => $row->Day6 * -1,
                '7' => $row->Day7 * -1,
                '8' => $row->Day8 * -1,
                '9' => $row->Day9 * -1,
                '10' => $row->Day10 * -1,
                '11' => $row->Day11 * -1,
                '12' => $row->Day12 * -1,
                '13' => $row->Day13 * -1,
                '14' => $row->Day14 * -1,
                '15' => $row->Day15 * -1,
                '16' => $row->Day16 * -1,
                '17' => $row->Day17 * -1,
                '18' => $row->Day18 * -1,
                '19' => $row->Day19 * -1,
                '20' => $row->Day20 * -1,
                '21' => $row->Day21 * -1,
                '22' => $row->Day22 * -1,
                '23' => $row->Day23 * -1,
                '24' => $row->Day24 * -1,
                '25' => $row->Day25 * -1,
                '26' => $row->Day26 * -1,
                '27' => $row->Day27 * -1,
                '28' => $row->Day28 * -1,
                '29' => $row->Day29 * -1,
                '30' => $row->Day30 * -1,
                '31' => $row->Day31 * -1,
            ];
            unset($row->Day1);
            unset($row->Day2);
            unset($row->Day3);
            unset($row->Day4);
            unset($row->Day5);
            unset($row->Day6);
            unset($row->Day7);
            unset($row->Day8);
            unset($row->Day9);
            unset($row->Day10);
            unset($row->Day11);
            unset($row->Day12);
            unset($row->Day13);
            unset($row->Day14);
            unset($row->Day15);
            unset($row->Day16);
            unset($row->Day17);
            unset($row->Day18);
            unset($row->Day19);
            unset($row->Day20);
            unset($row->Day21);
            unset($row->Day22);
            unset($row->Day23);
            unset($row->Day24);
            unset($row->Day25);
            unset($row->Day26);
            unset($row->Day27);
            unset($row->Day28);
            unset($row->Day29);
            unset($row->Day30);
            unset($row->Day31);
        }
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }


    public function store(Request $request)
    {
        $request = object_to_array(json_decode($request->getContent()));
        
        $from = Carbon::parse($request['DateFrom']);
        $period = $from->format('Ym');
        $this->allotmentService->create($request);

        $item = Item::findOrFail($request['Item']);

        $data =  ['item' => $item->ParentOid, 'period' => $period, 'type' => $request['TravelType'] ];

        return response()->json(
            $data,
            Response::HTTP_OK
        );
       
    }

    public function update(Request $request, $id)
    {
        $allotment = TravelAllotment::findOrFail($id);
        $this->allotmentService->updateQty($allotment, $request['Quantity']);
        if ($request['CutoffDay'] != $allotment->CutoffDay) {
            $this->allotmentService->updateCutoff($allotment, $request['CutoffDay']);
        }
        
        $item = $allotment->ItemObj;
        $data =  ['item' => $item->ParentOid, 'period' => $allotment->Period, 'type' => $allotment->TravelType ];

        return response()->json(
            $data,
            Response::HTTP_OK
        );
        
    }

    public function destroy(Request $request, $id)
    {
        $allotment = TravelAllotment::findOrFail($id);
        $item = $allotment->Item;
        $period = $allotment->Period;
        $allotment->TravelAllotmentCutoffObj()->delete();
        $allotment->delete();
        
        
        return response()->json(
            null, Response::HTTP_NO_CONTENT
        );
    }

    public function destroyAllotmentTransaction(Request $request, $id)
    {
        $allotment = TravelTransactionAllotment::findOrFail($id);
        $id = Item::where('Oid', $allotment->Item)->value('ParentOid');
        $period = $allotment->Period;
        $allotment->delete();

        return response()->json(
            null, Response::HTTP_NO_CONTENT
        );
    }

    public function take(Request $request)
    {
        $request = object_to_array(json_decode($request->getContent()));

        $from = Carbon::parse($request['DateFrom']);
        $to =  Carbon::parse($request['DateEnd']);
        $period = $from->format('Ym');
        
        $this->allotmentTransactionService->assignAllotment([
            'From' => $from,
            'To' => $to,
            'TravelType' => $request['TravelType'],
            'Qty' => $request['Quantity'],
            'Code' => $request['Code'],
            'Item' => $request['Item']
        ]);
       
        $item = Item::findOrFail($request['Item']);
        $data = [ 'item' => $item->ParentOid, 'period' => $period, 'type' => $request['TravelType'] ];

        return response()->json(
            $data,
            Response::HTTP_OK
        );
        
    }
}