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
            <td style="border: none !important" width="15%">
                Report Title </br>
                Date </br>
                Transaction No</br>
                Start Date </br>
                End Date </br>
                Status </br>
                Company
            </td>
            <td style="border: none !important" width="85%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br>
                : </br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}</br>
                : </br>
                : {{$dataReport->CompanyObj->Name}}
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">TRASNSACTION NUMBER</th>
                <th width="80px">TRANSACTION DATE</th>
                <th width="80px">TRANSACTION TYPE</th>
                <th width="200px">REMARKS</th>
                <th width="200px">DESCRIPTION</th>
                <th width="100px">AMOUNT</th>
                <th width="100px" class="lastcol">BUSINESS UNIT</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Code}}</td>
            <td>{{date('d F y', strtotime($row->TransactionDate))}}</td>
            <td>{{$row->OrderType}}</td>
            <td>{{$row->Remark}}</td>
            <td>{{$row->PurchaseDescription}}</td>
            <td align="right">{{number_format($row->PurchaseAmount,2)}}</td>
            <td> @php $v = null; try {  $v = $row->TravelTypeObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>