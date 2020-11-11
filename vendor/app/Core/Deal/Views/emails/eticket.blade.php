@extends( config('core.base.email.master') ?? 'Core\Base::emails.master' ) 

@section('greeting', __('email.greeting').' '.$user->Name)
@section('title', __('email.purchase.title'))
@section('content')
  <p>{{__('email.purchase.opening')}}</p>
  <hr />
  @if (isset($pos->DescriptionSummary))
    <p><strong>>{{__('transacton.detail')}}</strong></p>
    <p>{!! $pos->DescriptionSummary !!}</p>
    <hr />
  @endif  
  @if (isset($pos->DealTransactionObj->POSItemServiceObj->DescTermConditionEN))
    <p><strong>{{__('item.term')}}</strong></p>
    <p>{!! $pos->DealTransactionObj->POSItemServiceObj->DescTermConditionEN !!}</p>
    <hr />
  @endif  
  <p>{{__('email.purchase.ending')}}</p>
@endsection
@section('regards', __('email.regards'))