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
                Date </br>
                Tour Code </br>
                Arrival From </br>
                Arrival To </br>
                Status
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br>
                : </br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}</br>
                : 
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">TOUR CODE</th>
                <th width="80px">SOURCE</th>
                <th width="120px">DATE</th>
                <th width="120px">DESCRIPTION</th>
                <th width="100px">CURR</th>
                <th width="100px">GROSS AMT.</th>
                <th width="250px">GST%</th>
                <th width="120px">GST AMT.</th>
                <th width="200px">NETT AMT.</th>
                <th width="100px" class="lastcol">REMARK</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Code}}</td>
            <td> @php $v = null; try {  $v = $row->OrderType; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td>{{date('d/m/y', strtotime($row->Date))}}</td>
            <td>{{$row->Description}}</td>
            <td> @php $v = null; try {  $v = $row->PurchaseCurrencyObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td align="right">{{number_format($row->SalesTotal,2)}}</td>
            @php $gst = 7; @endphp
            <td align="right">{{number_format($row->IsGSTApplicable,2) == 1 ? $gst : 0}}</td>
            <td align="right">0</td>
            <td align="right">0</td>
            <td align="right">{!! $row->Note !!}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>