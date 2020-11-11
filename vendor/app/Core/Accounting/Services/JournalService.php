<?php

namespace App\Core\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\Status;

class JournalService 
{

    public function getOpeningBalance($date, $accountId, $businessPartnerId = null)
    {
        $companyId = config('app.company_id');
        $date = Carbon::parse($date)->toDateString();
        $query = "SELECT SUM(IFNULL(DebetAmount,0) - IFNULL(CreditAmount,0)) AS Actual, 
            SUM(IFNULL(DebetBase,0) - IFNULL(CreditBase,0)) AS Base 
            FROM accjournal j 
            LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType 
            WHERE j.Date <= '{$date}' 
            AND jt.Code = 'OPEN' 
            AND j.GCRecord IS NULL 
            AND j.Account = '{$accountId}' AND j.Company = '{$companyId}' ";

        if (!is_null($businessPartnerId)) {
            $query .= "AND BusinessPartner = '{$businessPartnerId}'";
        }
        $result = DB::select($query);
        $result = array_shift($result);
        return $result;
    }

    public function getRate($date, $accountId, $businessPartnerId = null)
    {
        $companyId = config('app.company_id');
        $rate = $this->getOpeningBalance($date, $accountId, $businessPartnerId);
        $startDate = Carbon::parse($date)->startOfMonth()->toDateString();
        $toDate = Carbon::parse($date)->toDateString();

        $lastActual = 0;
        $lastBase = 0;

        if (!is_null($rate)) {
            if (isset($rate->Actual)) $lastActual = $rate->Actual;
            if (isset($rate->Base)) $lastBase = $rate->Base;
        }

        // $query = "SELECT (SUM(DebetBase - CreditBase) + {$lastBase}) / (SUM(DebetAmount - CreditAmount) + {$lastActual})
        // AS Rate FROM accjournal 
        // WHERE Date >= '{$startDate}' 
        // AND Date <= '{$toDate}' 
        // AND Company = '{$companyId}' 
        // AND Account = '{$accountId}' 
        // AND GCRecord IS NULL ";
        // if (!is_null($businessPartnerId)) {
        //     $query .= "AND BusinessPartner = '{$businessPartnerId}'";
        // }
        // $result = DB::select($query);
        // if (count($result) == 0) return 1;
        // return $result[0]->Rate ?? 1;
        $query = "SELECT IFNULL(SUM(IFNULL(DebetBase,0) - IFNULL(CreditBase,0)),0) AS TotalBase, IFNULL(SUM(IFNULL(DebetAmount,0) - IFNULL(CreditAmount,0)),0) AS TotalAmount
        FROM accjournal 
        WHERE Date >= '{$startDate}' 
        AND Date <= '{$toDate}' 
        AND Company = '{$companyId}' 
        AND Account = '{$accountId}' 
        AND GCRecord IS NULL ";
        if (!is_null($businessPartnerId)) {
            $query .= "AND BusinessPartner = '{$businessPartnerId}'";
        }
        $result = DB::select($query);
        if (count($result) == 0)  {
            // if ($lastBase + $lastActual == 0) return 1; else return $lastBase / ($lastActual ?? 1);
            return $lastBase + $lastActual == 0 ? 1 : $lastBase / ($lastActual ?? 1);
        }            
        else {
            $rate = $result[0]->TotalAmount + $lastActual;
            if ($rate == 0) $rate = 1;
            return ($result[0]->TotalBase + $lastBase) / $rate;
        }            
    }

    public function checkUnpostedTransaction($periodId)
    {
        $companyId = config('app.company_id');
        $tables = [ 
            [ 'accgeneraljournal', 'Date', 'General Journals' ],
            [ 'acccashbank', 'Date', 'Cash Bank' ],
            [ 'accapinvoice', 'Date', 'AP Invoice' ],
            [ 'accarinvoice', 'Date', 'AR Invoice' ]
          ];
          $status = Status::entry()->first();
          for ($i = 0; $i < count($tables); $i++) {
            $query = "SELECT COUNT(Oid) as count FROM {$tables[$i][0]} 
                WHERE Status ='{$status->Oid}' 
                AND DATE_FORMAT(Date, '%Y%m') = '{$periodId}'
                AND Company = '{$companyId}' 
                AND GCRecord IS NULL";
            $result = DB::select($query);
            if ($result[0]->count > 0) {
                throw new \Exception("There are {$result[0]->count} Unposted transactions from {$tables[$i][2]}");
                return 0;
            }
          }
          return 1;
    }
    
    public function checkUnposted($periodId, $table)
    {
        $companyId = config('app.company_id');
        $status = Status::entry()->first();
        $query = "SELECT COUNT(Oid) as count FROM {$table} 
            WHERE Status ='{$status->Oid}' 
            AND DATE_FORMAT({$table}.Date, '%Y%m') = '{$periodId}' 
            AND Company = '{$companyId}' 
            AND GCRecord IS NULL";
        $result = DB::select($query);
        if ($result[0]->count > 0) {
            throw new \Exception("There are unposted {$query} transactions");
            return 0;
        } else {
            return 1;
        }        
    }
}