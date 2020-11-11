<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$dataReport->reporttitle}}</title>
    <style type="text/css">

    body:lang(zh){
      font-family: 'WenQuanYi Zen Hei';
      font-weight:normal;
      font-style:normal;
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
                Tour Code </br>
                From Date </br>
                To Date </br>
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
                <th width="80px" class="firstcol">INVOICE NO</th>
                <th width="80px">INVOICE DATE</th>
                <th width="120px">STATUS</th>
                <th width="120px">TOUR CODE</th>
                <th width="120px">EO NUMBER</th>
                <th width="120px">DEPARTURE DATE</th>
                <th width="120px">SALES PERSON NAME</th>
                <th width="120px">CUSTOMER NAME</th>
                <th width="120px">ADDRESS</th>
                <th width="120px">CONTRACT PERSON</th>
                <th width="120px">CONTACT NO</th>
                <th width="120px">INV. AMT</th>
                <th width="120px">DEPOSIT AMT</th>
                <th width="120px">PAID AMT</th>
                <th width="120px">BAL. AMT/th>
                <th width="100px" class="lastcol">REASON FOR CANCEL</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Code}}</td>
            <td>{{date('d/m/y', strtotime($row->Date))}}</td>
            <td>{{$row->Status}}</td>
            {{-- <td>{{$row->QtyCNB}}</td>
            <td>{{date('d/m/y', strtotime($row->))}}</td>
            <td>{{$row->QtyInfant}}</td>
            <td>{{$row->QtyTL}}</td>
            <td>{{$row->QtyExBed}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>balance</td>
            <td>{{$row->Rate}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>balanceSGD</td>
            <td>{{$row->Remark}}</td> --}}
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>