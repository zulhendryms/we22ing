<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use Carbon\Carbon;
use Awobaz\Compoships\Compoships;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read boolean $ForIndonesian Check if the schedule is for Indonesian
 */
class FerrySchedule extends BaseModel {
    use Compoships;
    use BelongsToCompany;
    protected $table = 'ferferryschedule';

    public function __get($key)
    {
        switch ($key) {
            case 'ForIndonesian':
                // return  $this->Foreigner1WAdult + $this->Foreigner1WChild + $this->Foreigner2WAdult + $this->Foreigner2WChild == 0;
                $pricing = $this->FerryPricingObj;
                if (isset($pricing)) {                    
                    return  $pricing->F2WChildWkd + $pricing->F2WChildWke + $pricing->F2WAdultWkd + $pricing->F2WAdultWke == 0;
                } else {
                    return  $this->Foreigner1WAdult + $this->Foreigner1WChild + $this->Foreigner2WAdult + $this->Foreigner2WChild == 0;
                }
                
        }
        return parent::__get($key);
    }

    /**
     * Get the currency of the schedule
     */
    public function CurrencyObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid");
    }

    /**
     * Get the business partner port of the schedule
     */
    public function BusinessPartnerPortObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\BusinessPartnerPort", "BusinessPartnerPort", "Oid");
    }

    /**
     * Get the route of the schedule
     */
    public function RouteObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\Route", "Route", "Oid");
    }

    public function FerryPricingObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\FerryPricing", [ 'BusinessPartnerPort', 'Period', 'Route' ], [ 'BusinessPartnerPort', 'Period', 'Route' ]);
    }

    /**
     * @param string|number $age
     * @param boolean $local
     * @param string $path
     * @param Date $date
     * @param \App\Core\Master\Entities\BusinessPartner $businessPartner
     */
    public function getPrice($age, $local = true, $path = "1w", $date = null, $businessPartner = null)
    {
        $var = strtoupper($path);
        if (is_string($age)) {
            $age = ucfirst($age);
            $var .= $age;
        } else {
            if ($age > config('app.child_age')) {
                $var.= "Adult";
            } else {
                $var .="Child";
            }
        }

        $pricing = $this->FerryPricingObj;
        if (isset($pricing)) {
            $var .= (is_null($date) ? now() : Carbon::parse($date))->isWeekend() ? 'Wke' : 'Wkd';
            $level = '';
            if (is_null($businessPartner) && Auth::check()) {
                $businessPartner = Auth::user()->BusinessPartnerObj;
            }
            if (isset($businessPartner) && !empty($businessPartner->SalesPriceLevel)) $level = $businessPartner->SalesPriceLevel;
            $var .= $level;
            $var = ($local ? 'L' : 'F').$var;
            return $pricing->{$var};
        } else {
            $var = ($local ? 'Local' : 'Foreigner').$var;
            return $this->{$var};
        }
    }

    /**
     * @param string|number $age
     * @param boolean $local
     * @param string $path
     * @param Date $date
     * @param \App\Core\Master\Entities\BusinessPartner $businessPartner
     */
    public function getCostPrice($age, $local = true, $path = "1w", $date = null, $businessPartner = null)
    { //_LCAdultWkd
        $var = 'C';
        if (is_string($age)) {
            $age = ucfirst($age);
            $var .= $age;
        } else {
            if ($age > config('app.child_age')) {
                $var.= "Adult";
            } else {
                $var .="Child";
            }
        }

        $pricing = $this->FerryPricingObj;
        if (isset($pricing)) {
            $var .= (is_null($date) ? now() : Carbon::parse($date))->isWeekend() ? 'Wke' : 'Wkd';
            $level = '';
            if (is_null($businessPartner) && Auth::check()) {
                $businessPartner = Auth::user()->BusinessPartnerObj;
            }
            if (isset($businessPartner) && !empty($businessPartner->SalesPriceLevel)) $level = $businessPartner->SalesPriceLevel;
            $var .= $level;
            $var = ($local ? 'L' : 'F').$var;
            return $pricing->{$var};
        }
    }

    /**
     * @param \DateTime|Carbon|string $date
     * @return Carbon
     */
    public function getStartTime($date)
    {
      return Carbon::parse(Carbon::parse($date)->toDateString().' '.$this->Time);
    }

    /**
     * @param \DateTime|Carbon|string $date
     * @return Carbon
     */
    public function getEndTime($date)
    {
        $route = $this->RouteObj;
        $portFrom = $route->PortFromObj;
        $portTo = $route->PortToObj;
        $timezoneFrom = $portFrom->CityObj->TimezoneObj;
        $timezoneTo = $portTo->CityObj->TimezoneObj;

        $diff = (Carbon::now($timezoneTo->Name)->offset - Carbon::now($timezoneFrom->Name)->offset) / 3600;
        return Carbon::parse(Carbon::parse($date)->toDateString().' '.$this->Time)
        ->addHour($diff)
        ->addMinutes($this->duration);
    }

    /**
     * @param \DateTime|Carbon|string $date
     * @return boolean
     */
    public function checkExpiry($date)
    {
        $day = Carbon::parse($date)->day;
        if ($this->Period < Carbon::parse($date)->year.Carbon::parse($date)->format('m')) return true;
        if ($day == Carbon::now()->day) {
            $now = Carbon::now();
            return $now->hour.$now->minute < $this->TimeCutOff;
        }
        return false;
    }

    /**
     * Update schedule paid allotment
     * 
     * @param \DateTime|Carbon|string $date
     * @param integer $qty
     */
    public function paid($date, $qty)
    {
      $day = Carbon::parse($date)->day;
      $this->{'Book'.$day} = $this->{'Book'.$day} - $qty;
      $this->{'Paid'.$day} = $this->{'Paid'.$day} + $qty;
      $this->save();
    }

     /**
     * Update schedule booked allotment
     * 
     * @param \DateTime|Carbon|string $date
     * @param integer $qty
     */
    public function booked($date, $qty)
    {
      $day = Carbon::parse($date)->day;
      $this->{'Book'.$day} = $this->{'Book'.$day} + $qty;
      $this->save();
    }

    /**
     * Update schedule booked allotment
     * 
     * @param \DateTime|Carbon|string $date
     * @param integer $qty
     */
    public function cancelled($date, $qty)
    {
      $day = Carbon::parse($date)->day;
      $this->{'Book'.$day} = $this->{'Book'.$day} - $qty;
      $this->save();
    }


}