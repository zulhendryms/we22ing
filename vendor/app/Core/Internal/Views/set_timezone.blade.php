<script type="text/javascript">
    $.get('{{route('Core\Internal::timezone')}}', { value: -new Date().getTimezoneOffset()/60 })
</script>