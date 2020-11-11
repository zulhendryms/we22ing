<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>E-Ticket Attraction</title></head>
<body style="margin:0; font-family:Verdana, Geneva, sans-serif">
  <div style="background-color: {{config('colors.base')}};padding:18px 15px 0px 15px;text-align: left;color:#fff;">
    <img src="{{company_logo(1)}}" width="120" />
    <div style="float:right"><div style="font-size: 28px;font-weight: bold;">BOOKING SUMMARY</div></div>
    <div style="clear:both"></div><br />
  </div>
  <div style="padding: 13px 20px 13px 20px;">
    <div>
      <div style="float:left">
        <strong style="font-size: 14px;margin:0 0 5px 0;color:#BABABA;">Booking Id: </strong><br />
          <strong style="font-size: 20px; margin: 0 0 5px 0; color: {{config('colors.base')}};">{{$pos->Code}}</strong><br />
          @if ($pos->CodeReference)
            <strong style="font-size: 20px; margin: 0 0 5px 0; color: {{config('colors.base')}};">Booking No. {{$pos->CodeReference}}</strong><br />
          @endif        
      </div>
      <div style="font-size: 12px; float:right;padding: 8px 15px 8px 14px;color: #fff;font-weight: bold;background-color: {{config('colors.base')}};border-radius: 8px;">{{strtoupper($pos->StatusObj->Name)}}</div>      
    </div>
    <div style="clear:both"></div>
  </div>

  <div style="100%;margin-bottom:15px;padding: 0px 20px 0px 20px;">
    <div style="float:left;width:40%;font-size:12px;color:#858585">
      Issued at: {{\Carbon\Carbon::parse($pos->DatePayment)->tz('Asia/Jakarta')->toDayDateTimeString()}}<br />
      @if (isset($pos->TravelTransactionObj->EmployeeObj)) Tour Guide: {{$pos->TravelTransactionObj->EmployeeObj->Name}}<br /> @endif
      Date Period: {{\Carbon\Carbon::parse($pos->TravelTransactionObj->DateFrom)->format('d M Y')}} - {{\Carbon\Carbon::parse($pos->TravelTransactionObj->DateUntil)->format('d M Y')}}
    </div>
    <div style="float:left;width:40%;font-size:12px;color:#858585">
      Adult: {{$pos->TravelTransactionObj->QtyAdult}}<br />
      Child: {{$pos->TravelTransactionObj->QtyChild}}<br />
      Infant: {{$pos->TravelTransactionObj->QtyInfant}}
    </div>
    <div style="clear:both"></div>
  </div>
  
  <div style="background-color: #EFEFEF;font-weight: bold;padding: 10px 0 10px 20px;font-size: 16px;border-width: 0px 0 0px 0;border-color: #888888;border-style: solid;">Booking Details</div>  
