<table class="bordered">
    <tr class="strong text-center">
        <td>PERIOD</td>
        <td>HOTEL</td>
        <td>ROOMS</td>
        <td style="width: 25%">REMARK</td>
    </tr>
    
    <tr class="border-bottom-none">
    @foreach ($hotels as $hotel)
        <td>
            {{Carbon::parse($hotel->DateFrom)->format('M\' d')}} - {{Carbon::parse($hotel->DateFrom)->format('d')}}
        </td>
        <td>
            {{$hotel->ItemObj->ParentObj->Name}}
        </td>
        <td>
            {{$hotel->ItemObj->Name}}
        </td>
        <td></td>
    @endforeach
    </tr>
</table>