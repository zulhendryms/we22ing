<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Service Voucher</title>
</head>
<style type="text/css">

body{
    font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
    line-height: normal;
}
.bold{
    font-weight: 600;
}
.f12{
    font-size: 12pt;
}
.f9{
    font-size: 9pt;
}
.alignM {
        text-align: center;
      }
.floatL{
    float: left;
}
.floatR{
    float: right;
}
.nw{
    font-size: 10pt;
    white-space: normal;
    width: 50%;
}
.col2{
    column-count: 2;
    white-space: normal;
    margin-left: 40px;
    height: 200px;
    
}

</style>
@if($data[0]->CompanyLogo)
<header>
    <span><img src="{{$data[0]->CompanyLogo}}" width="100%" height="auto"></span>
</header>
@endif
<body>
    @php $businessPartner = ''; @endphp
    @foreach ($data as $row) 
    @if ($businessPartner != $row->BusinessPartner) 
    @if ($businessPartner != '')
    <table width="100%" style="min-height: 50px; border: 1px solid black; page-break-inside: avoid; margin-bottom: -15px;">
        <tr>
            <td>
                <p style="padding-left: 15px;">GUEST NAME :<br>
                    @php $count = 0; @endphp
                    <span>
                        @foreach ($dataPax as $rowPax)
                        {{$count == 0 ? "" : ", "}} {{$rowPax->Name}}
                        @php
                                $count++
                                @endphp
                        @endforeach
                    </span>
                </p>
            </td>
        </tr>
            </table>
            <table width="100%" style="min-height: 60px; border: 1px solid black; page-break-inside: avoid; margin-bottom: -15px;">
                <tr><br>
                    <td style="padding-left:20px;font-size: 11pt;">
                        Hotel Inclusion :
                        {{$row->HotelInclusion}}
                    </td>
                </tr>
            </table>
            <table width="100%" style="min-height: 60px; border: 1px solid black; page-break-inside: avoid;">
                <tr><br>
                    <td style="padding-left:20px;font-size: 11pt;">
                        HOTEL NOTE :
                            @if($row->HotelNote)
                            {{$row->HotelNote}}
                            @endif
                    </td>
                </tr>
            </table>
            <table width="100%" style="page-break-inside: avoid;">
                <td width="50%" style="font-size: 9pt">
                    <b class="f12">PLEASE NOTE</b><br>
                        This voucher need to be producee to the guide or hotel counter upon request when necessary. &nbsp;
                        This voucher has no monetary value and therefore cannot be exchanged for cash if it not utilized. &nbsp;
                        This voucher is not transferable and should there be any amount excess the value of this voucher will be paid by holder. &nbsp;
                        Please ensure your passport is valid at least six month from the date od expiry. &nbsp;
                        Should you have anyamendments or enquires regarding your reservation; you can contact us by phone, fax, or e-mail. &nbsp;
                        Cancellation charges will be applied once booking is confirmed.
                </td>
                <td width="50%" style="font-size: 9pt">
                    <u><b>Terms & Conditions for Airport Transfer Service:</b></u><br>
                        Upon exit at arrival, please look for <u><b>ACETOURS LOGO</b></u>or 
                        <u><b>GUEST NAME</b></u> sign board for transfer service. &nbsp;
                        Pick-up driver will only wait from the time airplane touches down till the luggage belt is clear. &nbsp;
                        Luggage allowance per pax is 01 piece. Extra luggage is chargable (S$5 nett per piece).
                </td>
            </table>
                <table width="100%" style="page-break-inside: avoid;">
                    <td width="30%" style="font-size: 9pt">
                            <b>For Emergency Purpose, please contact:</b>
                            <br>
                            <span>Inbound: +65 6438 2811/ Outbound: +65 6533 6911</span>
                            <br>
                            <span>Office Hours: (09:00 - 18:00) (Monday-Friday)</span>
                            <br>
                        </p>
                    </td>
                    <td width="70%" style="font-size: 9pt">
                            <span>Staff: {{$row->User}} &nbsp;&nbsp; Mobile: {{$row->UserPhone}}</span>
                            <br>
                            <span>(After Office Hours/Sunday/Public Holiday)</span>
                            <br>
                            <span>Emergency Email : inb@acetours.sg</span>
                    </td>
                </table>
                <div style="padding-bottom:150px; page-break-inside: avoid;">
                    <p class="floatR bold f12">
                        <span>ACE TOURS & TRAVEL PTE LTD</span>
                        <br>
                        <br>
                        <br>
                        ________________________
                        <br>
                        Authorized Signature & Stamp
                    </p>
                </div>
                <div style="page-break-after:always;">&nbsp;</div>
            @endif

            <h1 class="bold alignM">SERVICE VOUCHER</h1>
            <main>
            <table width="100%">
                <tr>
                    <td class="bold" width="80%">TO <span>: {{$row->BPartner}}<br> 
                        {{$row->Address}}<br>
                        TEL : {{$row->PhoneNo}}
                    </span>
                    </td>
                    <td class="bold" width="20%">DATE <span>: {{$row->Date}}</span>
                        <br>
                        <span>STAFF : {{$row->User}}</span>
                    </td>
                </tr>
            </table>
            <hr>
        @endif
        <table width="100%" style="padding-bottom: 25px; font-size: 10pt">
            <tbody>
                <tr>
                    <td width="80px">TOUR CODE</td>
                    <td width="250px">: {{$row->TourCode}}</td>
                    <td width="100px">CONFIRMATION NO </td>
                    <td width="50px">: {{$row->CodeReff}}</td>
                </tr>
                <tr>
                    <td>CHECK IN DATE</td>
                    <td>: {{$row->DateFrom}}</td>
                    <td>ARRIVAL FLIGHT</td>
                    <td>: @if($dataFlight[0]->FlightCode){{$dataFlight[0]->FlightCode}}@endif</td>
                </tr>
                <tr>
                    <td>CHECK OUT DATE</td>
                    <td>: {{$row->DateUntil}}</td>
                    <td>DEPARTURE FLIGHT</td>
                    <td>: @if($dataFlight[1]->FlightCode){{$dataFlight[1]->FlightCode}}@endif</td>
                </tr>
                <tr>
                    <td>ROOM</td>
                    <td>: {{$row->RoomType}}</td>
                    <td valign="top">ROOM REMARKS</td>
                    <td align="justify">: {!! $row->Description !!}</td>
                </tr>
            </tbody>
        </table>

        @php 
            $businessPartner = $row->BusinessPartner;
        @endphp
    @endforeach
            
    <table width="100%" style="min-height: 50px; border: 1px solid black; page-break-inside: avoid; margin-bottom: -15px;">
        <tr>
            <td>
            <p style="padding-left: 15px;">GUEST NAME :<br>
                @php $count = 0; @endphp
                <span>
                @foreach ($dataPax as $rowPax)
                    {{$count == 0 ? "" : ", "}} {{$rowPax->Name}}
                    @php
                        $count++
                    @endphp
                @endforeach
                </span>
            </p>
        </td>
    </tr>
    </table>
    <table width="100%" style="min-height: 50px; border: 1px solid black; page-break-inside: avoid; margin-bottom: -15px;">
        <tr><br>
            <td style="padding-left:20px;font-size: 11pt;">
                Hotel Inclusion :
                {{$row->HotelInclusion}}
            </td>
        </tr>
    </table>
    <table width="100%" style="min-height: 50px; border: 1px solid black; page-break-inside: avoid;">
        <tr><br>
            <td style="padding-left:20px;font-size: 11pt;">
                HOTEL NOTE :
                    @if($row->HotelNote)
                    {{$row->HotelNote}}
                    @endif
            </td>
        </tr>
    </table>
    <table width="100%" style="page-break-inside: avoid;">
        <td width="50%" style="font-size: 9pt">
            <b class="f12">PLEASE NOTE</b><br>
                This voucher need to be producee to the guide or hotel counter upon request when necessary. &nbsp;
                This voucher has no monetary value and therefore cannot be exchanged for cash if it not utilized. &nbsp;
                This voucher is not transferable and should there be any amount excess the value of this voucher will be paid by holder. &nbsp;
                Please ensure your passport is valid at least six month from the date od expiry. &nbsp;
                Should you have anyamendments or enquires regarding your reservation; you can contact us by phone, fax, or e-mail. &nbsp;
                Cancellation charges will be applied once booking is confirmed.
        </td>
        <td width="50%" style="font-size: 9pt">
            <u><b>Terms & Conditions for Airport Transfer Service:</b></u><br>
                Upon exit at arrival, please look for <u><b>ACETOURS LOGO</b></u>or 
                <u><b>GUEST NAME</b></u> sign board for transfer service. &nbsp;
                Pick-up driver will only wait from the time airplane touches down till the luggage belt is clear. &nbsp;
                Luggage allowance per pax is 01 piece. Extra luggage is chargable (S$5 nett per piece).
        </td>
    </table>
        <table width="100%" style="page-break-inside: avoid;">
            <td width="30%" style="font-size: 9pt">
                    <b>For Emergency Purpose, please contact:</b>
                    <br>
                    <span>Inbound: +65 6438 2811/ Outbound: +65 6533 6911</span>
                    <br>
                    <span>Office Hours: (09:00 - 18:00) (Monday-Friday)</span>
                    <br>
                </p>
            </td>
            <td width="70%" style="font-size: 9pt">
                    <span>Staff: {{$row->User}} &nbsp;&nbsp; Mobile: {{$row->UserPhone}}</span>
                    <br>
                    <span>(After Office Hours/Sunday/Public Holiday)</span>
                    <br>
                    <span>Emergency Email : inb@acetours.sg</span>
            </td>
        </table>
        <div style="padding-bottom:150px; page-break-inside: avoid;">
            <p class="floatR bold f12">
                <span>ACE TOURS & TRAVEL PTE LTD</span>
                <br>
                <br>
                <br>
                ________________________
                <br>
                Authorized Signature & Stamp
            </p>
        </div>
        </main>
    </main>
</body>
</html>