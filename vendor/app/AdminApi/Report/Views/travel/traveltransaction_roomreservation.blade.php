<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$dataReport->reporttitle}}</title>
    <style type="text/css">
    body{
        line-height: normal;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    img {
        display: block;
        margin-left: auto;
        margin-right: auto;
        max-width: 250px;
        max-height: 100px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-bottom: 10px;
    }
    
    table th {
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #ffffff;
      border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975;
      background: #888888;
      font-size: 15px;
      padding-top:5px;
      padding-bottom:5px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #dddddd;
      vertical-align: top;
      font-size: 13px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:5px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    </style>
</head>
<body>
    {{-- <table width="100%">
        <tr>
            <td style="border: none !important" width="10%">
                Report Title </br>
                Date </br></br>
                Start Date </br>
                End Date
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br></br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr> {{--width:675px--}}
              <th class="firstcol" style="width:40px">TOUR CODE</th>
              <th style="width:150px">AGENT GROUP</th>
              <th style="width:150px">AGENT CODE</th>
              <th style="width:150px">AGENT NAME</th>
              <th style="width:100px">GUIDE NAME</th>
              <th style="width:100px">ADT</th>
              <th style="width:100px">CWB</th>
              <th style="width:100px">INF</th>
              <th style="width:100px">EX-BED</th>
              <th style="width:100px">PAX</th>
              <th style="width:100px">CUR</th>
              <th style="width:100px">TOUR FARE</th>
              <th style="width:100px">EX RATE</th>
              <th style="width:100px">TOUR FARE(SGD)</th>
              <th style="width:100px">DI YAT/OTH INCOME</th>
              <th style="width:100px">CHOCO</th>
              <th class="lastcol" style="width:100px">OPTTOUR</th>
            </tr>
          </thead>
          <tbody>
            @php $ItemGroup = ""; @endphp
            @foreach($data as $row)
              @if ($ItemGroup != $row->BusinessPartner)
                <tr>
                  <td colspan="6" class="group"><strong>@php $v = null; try {  $v = $row->BusinessPartnerObj->BusinessPartnerGroupObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp</strong></td>
                </tr>
                @php $ItemGroup = $row->BusinessPartner; @endphp
              @endif
              <tr>
                <td class="firstcol">{{$row->Code}}</td>
                <td> @php $v = null; try {  $v = $row->BusinessPartnerObj->BusinessPartnerGroupObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
                <td> @php $v = null; try {  $v = $row->BusinessPartnerObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
                <td> @php $v = null; try {  $v = $row->BusinessPartnerObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
                <td>{{$row->TourGuide}}</td>
                <td>{{$row->QtyAdult}}</td>
                <td>{{$row->QtyCWB}}</td>
                <td>{{$row->QtyCNB}}</td>
                <td>{{$row->QtyInfant}}</td>
                <td>{{$row->QtyExBed}}</td>
                <td>{{$row->QtyTotalPax}}</td>
                <td> @php $v = null; try {  $v = $row->CurrencyObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
                <td>{{$row->AmountTourFareTotal}}</td>
                <td>{{$row->Rate}}</td>
                <td>{{$row->AmountTourFareTotal}}</td>
                <td>{{$row->IncomeOther}}</td>
                <td>{{$row->IncomeTotalBox}}</td>
                <td class="lastcol">{{$row->OptionalTour1AmountTourBalance}}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

</body>
</html>