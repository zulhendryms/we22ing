<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>E-Ticket Attraction</title>
</head>
<body style="margin:0; font-family:Verdana, Geneva, sans-serif">
  <div style="background-color: #A00000;padding:18px 15px 0px 15px;text-align: left;color:#fff;">
    <img src="{{company_logo(1)}}" width="120" />
    <div style="float:right">
      <div style="font-size: 28px;font-weight: bold;">E-TICKET</div>
    </div>
    <div style="clear:both"></div><br />
  </div>
  <div style="padding: 13px 20px 13px 20px;">
    <div>
      <div style="float:left">
        <strong style="font-size: 14px;margin:0 0 5px 0;color:#BABABA;">
          Booking Id: </strong><br />
          <strong style="font-size: 20px; margin: 0 0 5px 0; color: #a00000;">
            {{$pos->Code}}
          </strong><br />
          @if ($pos->CodeReference)
            <strong style="font-size: 20px; margin: 0 0 5px 0; color: #a00000;">
              Booking No. {{$pos->CodeReference}}
            </strong><br />
          @endif
        <div style="font-size:12px;color:#858585">
            Issued at: {{\Carbon\Carbon::parse($pos->DatePayment)->tz('Asia/Jakarta')->toDayDateTimeString()}}
        </div>
      </div>
      <div style="font-size: 12px; float:right;padding: 8px 15px 8px 14px;color: #fff;font-weight: bold;background-color: #a00000;border-radius: 8px;">
        {{strtoupper($pos->StatusObj->Name)}}
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
  <div style="background-color: #EFEFEF;font-weight: bold;padding: 10px 0 10px 20px;font-size: 16px;border-width: 0px 0 0px 0;border-color: #888888;border-style: solid;">
    Booking Details
  </div>
  <div style="padding: 13px 20px 13px 20px;">
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
  </div>

<div style="padding: 20px 20px 20px 20px;">
  @foreach($pos->Details as $key => $detail)
    @php
      if (empty($pos->APIType)) {
        if (!in_array($detail->ItemObj->ParentObj->APIType, $apiTypes)) continue;
      }
    @endphp
    <div style="100%;margin-bottom:15px">
      <div style="float:left;width:60%">
        <strong style="font-size:14px">{{$detail->ItemObj->Name}}</strong><br />
        <label style="font-size:14px;color:#BABABA">
            {{$detail->ItemObj->DescriptionEN}}
         </label>
      </div>
      <div style="float:left;width:10%">
        <strong style="font-size:14px">{{$detail->Quantity}} pcs</strong>
      </div>
      <div style="float:right;width:20%;text-align:right">
        <strong style="font-size:14px">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($detail->Amount, $pos->CompanyObj->CurrencyObj->Decimal)}}</strong>
      </div>
      <div style="clear:both"></div>
    </div>
  @endforeach
</div>
<hr style="color: #bababa; margin: 15px 0 15px 0;" />
  <div style="font-size: 14px;">
    <div style="float: left;"><strong>Subtotal</strong></div>
    <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->SubtotalAmountBase, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
    <div style="clear: both;">&nbsp;</div>
    @if (isset($pos->DiscountAmountBase))
      <div style="float: left;"><strong>Discount</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->DiscountAmountBase, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif @if (isset($pos->ConvenienceAmountBase))
      <div style="float: left;"><strong>Convinience Fee</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->ConvenienceAmountBase, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif @if (isset($pos->AdditionalAmountBase))
      <div style="float: left;"><strong>Additional Fee</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->AdditionalAmountBase, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif @if (isset($pos->AdmissionAmountBase))
      <div style="float: left;"><strong>Admission Fee</strong></div>
      <div style="float: right;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->AdmissionAmountBase, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif
    <hr style="color: #bababa; margin: 10px 0 10px 0;" />
    <div style="float: left;"><div style="font-size: 22px; font-weight: bold;">Total</div></div>
    <div style="float: right;">
      <div style="color: #a00000; font-size: 22px; font-weight: bold;">{{$pos->CompanyObj->CurrencyObj->Symbol}} {{number_format($pos->TotalAmountBase, $pos->CompanyObj->CurrencyObj->Decimal)}}</div>
    </div>
    <div style="clear: both;">&nbsp;</div>
    <br />
    @if ($pos->Currency != company()->Currency)
      <div style="float: right; fontsize: 9; color: gray; textalign: right; fontstyle: italic;">Est. in {{$pos->CurrencyObj->Symbol}} {{number_format($pos->TotalAmount, $pos->CurrencyObj->Decimal)}}</div>
      <div style="clear: both;">&nbsp;</div>
    @endif
  </div>

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
  <div style="font-size: 14px; color: #858585;"></div>
</div>


</body>
</html>
