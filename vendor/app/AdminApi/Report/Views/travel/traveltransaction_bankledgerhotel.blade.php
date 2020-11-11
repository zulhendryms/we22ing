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
                Hotel Name </br>
                Start Date </br>
                End Date
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br></br>
                : </br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr>
                <th width="20px" class="firstcol">No</th>
                <th width="80px">HOTEL</th>
                <th width="50px">TOUR CODE</th>
                <th width="120px">HOTEL NAME</th>
                <th width="120px">PAYMENT DATE</th>
                <th width="100px">TOTAL AMOUNT</th>
                <th width="30px" class="lastcol">STAFF</th>
            </tr>
        </thead>
        <tbody>
            @php
                $count=1;
            @endphp
        @foreach($data as $row)
        <tr>
            <td class="firstcol">{{$count}}</td>
            <td> @php $v = null; try {  $v = $row->TravelTransactionDetails->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td>{{$row->Code}}</td>
            <td> @php $v = null; try {  $v = $row->TravelTransactionDetails->Description; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td>{{date('d/m/y', strtotime($row->TransactionDate))}}</td>
            <td align="right">{{number_format($row->TotalAmount,2)}}</td>
            <td> @php $v = null; try {  $v = $row->UserObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
        </tr>
        @php
            $count++; 
        @endphp
        @endforeach
        </tbody>
    </table>

</body>
</html>