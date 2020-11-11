<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>E-Ticket Attraction</title></head>
<body style="margin:0; font-family:Verdana, Geneva, sans-serif">
  <div style="background-color: {{config('colors.base')}};padding:18px 15px 0px 15px;text-align: left;color:#fff;">
    <img src="{{company_logo(1)}}" width="120" />
    <div style="float:right"><div style="font-size: 28px;font-weight: bold;">E-TICKET</div></div>
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
        <div style="font-size:12px;color:#858585">Issued at: {{\Carbon\Carbon::parse($pos->DatePayment)->tz('Asia/Jakarta')->toDayDateTimeString()}}</div>
      </div>
      <div style="font-size: 12px; float:right;padding: 8px 15px 8px 14px;color: #fff;font-weight: bold;background-color: {{config('colors.base')}};border-radius: 8px;">{{strtoupper($pos->StatusObj->Name)}}</div>
    </div>
    <div style="clear:both"></div>
  </div>
  <div style="background-color: #EFEFEF;font-weight: bold;padding: 10px 0 10px 20px;font-size: 16px;border-width: 0px 0 0px 0;border-color: #888888;border-style: solid;">Booking Details</div>
  {{-- <div style="padding: 13px 20px 13px 20px;">
    <div>
        @php
            $item = $pos->DealTransactionObj->POSItemServiceObj->ItemObj;
        @endphp
        <strong style="font-size: 16px;display:block;margin-top:8px;margin-right:10px">
          {{$item->Name}}
        </strong>
        <label style="font-size:14px;color:#BABABA">
            {{$item->PurchaseBusinessPartnerObj->Name}}
         </label>
    </div>
  </div> --}}

<div style="padding: 20px 20px 20px 20px;">
  @foreach(( $details ?? $pos->TravelTransactionDetails) as $key => $detail)
    @php
        $terms = $detail->ItemObj->ParentObj->POSItemServiceObj->DescTermConditionEN;
    @endphp
    <div style="100%;margin-bottom:15px">
      <div style="float:left;width:60%">
        <strong style="font-size:14px">{{$detail->ItemObj->Name}} @isset($date) ({{$date}}) @endisset</strong>
        @if (isset($detail->ItemObj->DescriptionEN))
        <br /><label style="font-size:14px;color:#BABABA">{!! $detail->ItemObj->DescriptionEN !!}</label>
        @endif
      </div>
      <div style="float:left;width:10%">
        @if ($detail->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Hotel')
            <!-- <strong style="font-size:14px">{{$detail->ItemHotelObj->QtyAdult}} max. guests</strong></br> -->
            @if ($detail->ItemObj->ParentObj->ETicketMergeType == 1)
              <strong style="font-size:14px">{{$detail->QtyDay}} nights</strong></br>
              <strong style="font-size:14px">1 rooms</strong>
            @else
              <strong style="font-size:14px">{{$detail->QtyDay}} nights</strong></br>
              <strong style="font-size:14px">{{$detail->Qty}} rooms</strong>
            @endif
        @else
          @if ($detail->ItemObj->ParentObj->ETicketMergeType == 1)
            <strong style="font-size:14px">{{$qty}}</strong></br>
          @else
            <strong style="font-size:14px">{{$detail->QtyAdult}} Adults</strong></br>
            @if ($detail->QtyChild > 0)<strong style="font-size:14px">{{$detail->QtyChild}} Children</strong></br>@endif
            @if ($detail->QtyInfant > 0)<strong style="font-size:14px">{{$detail->QtyInfant}} Infants</strong></br>@endif
          @endif
        @endif
      </div>
      <!-- <div style="float:right;width:20%;text-align:right">
        <strong style="font-size:14px">{{$pos->CurrencyObj->Symbol}} {{number_format($detail->SalesTotal, $pos->CurrencyObj->Decimal)}}</strong>
      </div> -->
      <div style="clear:both"></div>
    </div>
  @endforeach
</div>
<hr style="color: #bababa; margin: 15px 0 15px 0;" />
  <!-- <div style="font-size: 14px;">
    <div style="float: left;"><strong>Subtotal</strong></div>
    <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->SubtotalAmount, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
    <div style="clear: both;">&nbsp;</div>
    @if (isset($pos->DiscountAmount))
      <div style="float: left;"><strong>Discount</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->DiscountAmount, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif @if (isset($pos->ConvenienceAmount))
      <div style="float: left;"><strong>Convinience Fee</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->ConvenienceAmount, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif @if (isset($pos->AdditionalAmount))
      <div style="float: left;"><strong>Additional Fee</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->AdditionalAmount, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif @if (isset($pos->AdmissionAmount))
      <div style="float: left;"><strong>Admission Fee</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->AdmissionAmount, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif
    <hr style="color: #bababa; margin: 10px 0 10px 0;" />
    <div style="float: left;"><div style="font-size: 22px; font-weight: bold;">Total</div></div>
    <div style="float: right;">
      <div style="color: {{config('colors.base')}}; font-size: 22px; font-weight: bold;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->TotalAmount, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
    </div>
    <div style="clear: both;">&nbsp;</div>
    <br />
    @if ($pos->Currency != company()->Currency)
      <div style="float: right; fontsize: 9; color: gray; textalign: right; fontstyle: italic;">Est. in {{$pos->CurrencyObj->Symbol}} {{number_format($pos->TotalAmountDisplay, $pos->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif
  </div> -->

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
  Terms and Condition  
</div>
<div style="padding: 13px 20px 13px 20px;">
  <div style="font-size: 14px; color: #858585;">{!! $terms !!}</div>
</div>


</body>
</html>
