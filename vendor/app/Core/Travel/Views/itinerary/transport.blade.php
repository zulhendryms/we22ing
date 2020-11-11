<table class="bordered">
    <tr class="strong text-center">
        <td style="width: 35%">TRANSPORTATION</td>
        <td>DRIVER</td>
        <td style="width: 25%">CONTACT NO</td>
    </tr>
    
    <tr class="border-bottom-none">
    @foreach ($transports as $transport)
        <td>
            {{$transport->ItemObj->BusinessPartnerObj->Name}} / {{ $transport->ItemObj->Name}}
        </td>
        <td></td>
        <td></td>
    @endforeach
    </tr>
</table>