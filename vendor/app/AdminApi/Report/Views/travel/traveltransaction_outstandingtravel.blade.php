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

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">Company</th>
                <th width="80px">Code</th>
                <th width="50px">Date From</th>
                <th width="50px">Date Until</th>
                <th width="200px">Customer</th>
                <th width="70px">Qty Total Pax</th>
                <th width="70px">Tour Fare Total</th>
                <th width="70px" class="lastcol">Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Company}}</td>
            <td>{{$row->Code}}</td>
            <td>{{$row->DateFrom}}</td>
            <td>{{$row->DateUntil}}</td>
            <td>{{$row->Customer}}</td>
            <td align="center">{{$row->QtyTotalPax}}</td>
            <td align="right">{{number_format($row->AmountTourFareTotal,2)}}</td>
            <td>{{$row->Status}}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>