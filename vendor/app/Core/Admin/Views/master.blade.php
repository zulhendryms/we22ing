<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Administrator</title>
    <link rel="stylesheet" type="text/css" href="{{asset(mix('css/admin.css'))}}" />
    @yield('styles')
  </head>
  <body>
    @include('Core\Admin::navbar')
    @yield('content')
    <script type="text/javascript" src="{{asset(mix('js/admin.js'))}}"></script>
    @yield('scripts')
  </body>
</html>