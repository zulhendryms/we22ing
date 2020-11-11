<?php

namespace App\Core\External\Services;

use Carbon\Carbon;
use App\Core\Base\Services\HttpService;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Services\AuditService;

class MajesticService {

    /** @var HttpService $httpService */
    private $httpService;
    private $auditService;
    private $defaultParams;

    /**
     * @param HttpService $httpService
     * @return void
     */
    public function __construct(HttpService $httpService, AuditService $auditService)
    {
        $this->serverKey = config('services.midtrans.server_key');
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $this->defaultParams = [
            'Username' => config('services.majestic.username'),
            'SecurityKey' => config('services.majestic.security_key')
        ];

        $this->httpService = $httpService
        ->baseUrl(config('services.majestic.url'))
        ->headers($headers)
        ->timeout(60)
        ->json();

        $this->auditService = $auditService;
    }

    /**
     * Get list of available schedules
     * 
     * @param array $options
     * @return mixed
     */
    public function getFerries($options)
    {
        $data = [
            'TicketCategory' => 'Adult',
            'TotalPax' => $options['qty'],
            'JourneyType' => 2,
            'IsReturnOpenTicket' => 1,
            'DepartPort' => $options['port_from'],
            'ArrivalPort' => $options['port_to'],
            'TravelDate' => $options['date'],
            'ReturnDepartPort' => '',
            'ReturnArrivalPort' => '',
            'ReturnTravelDate' => ''
        ];

        logger("[MajesticService - getFerries]".PHP_EOL, ['params' => $options]);

        return $this->httpService->post('/MFFSchedule', array_merge($this->defaultParams, $data));
    }

    /**
     * Book majestic ferry
     * 
     * @param array $options
     * @return mixed
     */
    public function book($options)
    {
        $ferries = $this->getFerries([
            'qty' => $options['qty'],
            'port_from' => $options['port_from'],
            'port_to' => $options['port_to'],
            'date' => $options['date']
        ]);
        
        throw_if(count($ferries) == 0, \Exception::class, "Schedule not available");

        $ferries = $ferries[0];

        throw_if(count($ferries->DepartTrips) == 0, \Exception::class, "Schedule not available");
        throw_if(count($ferries->Price) == 0, \Exception::class, "Schedule not available");

        // $price = $ferries->Price[0];

        $schedule;
        foreach ($ferries->DepartTrips as $trip) {
            if (strtotime($trip->TravelTime) == strtotime($options['time'])) {
                if ($trip->Capacity && $trip->QuotaLeft <= 0) break;
                $schedule = $trip;
                break;
            }
        }

        throw_unless(isset($schedule), \Exception::class, "Schedule not available");

        $data = [
            'BookingName' => company_name(),
            'TicketCategory' => 'Adult',
            'JourneyType' => 2,
            'IsReturnOpenTicket' => 1,
            'DepartTripCode' => $schedule->TripCode,
            'DepartSeatCategory' => $schedule->SeatCategory,
            'TravelDate' => $options['date'],
            'ReturnTripCode' => '',
            'ReturnSeatCategory' => '',
            'ReturnTravelDate' => '',
            'IncludeDepartTerminalFee' => 1,
            'IncludeReturnTerminalFee' => 1,
            'Passenger' => [],
        ];


        foreach ($options['passengers'] as $passenger) {
            $price = null;
            foreach ($ferries->Price as $p) { 
                if ($p->TicketType == 'WEEKDAYPROMO') {
                    $price = $p;
                    break;
                }
                if (is_null($price) && $p->TicketType == 'BASE') {
                    $price = $p;
                }
            }
        
            if (is_null($price)) { 
                $price = $ferries->Price[0]; 
            } 

            $data['Passenger'][] = [
                'PassportNo' => $passenger['passport_no'],
                'PassportName' => $passenger['name'],
                'BirthDate' => Carbon::parse($passenger['birthdate'])->toDateString(),
                'BirthPlace' => $passenger['birthplace'],
                'Gender' => $passenger['title'] == 'Mr.' ? 'M' : 'F',
                'Nationality' => $passenger['nationality'] ?? 'Singaporean',
                'PassportIssueDate' => Carbon::parse($passenger['passport_expiry'])->subYears(5)->toDateString(),
                'PassportExpiredDate' => Carbon::parse($passenger['passport_expiry'])->toDateString(),
                'PriceCode' => $price->PriceCode,
            ];
        }
        
        
        logger("MajesticService - book".PHP_EOL, ['params' => $data]);

        $res = $this->httpService->post('/MFFPassengerBooking', array_merge($this->defaultParams, $data));

        $this->auditService->create($options['pos'], [
            'Module' => 'CallAPI',
            'Description' => 'Connecting Majestic API',
            'Message' => json_encode($res),
            'User' => $options['pos']->UserObj
        ]);

        throw_unless(isset($res) && is_array($res), \Exception::class, "An error occurred, please try again");
        $res = $res[0];
        if (property_exists($res, "Status") && $res->Status == 'Error') {
            throw new \Exception($res->Message);
        }
        return $res;
    }

    /**
     * Cancel majestic booking
     * 
     * @param array $options
     * @return mixed
     */
    public function cancel($options)
    {
        $data = [
            'BookingCode' => $options['code'],
            'PassportNo' => $options['passport_no']
        ];

        return $this->httpService->post('/MFFCancelBooking', array_merge($this->defaultParams, $data));
    }
}