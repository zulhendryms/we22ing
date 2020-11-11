<form method="post">
    <textarea name="log" id="" cols="30" rows="10"></textarea> </br>
    {{ csrf_field() }}
    <input type="submit" value="Decrypt"> </br>
    @if(!is_null($result))
        Address: {{ $result['address'] }} </br>
        Private Key: {{ $result['private_key'] }}
    @endif
</form>