<div style="padding: 10px 20px 10px 20px;">
  @foreach($pos->TravelTransactionDetails as $key => $detail)
    @if ($detail->Type != 5)
      <div style="100%;margin-bottom:15px">
        @if(isset($detail->Item))
        <div style="float:left;width:80%">{{$detail->ItemObj->Name}}
          <!-- <strong style="font-size:14px">{{$detail->ItemObj->Name}}</strong> -->        
          @if (isset($detail->ItemObj->DescriptionEN))
          <br /><label style="font-size:14px;color:#BABABA">{!! $detail->ItemObj->DescriptionEN !!}</label>
          @endif
        </div>
        <div style="float:left;width:10%">
          @if ($detail->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Hotel')
              <strong style="font-size:14px">{{$detail->QtyDay}} Nights</strong></br>
              <strong style="font-size:14px">{{$detail->Qty}} Rooms</strong>
          @else
            <strong style="font-size:14px">{{$detail->QtyAdult}} Adults</strong></br>
            @if ($detail->QtyChild > 0)<strong style="font-size:14px">{{$detail->QtyChild}} Children</strong></br>@endif
            @if ($detail->QtyInfant > 0)<strong style="font-size:14px">{{$detail->QtyInfant}} Infants</strong></br>@endif
          @endif
        </div>
        <!-- <div style="float:right;width:20%;text-align:right"><strong style="font-size:14px">{{$pos->CurrencyObj->Symbol}} {{number_format($detail->SalesTotal, $pos->CurrencyObj->Decimal)}}</strong></div> -->
        @else
        <div style="float:left;width:100%">{{$detail->Name}}</div>
        @endif
        <div style="clear:both"></div>
      </div>
    @endif
  @endforeach
</div>
  
@if ($pos->Source == 'Backend')
<div style="background-color: #EFEFEF;font-weight: bold;padding: 10px 0 10px 20px;font-size: 16px;border-width: 0px 0 0px 0;border-color: #888888;border-style: solid;">Flight Details</div>  
<div style="padding: 10px 20px 10px 20px;">
  <div style="100%;margin-bottom:0px">
  <div style="float:left;width:20%"><strong style="font-size:14px">Flight No.</strong></div>
    <div style="float:left;width:20%"><strong style="font-size:14px">Date Departure</strong></div>
    <div style="float:left;width:20%"><strong style="font-size:14px">ETA</strong></div>
    <div style="float:left;width:20%"><strong style="font-size:14px">From</strong></div>
    <div style="float:left;width:20%"><strong style="font-size:14px">To</strong></div>
    <div style="clear:both"></div>
  </div>
</div>
<hr style="width:90%;color: #bababa; margin: 0px 0 0px 0;" />
<div style="padding: 10px 20px 0px 20px;">
  <div style="100%;margin-bottom:0px">
    <div style="float:left;width:20%;font-size:14px;">{{$pos->TravelTransactionObj->FlightDepartNumber}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{\Carbon\Carbon::parse($pos->TravelTransactionObj->FlightDepartDate)->format('d M Y H:i')}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{gmdate('H:i', $pos->TravelTransactionObj->FlightDepartETA)}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{$pos->TravelTransactionObj->FlightDepartFrom}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{$pos->TravelTransactionObj->FlightDepartTo}}</div>
    <div style="clear:both"></div>
  </div>
</div>
<div style="padding: 0px 20px 10px 20px;">
  <div style="100%;margin-bottom:0px">
    <div style="float:left;width:20%;font-size:14px;">{{$pos->TravelTransactionObj->FlightReturnNumber}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{\Carbon\Carbon::parse($pos->TravelTransactionObj->FlightReturnDate)->format('d M Y H:i')}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{gmdate('H:i', $pos->TravelTransactionObj->FlightReturnETA)}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{$pos->TravelTransactionObj->FlightReturnFrom}}</div>
    <div style="float:left;width:20%;font-size:14px;">{{$pos->TravelTransactionObj->FlightReturnTo}}</div>
    <div style="clear:both"></div>
  </div>
</div>
@endif

<div style="background-color: #EFEFEF;font-weight: bold;padding: 10px 0 10px 20px;font-size: 16px;border-width: 0px 0 0px 0;border-color: #888888;border-style: solid;">
  Contact Detail
</div>
<div style="padding: 13px 20px 13px 20px;">
  <strong style="font-size:16px">{{$pos->ContactName}}</strong></br>
  <div style="font-size:14px;color:#858585">
    Mobile No. +{{$pos->ContactPhone}}</br>
    Email {{$pos->ContactEmail}}
  </div>
</div>

<div style="background-color: #efefef; font-weight: bold; padding: 10px 0 10px 20px; font-size: 16px; border-width: 0px 0 0px 0; border-color: #888888; border-style: solid;">
  Additional Note
</div>
<div style="padding: 13px 20px 13px 20px;">
  <div style="font-size: 14px; color: #858585;">{!! $pos->Note !!}</div>
</div>

</body>
</html>
