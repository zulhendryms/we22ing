@extends( config('core.base.email.master') ?? 'Core\Base::emails.master' ) 

@section('greeting', __('email.greeting').' '.$user->Name)
@section('title', __('email.welcome.title'))
@section('content')
  <p>{{__('email.confirmation.opening')}}</p>
  <p><a href="#" class="btn" mc:disable-tracking="">{{__('email.confirmation.button')}}</a></p>
  <p>{{__('email.confirmation.ending')}}</p>
@endsection
@section('regards', __('email.regards'))