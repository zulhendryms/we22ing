<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
  <title>prereport-traveltransaction</title>
    <style type="text/css">    
      table.all {
        border: 1px solid black;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
        font-size: 8pt;
        white-space: normal;
        margin-bottom: 2px;
      }   
      /* .flex {
        display: flex;
        text-align: left;
        line-height: 30px;
      } */

      .unborder {
        border: 0px solid black;
      }
      .bold {
        font-size: 10pt;
        font-weight: bold;
      }
      .w-40 {
        width: 40%;
      }
      .w-20 {
        width: 20%;
      }
      .w-60 {
        width: 60%;
      }
      .w-33 {
        width: 33.3%;
      }
      .w-50 {
        width: 50%;
      }
      .w-25 {
        width: 25%;
      }
      .w-full {
        width: 100%;
      }
      .mr-2 {
        margin-right: 2px;
        padding-right: 2px;
      }
      .mb-2 {
        margin-bottom: 1em;
      }
      .text-header {
        text-align: left;
      }
      .remark {
        vertical-align:text-top;
        width: 99.5%;
        height: 100px;
        border: 1px solid black;
        font-size: 10pt;
      }
      table,th,td {
        border: 1px solid black;
        height: 26px;
      }
      .coach1{
        grid-column: 5;
      }
      header{
        padding-bottom: 10px;
      }

    </style>
</head>
<header>
  <h3 style="text-align: center;">GROUP SETTLEMENT FORM</h3>
</header>
  <body style="font-family:Tahoma, Geneva, sans-serif">
  <table class="w-full unborder">
    <tr>
      <td class="w-40 unborder">
        <table class="all">
          <tbody>
            <tr>
              <td class="w-40 bold">AGENT</td>
              <td>{{$data[0]->Agent}}</td>
            </tr>
          <tbody>
            <tr>
              <td class="bold">TOUR CODE</td>
              <td>{{$data[0]->TourCode}}</td>
            </tr>
            <tr>
              <td class="bold">TOUR GUIDE</td>
              <td>{{$data[0]->TourGuide1}}</td>
            </tr>
          </tbody>    
        </table>
        </td>

        <td class="w-20 unborder">
          <table class="all" cellspan="0" cellspacing="0" style="white-space:nowrap">
            <tbody>
              <tr>
                <td class="w-20 bold">SOS TIME</td>
                <td></td>
              </tr>
              <tr>
                <td class="bold">ARR DATE</td>
                <td>{{$data[0]->DateFrom}}</td>
              </tr>
              <tr>
                <td class="bold">DPT DATE</td>
                <td>{{$data[0]->DateUntil}}</td>
              </tr>
            </tbody>
          </table>
        </td>

        <td class="w-40 unborder">
          <table class="all" cellspan="0" cellspacing="0">
            <tbody>
              <tr>
                <td class="bold">COACH 1</td>
                <td colspan="8"></td>
              </tr>
              <tr>
                <td class="bold">COACH 2</td> 
                <td colspan="8"></td>
              </tr>
              </tbody>
              <tbody>
              <tr class="coach1">
                <td class="bold">PAX</td>
                <td class="bold">ADT</td>
                <td>{{$data[0]->ADT}}</td>
                <td class="bold">CHD</td>
                <td>{{$data[0]->CHD}}</td>
                <td class="bold">INF</td>
                <td>{{$data[0]->INF}}</td>
                <td class="bold">TL</td>
                <td>{{$data[0]->TL}}</td>
              </tr>
            </tbody>
          </table>
        </td>
          
    </tr>
