<table class="bordered">
    <tr class="strong text-center">
        <td>Flight No</td>
        <td>Class</td>
        <td>Date</td>
        <td>From</td>
        <td>To</td>
        <td style="width: 5%">ETD</td>
        <td style="width: 5%">ETA</td>
        <td style="width: 35%">Remark</td>
    </tr>
    <tr>
        <td>{{$travelTransaction->FlightDepartNumber}}</td>
        <td></td>
        <td>
            @isset($travelTransaction->FlightDepartDate)
                {{Carbon::parse($travelTransaction->FlightDepartDate)->format('d M Y')}}
            @endisset
        </td>
        <td>
            {{$travelTransaction->FlightDepartFrom}}
        </td>
        <td>
            {{$travelTransaction->FlightDepartTo}}
        </td>
        <td>
            {{Carbon::parse($travelTransaction->FlightDepartDate)->format('H:i')}}
        </td>
        <td>
            {{gmdate('H:i', $pos->TravelTransactionObj->FlightDepartETA)}}
        </td>
        <td></td>
    </tr>
    <tr class="border-bottom-none">
        <td>{{$travelTransaction->FlightReturnNumber}}</td>
        <td></td>
        <td>
            @isset($travelTransaction->FlightReturnDate)
                {{Carbon::parse($travelTransaction->FlightReturnDate)->format('d M Y')}}
            @endisset
        </td>
        <td>
            {{$travelTransaction->FlightReturnFrom}}
        </td>
        <td>
            {{$travelTransaction->FlightReturnTo}}
        </td>
        <td>
            {{Carbon::parse($travelTransaction->FlightReturnDate)->format('H:i')}}
        </td>
        <td>
            {{gmdate('H:i', $pos->TravelTransactionObj->FlightReturnETA)}}
        </td>
        <td></td>
    </tr>
</table>