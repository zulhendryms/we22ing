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
      padding: 15px 10px;
      color: #5D6975;
      border-bottom: 1px solid black;
      white-space: nowrap;
      font-weight: bold; 
      color: #000000;
      border: 1px solid #5D6975;
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid black;
      vertical-align: top;
      font-size: 13px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td style="border: none !important" width="10%">
                Report Title </br>
                Date </br>
                Bank Code </br>
                Start Date </br>
                End Date
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br>
                : Bank</br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}
            </td>
        </tr>
    </table>

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">DATE</th>
                <th width="150px">CHEQUE NO</th>
                <th width="200px">DESCRIPTION 1</th>
                <th width="200px">DESCRIPTION 2</th>
                <th width="150px">IN</th>
                <th width="150px">OUT</th>
                <th width="150px" class="lastcol">BALANCE</th>
            </tr>
        </thead>
        <tbody>
        @php $balance = 0;  @endphp
        @foreach($data as $row)
        <tr>
           <td>{{date('d F y', strtotime($row->Date))}}</td>
           <td>{{$row->JournalType}}</td>
           <td>{{$row->Note}}</td>
           <td>{{$row->Note}}</td>
           <td align="right">{{number_format($row->DebetAmount ,2)}}</td>
           <td align="right">{{number_format($row->CreditAmount ,2)}}</td>
           @php
               $balance  += $row->DebetAmount - $row->CreditAmount;
           @endphp
           <td align="right">{{number_format($balance ,2)}}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    
    
    
</body>
</html>