@extends( config('core.base.email.master') ?? 'Core\Base::emails.master' ) 

@section('content')
  <p style="text-align:center"><strong>{{__('email.businesspartner.title')}}</strong></p>
  <p class="hero" style="text-align:center">No: {{$pos->Code}}</p><br />
  <p>{{__('email.greeting').' '.$pos->SupplierObj->Name.','}}</p>
  <p>{{__('email.businesspartner.opening')}}</p>
  <hr />
  <table>
    <tr><td>Guest Name</td><td>: {{$pos->ContactName}}</td></tr>
    <tr><td>Paid Date</td><td>: {{$pos->DatePayment}}</td></tr>
    <tr><td>Order Details </td><td>: <strong>{{$pos->DealTransactionObj->POSItemServiceObj->Name}}</strong></td></tr>
  </table>  
  @if (isset($pos->DescriptionSummary))
    <p>{!! $pos->DescriptionSummary !!}</p>
  @endif
  <hr />
  @if ($pos->Details->count() != 0)
    <table style="padding:10px">
      <tr><td class="tdfirst"><strong>Item</strong></td><td class="tdlast" style="text-align:right"><strong>Quantity</strong></td></tr>
      @foreach ($pos->Details as $detail)    
          <tr><td class="tdfirst">{{$detail->ItemObj->Subtitle}}</td><td class="tdlast" style="text-align:right">{{number_format($detail->Quantity)}} pcs</td></tr>      
      @endforeach
    </table>
  @endif
  <hr />  
  <p>{{__('email.businesspartner.ending')}}</p>
  <p style="text-align:center;"><a href="#" class="btn" mc:disable-tracking="">{{__('email.businesspartner.button')}}</a></p>  
@endsection
@section('regards', __('email.regards'))