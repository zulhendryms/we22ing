@extends( config('core.base.email.master') ?? 'Core\Base::emails.master' ) 
@section('content')
  <hr />
  <p class="hero">{{__('email.payment.title1')}}</p>
  <p>{!! __('email.payment.description1') !!}</p>
  <p style="text-align:center">
    {{__('system.bankname')}}: {{$pos->PaymentMethodObj->Name}} <br />
    {{__('system.bankaccountno')}}: {{$pos->PaymentMethodObj->BankNo}} <br />
    {{__('system.bankaccountname')}}: {{$pos->PaymentMethodObj->BankName}}<br />
    {{__('system.bankcode')}}: {{$pos->PaymentMethodObj->BankCode}}<br />
  </p>    
  <p class="hero" style="text-align:center">{{$pos->CurrencyObj->Symbol.' '.number_format($pos->TotalAmount, $pos->CurrencyObj->Decimal)}}</p>
  <p style="text-align:center">{{__('email.payment.expiredate')}}: <strong>{{\Carbon\Carbon::parse($pos->DateExpiry)->tz('Asia/Jakarta')->toDayDateTimeString()}}</strong> <br /></p>
  <hr />
  <p class="hero">{{__('email.payment.title2')}}</p>
  <p>{{__('email.payment.description2')}}</p>
  <p style="text-align:center;"><a href="#" class="btn" mc:disable-tracking="">{{__('email.confirmation.button')}}</a></p>
@endsection
@section('regards', __('email.regards'))