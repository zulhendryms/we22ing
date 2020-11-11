<?php

namespace App\Core\Master\Traits;

trait PaymentMethodAvailable
{
    /**
     * Scope a query to return available payment method.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        $now = now();
        if ($now->utc) {
            $now->addHours(company_timezone());
        }
        $tomorrow = (clone $now)->addDay(1);
        $tomorrowWithTime = $tomorrow->toDateTimeString();
        $tomorrow = $tomorrow->toDateString();
        $nowWithTime = $now->toDateTimeString();
        $now = $now->toDateString();

        return $query->whereRaw("IF(
            DisableStartTime > DisableEndTime,
             CONCAT('{$now}',' ', DisableStartTime) < '{$nowWithTime}' AND CONCAT('{$tomorrow}', ' ', DisableEndTime) >= '{$nowWithTime}',
             CONCAT('{$now}',' ', DisableStartTime) < '{$nowWithTime}' AND CONCAT('{$now}', ' ', DisableEndTime) >= '{$nowWithTime}'
            ) = 0")
        ->orWhereRaw('(DisableStartTime IS NULL OR DisableEndTime IS NULL)');
    }
}