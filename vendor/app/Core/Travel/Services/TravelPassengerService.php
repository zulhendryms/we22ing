<?php

namespace App\Core\Travel\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\POS\Entities\PointOfSaleDetail;
use App\Core\Master\Services\PassengerService;

class TravelPassengerService
{
    /** @var PassengerService $passengerService */
    protected $passengerService;

    /**
     * @param PassengerService $passengerService
     */
    public function __construct(PassengerService $passengerService)
    {
        $this->passengerService = $passengerService;
    }

    public function create(TravelTransactionDetail $pos, $param)
    {
        $passenger = $pos->Passengers()->create($param);

        // if (isset($pos->UserObj)) {
        //     $user = $pos->UserObj->Passengers()
        //     ->where('Name', $param['Name'])
        //     ->where('DateOfBirth', $param['DateOfBirth'])
        //     ->first();

        //     if (is_null($user)) $user->Passengers()->create($param);
        // }
        return $this->passengerService->create($pos->CompanyObj, $param);
    }
}