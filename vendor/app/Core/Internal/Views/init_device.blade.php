@if (is_null(request()->cookie(config('constants.device_id'))))
    <script type="text/javascript">
        $.get('{{route('Core\Internal::device')}}')
    </script>
@endif