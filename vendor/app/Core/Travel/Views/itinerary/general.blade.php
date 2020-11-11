<table class="bordered">
    <tr>
        <td rowspan="2" style="width: 15%;" class="vtop border-right-none">
            Ref
        </td>
        <td rowspan="2" class="vtop border-left-none">
            : {{$pos->Code}}
        </td>
        <td rowspan="2" class="text-center strong" style="font-size: 18px">
            CSH
        </td>
        <td rowspan="2" style="width: 30%">
            Periode:</br>
            {{Carbon::parse($travelTransaction->DateFrom)->format('d M Y')}} - {{Carbon::parse($travelTransaction->DateUntil)->format('d M Y')}}
        </td>
        <td class="text-center strong" style="background:gray; color: white" colspan="4">
            NO OF PAX
        </td>
    </tr>
    <tr class="text-center">
        <td>Adult</td>
        <td>Child</td>
        <td>Infant</td>
        <td>TL</td>
    </tr>
    <tr>
        <td class="border-right-none">Guide</td>
        <td colspan="3" class="border-left-none">: @isset($travelTransaction->EmployeeObj) {{$travelTransaction->EmployeeObj->Name}} ({{$travelTransaction->EmployeeObj->Phone}}) @endisset</td>
        <td class="text-center">{{$travelTransaction->QtyAdult}}</td>
        <td class="text-center">{{$travelTransaction->QtyChild}}</td>
        <td class="text-center">{{$travelTransaction->QtyInfant}}</td>
        <td class="text-center">1</td>
    </tr>
    <tr class="border-bottom-none">
        <td class="border-right-none">Guest / TL</td>
        <td colspan="7" class="border-left-none">: @isset($pos->BusinessPartnerObj) {{$pos->BusinessPartnerObj->Name}} @endisset</td>
    </tr>
</table>