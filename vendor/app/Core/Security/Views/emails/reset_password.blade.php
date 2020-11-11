@extends( config('core.base.email.master') ?? 'Core\Base::emails.master' ) 

@section('greeting', __('email.greeting').' '.$user->Name)
@section('title', __('email.welcome.title'))
@section('content')
  <p>{{__('email.reset_password.opening')}}</p>
  <p style="text-align:center;background:#ccc;padding:10px">
        <strong>{{$user->ResetCode}}</strong>
    </p>
  <p>{{__('email.reset_password.ending')}}</p>
@endsection
@section('regards', __('email.regards'))