</table>
  {{-- <!-- tabel 1 --> --}}

  {{-- <!-- tabel2 --> --}}
  <table class="w-full unborder">
    <tr>
      <td class="w-40 unborder">
        <table class="all">
          <tbody>
            <tr>
              <td class="bold">ARR/DPT</td>
              <td class="bold">DATE</td>
              <td class="bold">FLIGHT No.</td>
              <td class="bold">REMARK</td>
            </tr>
            @php $count = 0; @endphp
            @foreach($dataFlight as $row)
              <tr>
                <td>{{$row->FlightType ? 'Arrival' : 'Departure'}}</td>
                <td>{{$row->FlightDate}}</td>
                <td>{{$row->FlightNo}}</td>
                <td>{{$row->FlightRemark}}</td>
              </tr>
              @php $count = $count + 1; @endphp
            @endforeach            
            @for($i = 0; $i < (6 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
          </tbody>
        </table>
      </td>

      <td class="w-40 unborder">
        <table class="all" cellspan="0" cellspacing="0" style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="bold">DATE</td>
              <td class="bold">HOTEL NAME</td>
              <td class="bold">ROOMS</td>
            </tr>
            @php $count = 0; @endphp
            @foreach($dataDetail as $row)
              @if($row->OrderType == 'Hotel')
                <tr>
                  <td>{{$row->Date}}</td>
                  <td>{{$row->BusinessPartner}}</td>
                  <td>{{$row->TravelHotelRoomType}}</td>
                </tr>
                @php $count = $count + 1; @endphp
              @endif
            @endforeach            
            @for($i = 0; $i < (6 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
          </tbody>
        </table>
      </td>
    </tr>
  </table>
  {{-- <!-- tabel 3 --> --}}
  <table class="w-full unborder">
    <tr>
      <td class="w-33 unborder">
        <table class="all" style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="bold">DATE</td>
              <td class="bold">LUNCH/RESTAURANT</td>
              <td class="bold">$ AMOUNT</td>
            </tr>
            @php $count = 0; $LunchTotal=0;@endphp
            @foreach($dataDetail as $row)
              @if($row->OrderType == 'Restaurant' && ($row->PurchaseOption == 'Breakfast' || $row->PurchaseOption == 'Lunch'))
                <tr>
                  <td>{{$row->Date}}</td>
                  <td>{{$row->BusinessPartner}}</td>
                  <td align="right">{{number_format($row->TotalAmount ,2)}}</td>
                </tr>
                @php 
                $count = $count + 1;
                $LunchTotal = $row->TotalAmount;
                @endphp
              @endif
            @endforeach            
            @for($i = 0; $i < (5 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
            <tr>
              <td></td>
              <td align="right" style="font-weight: bold;">Sub: </td>
              <td align="right">{{number_format($LunchTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
    </td>
    <td class="w-33 unborder">
      <table class="all" cellspan="0" cellspacing="0" style="white-space:nowrap">
        <tbody>
            <tr>
              <td class="bold">DATE</td>
              <td class="bold">HIGH TEA</td>
              <td class="bold">$ AMOUNT</td>
            </tr>
            @php $count = 0; $HighTeaTotal= 0;@endphp
            @foreach($dataDetail as $row)
            @if($row->OrderType == 'Restaurant' && ($row->PurchaseOption == 'Hightea' || $row->PurchaseOption == 'Hightea2'))
                <tr>
                  <td>{{$row->Date}}</td>
                  <td>{{$row->BusinessPartner}}</td>
                  <td align="right">{{number_format($row->TotalAmount ,2)}}</td>
                </tr>
                @php 
                $count = $count + 1;
                $HighTeaTotal = $row->TotalAmount;
                @endphp
              @endif
            @endforeach            
            @for($i = 0; $i < (5 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
            <tr>
              <td></td>
              <td align="right" style="font-weight: bold;">Sub: </td>
              <td align="right">{{number_format($HighTeaTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
      <td class="w-33 unborder">
        <table class="all" cellspan="0" cellspacing="0" style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="bold">DATE</td>
              <td class="bold">DINNER/RESTAURANT</td>
              <td class="bold">$ AMOUNT</td>
            </tr>
            @php $count = 0; $DinnerTotal = 0;@endphp
            @foreach($dataDetail as $row)
            @if($row->OrderType == 'Restaurant' && $row->PurchaseOption == 'Dinner')
                <tr>
                  <td>{{$row->Date}}</td>
                  <td>{{$row->BusinessPartner}}</td>
                  <td align="right">{{number_format($row->TotalAmount ,2)}}</td>
                </tr>
                @php 
                $count = $count + 1; 
                $DinnerTotal = $row->TotalAmount;
                @endphp
              @endif
            @endforeach            
            @for($i = 0; $i < (5 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
            <tr>
              <td></td>
              <td align="right" style="font-weight: bold;">Sub: </td>
              <td align="right">{{number_format($DinnerTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
  </tr>
  </table>
  {{-- <!-- TABEL 4 --> --}}
  <table class="w-full unborder">
    <tr>
      <td class="w-50 unborder">
        <table class="all">
          <tbody>
            <tr>
              <td class="bold">DATE</td>
              <td class="bold">SENTOSA ATTRACTIONS</td>
              <td class="bold">SNR</td>
              <td class="bold">ADT</td>
              <td class="bold">CHD</td>
              <td class="bold">$ AMT</td>
            </tr>
             @php $count = 0; $SentosaTotal=0;@endphp
            @foreach($dataDetail as $row)
              @if($row->OrderType == 'Attraction' && $row->ItemGroupCode == 'Sentosa')
                <tr>
                  <td>{{$row->Date}}</td>
                  <td>{{$row->Item}}</td>
                  <td align="right">{{$row->QtySnr}}</td>
                  <td align="right">{{$row->QtyAdt}}</td>
                  <td align="right">{{$row->QtyChd}}</td>
                  <td align="right">{{number_format($row->TotalAmount ,2)}}</td>
                </tr>
                @php
                $count = $count + 1; 
                $SentosaTotal = $SentosaTotal + $row->TotalAmount;
                @endphp
              @endif
            @endforeach            
            @for($i = 0; $i < (6 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
            <tr>
              <td colspan="5" style="text-align: right; font-weight: bold;">Sub: </td>
              <td align="right">{{number_format($SentosaTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
    </td>
    <td class="w-50 unborder">
      <table class="all" cellspan="0" cellspacing="0">
        <tbody>
          <tr>
            <td class="bold">DATE</td>
              <td class="bold">CITY ATTRACTIONS</td>
              <td class="bold">SNR</td>
              <td class="bold">ADT</td>
              <td class="bold">CHD</td>
              <td class="bold">$ AMT</td>
            </tr>
            @php $count = 0; $CityTotal=0;@endphp
            @foreach($dataDetail as $row)
            @if($row->OrderType == 'Attraction' && $row->ItemGroupCode != 'Sentosa')
                <tr>
                  <td>{{$row->Date}}</td>
                  <td>{{$row->Item}}</td>
                  <td align="right">{{$row->QtySnr}}</td>
                  <td align="right">{{$row->QtyAdt}}</td>
                  <td align="right">{{$row->QtyChd}}</td>
                  <td align="right">{{number_format($row->TotalAmount ,2)}}</td>
                </tr>
                @php $count = $count + 1; 
                $CityTotal = $CityTotal + $row->TotalAmount;
                @endphp
              @endif
            @endforeach            
            @for($i = 0; $i < (6 - $count ); $i++)
              <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            @endfor
            <tr>
              <td colspan="5" style="text-align: right; font-weight: bold;">Sub: </td>
              <td align="right">{{number_format($CityTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
  </tr>
  </table>
  {{-- <!-- TABEL 5 --> --}}
  <table class="w-full unborder">
    <tr>
      <td class="w-25 unborder">
        <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="bold w-50">Tips</td>
              <td class="bold">Expenses</td>
            </tr>
            <tr>
              <td class="bold">Driver Tip</td>
              <td align="right">{{number_format($data[0]->DriverTip ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Porter Tip</td>
              <td align="right">{{number_format($data[0]->PorterTip ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Luggage Tip</td>
              <td align="right">{{number_format($data[0]->LuggageTip ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Mineral Water</td>
              <td align="right">{{number_format($data[0]->MineralWater ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Taxi Claim</td>
              <td align="right">{{number_format($data[0]->TaxiClaim ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Combo / Coach Fee</td>
              <td align="right">{{number_format($data[0]->CoachFee ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tips Guide</td>
              <td align="right">{{number_format($data[0]->GuideTips ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tour Guide Fee</td>
              <td align="right">{{number_format($data[0]->GuideFee ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Other</td>
              <td align="right">{{number_format($data[0]->Other ,2)}}</td>
            </tr>
            @php
                $ExpenseTotal = 0;
                $ExpenseTotal = $data[0]->DriverTip + $data[0]->PorterTip + $data[0]->LuggageTip + $data[0]->MineralWater + $data[0]->TaxiClaim + $data[0]->CoachFee + $data[0]->GuideTips + $data[0]->Other;
            @endphp
            <tr>
              <td class="bold">BAL TO ACE</td>
              <td align="right">{{number_format($ExpenseTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
      <td class="w-25 unborder">
        <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="bold w-50">Souvenir</td>
              <td class="bold">Income</td>
            </tr>
            <tr>
              <td class="bold">Total Box</td>
              <td align="right">{{number_format($data[0]->toTotalBox ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">To Tour Leader</td>
              <td align="right">{{number_format($data[0]->toTourLeader ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">To Tour Guide</td>
              <td align="right">{{number_format($data[0]->toTourGuide ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">To ACE</td>
              <td align="right">{{number_format($data[0]->toCompany ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Exchange Rate</td>
              <td align="right">{{number_format($data[0]->ExcangeRate ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Sediz Costing</td>
              <td align="right">{{number_format($data[0]->SerdizCosting ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tips to ACE</td>
              <td align="right">{{number_format($data[0]->TipstoAce ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Other</td>
              <td align="right">{{number_format($data[0]->toOther ,2)}}</td>
            </tr>
            <tr>
              <td class="bold"></td>
              <td align="right"></td>
            </tr>
            @php
                $IncomeTotal = 0;
                $IncomeTotal = $data[0]->toTotalBox + $data[0]->toTourLeader + $data[0]->toTourGuide + $data[0]->toCompany + $data[0]->ExcangeRate + $data[0]->SerdizCosting + $data[0]->TipstoAce + $data[0]->toOther;
            @endphp
            <tr>
              <td class="bold">BAL TO TG</td>
              <td align="right">{{number_format($data[0]->BalanceToCompany ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
    <td class="w-25 unborder">
      <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
        <tbody>
            <tr>
              <td class="bold w-50">Optioal Tour 1</td>
              <td class="bold">Expenses</td>
            </tr>
            <tr>
              <td class="bold">Selling (Adult)</td>
              <td align="right">{{number_format($data[0]->OptionalTour1TicketAdult ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Selling (Child)</td>
              <td align="right">{{number_format($data[0]->OptionalTour1TicketChild ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Selling (Senior)</td>
              <td align="right">{{number_format($data[0]->OptionalTour1TicketSenior ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">MRT / Expenses</td>
              <td align="right">{{number_format($data[0]->OptionalTour1AmountMRT ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Ticket Cost</td>
              <td align="right">{{number_format($data[0]->OptionalTour1AmountTicket ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Driver Tips</td>
              <td align="right">{{number_format($data[0]->OptionalTour1AmountDriver ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tour Leader</td>
              <td align="right">{{number_format($data[0]->OptionalTour1AmountTourLeader ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tour Guide</td>
              <td align="right">{{number_format($data[0]->OptionalTour1AmountTourGuide ,2)}}</td>
            </tr>
            <tr>
              <td class="bold"></td>
              <td align="right"></td>
            </tr>
            @php
                $Tour1Total = 0;
                $Tour1Total = $data[0]->OptionalTour1TicketSenior + $data[0]->OptionalTour1AmountMRT + $data[0]->OptionalTour1AmountTicket + $data[0]->OptionalTour1AmountDriver 
                            + $data[0]->OptionalTour1AmountTourLeader + $data[0]->OptionalTour1AmountTourGuide;@endphp
            <tr>
              <td class="bold">BAL TO TG</td>
              <td align="right">{{number_format($Tour1Total ,2)}}</td>
            </tr>
          </tbody>
        </table>
    </td>
    <td class="w-25 unborder">
      <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
        <tbody>
            <tr>
              <td class="bold w-50">Optioal Tour 2</td>
              <td class="bold">Expenses</td>
            </tr>
            <tr>
              <td class="bold">Selling (Adult)</td>
              <td align="right">{{number_format($data[0]->OptionalTour2TicketAdult ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Selling (Child)</td>
              <td align="right">{{number_format($data[0]->OptionalTour2TicketChild ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Selling (Senior)</td>
              <td align="right">{{number_format($data[0]->OptionalTour2TicketSenior ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Other Expenses</td>
              <td align="right">{{number_format($data[0]->OptionalTour2AmountMRT ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Ticket Cost</td>
              <td align="right">{{number_format($data[0]->OptionalTour2AmountTicket ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Driver Tips</td>
              <td align="right">{{number_format($data[0]->OptionalTour2AmountDriver ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tour Leader</td>
              <td align="right">{{number_format($data[0]->OptionalTour2AmountTourLeader ,2)}}</td>
            </tr>
            <tr>
              <td class="bold">Tour Guide</td>
              <td align="right">{{number_format($data[0]->OptionalTour2AmountTourGuide ,2)}}</td>
            </tr>
            <tr>
              <td class="bold"></td>
              <td align="right"></td>
            </tr>
            @php
            $Tour2Total = 0;
            $Tour2Total = $data[0]->OptionalTour2TicketSenior + $data[0]->OptionalTour2AmountMRT + $data[0]->OptionalTour2AmountTicket 
                        + $data[0]->OptionalTour2AmountDriver + $data[0]->OptionalTour2AmountTourLeader + $data[0]->OptionalTour2AmountTourGuide;@endphp
            <tr>
              <td class="bold">BAL TO TG</td>
              <td align="right">{{number_format($Tour2Total ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
    </table>
    </tr>
    
</table>
{{-- <!-- tabel 1 --> --}}

{{-- <!-- tabel2 --> --}}
<table class="w-full unborder">
    <tr>
      <td class="w-25 unborder">
        <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="w-50 bold">TOTAL EXPS :</td>
              <td align="right">{{number_format($data[0]->BalanceToGuide ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
      <td class="w-25 unborder">
        <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="w-50 bold">AMT TO ACE :</td>
              <td align="right">{{number_format($data[0]->BalanceToCompany ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
      @php
         $AmtDueToAce = 0;
         $AmtDueToAce = $LunchTotal + $DinnerTotal + $SentosaTotal + $CityTotal + $ExpenseTotal + $IncomeTotal + $Tour1Total + $Tour2Total;
      @endphp
      <td class="w-25 unborder">
        <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="w-50 bold">AMT DUE TO ACE :</td>
              <td align="right">{{number_format($AmtDueToAce ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
      <td class="w-25 unborder">
        <table class="all" cellspan="0" cellspacing="0"style="white-space:nowrap">
          <tbody>
            <tr>
              <td class="w-50 bold">GUIDE :</td>
              <td align="right">{{number_format($data[0]->OptionalTour2AmountTourBalanceTotal ,2)}}</td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>

    <tr>
      <table class="w-full unborder">
        <tr>
          <td class="w-25 unborder">
            <table class="all remark" cellspan="0" cellspacing="0"style="white-space:normal">
              <tbody>
            <td style="vertical-align:top;"><strong>REMARK :</strong>{!! $data[0]->Remark !!}</td>
          </tbody>
        </table>
    </tr>


  </table>
</body>
</html>