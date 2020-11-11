@extends( config('core.base.email.master') ?? 'Core\Base::emails.master' ) 

@section('greeting', __('email.greeting').' '.$user->Name)
@section('title', __('email.welcome.title'))
@section('content')
  <p>{{__('email.welcome.opening')}}</p>
@endsection
@section('regards', __('email.regards'))