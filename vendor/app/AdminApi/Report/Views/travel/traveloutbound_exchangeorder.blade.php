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
                Staff Team
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
                <th width="80px" class="firstcol">EO No</th>
                <th width="80px">EO DATE</th>
                <th width="120px">SUPPLIER NAME</th>
                <th width="120px">OUTBOUND INV NO</th>
                <th width="120px">CUSTOMER NAME</th>
                <th width="120px">GROSS AMT</th>
                <th width="120px">DISC. AMT</th>
                <th width="120px">SUB AMT</th>
                <th width="120px">GST AMT</th>
                <th width="120px">NETT AMT</th>
                <th width="120px">REMARK</th>
                <th width="100px" class="lastcol">STATUS</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->BusinessPartnerObj->Name}}</td>
            <td>{{$row->Code}}</td>
            <td>{{date('d/m/y', strtotime($row->DateFrom))}}</td>
            <td>{{$row->QtyAdult}}</td>
            <td>{{$row->QtyCWB}}</td>
            <td>{{$row->QtyCNB}}</td>
            <td>{{$row->QtyInfant}}</td>
            <td>{{$row->QtyTL}}</td>
            {{-- <td>{{$row->Details->PurchaseCurrencyObj->Code}}</td> --}}
            <td>{{$row->QtyExBed}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>balance</td>
            <td>{{$row->Rate}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>balanceSGD</td>
            <td>{{$row->Remark}}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>