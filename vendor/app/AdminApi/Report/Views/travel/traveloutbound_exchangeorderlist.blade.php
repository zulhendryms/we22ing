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
                Sales Person Name
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br></br>
                : </br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}} </br>
                :
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">EXCHANGE ORDER NO</th>
                <th width="80px">EXCHANGE ORDER DATE</th>
                <th width="120px">SUPPLIER NAME</th>
                <th width="120px">INVOICE NUMBER</th>
                <th width="120px">ITEM DESC LINE1</th>
                <th width="120px">ITEM DESC LINE2</th>
                <th width="120px">QUANTITY</th>
                <th width="120px">UNIT PRICE</th>
                <th width="100px" class="lastcol">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Code}}</td>
            <td>{{date('d/m/y', strtotime($row->Date))}}</td>
            <td>{{$row->BusinessPartnerObj->Name}}</td>
            <td>{{$row->Code}}</td>
            <td>{{$row->ItemObj->Name}}</td>
            <td>{{$row->ItemObj->Name}}</td>
            <td>{{$row->Quantity}}</td>
            <td>{{$row->Price}}</td>
            <td>{{$row->Amount}}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>