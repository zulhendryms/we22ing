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
                Attraction </br>
                From Date </br>
                To Date </br>
                Staff Team
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br></br>
                : </br>
                : </br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">團號</th>
                <th width="80px">经纪人</th>
                <th width="120px">代理集团</th>
                <th width="120px">航班</th>
                <th width="120px">酒店名稱</th>
                <th width="120px">房數</th>
                <th width="120px">幾晚</th>
                <th width="120px">大人</th>
                <th width="120px">小孩佔床</th>
                <th width="120px">加床</th>
                <th width="120px">小孩不佔</th>
                <th width="120px">嬰兒</th>
                <th width="120px">明細</th>
                <th width="120px">團費收人</th>
                <th width="120px">新幣支出</th>
                <th width="120px">手续费</th>
                <th width="120px">匯率</th>
                <th width="120px">台幣支出</th>
                <th width="120px">佣金</th>
                <th width="120px">利潤</th>
                <th width="100px" class="lastcol">備註</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Code}}</td>
            <td> @php $v = null; try {  $v = $row->BusinessPartnerObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->BusinessPartnerObj->BusinessPartnerGroupObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->TravelFlightNumberObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td>{{$row->QtyTotalPax}}</td>
            <td>{{$row->QtyTotalPax}}</td>
            <td>{{$row->QtyAdult}}</td>
            <td>{{$row->QtyExBed}}</td>
            <td>{{$row->QtyChild}}</td>
            <td>{{$row->QtyInfant}}</td>
            <td>{{$row->PurchaseTotal}}</td>
            <td>{{$row->PurchaseTotal}}</td>
            <td>{{$row->Quantity}}</td> 
            <td>{{$row->AmountAgentCommission}}</td>
            <td>{{$row->ExpenseTourGuideFee}}</td>
            <td>{{$row->IncomeTourGuide}}</td>
            <td>{{$row->Rate}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>{{$row->AmountTourFareTotal}}</td>
            <td>{!! $row->Note !!}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>