<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>tour-agreement</title>
  <style type="text/css">
    @page { margin: 110px 25px; }
    p { margin-top: 0px; }
    p:last-child { page-break-after: never; }
    body:lang(zh){
      font-family: 'WenQuanYi Zen Hei';
      font-weight:normal;
      font-style:normal;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-bottom: 20px;
    }
    
    table th {
      padding: 15px 10px;
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #000000;
      border: 1px solid #5D6975;
      /* border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975; */
      /* background: #888888; */
      font-size: 10pt;
      padding-top:10px;
      padding-bottom:8px;
      padding-left:10px;
      padding-right:10px;
      font-family: 'WenQuanYi Zen Hei';
    }
    table td {
      border: 1px solid #5D6975;
      vertical-align: top;
      font-size: 9pt;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
      font-family: 'WenQuanYi Zen Hei';
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 8px;
      padding-top:8px;
      font-size: 12px;
      padding-bottom:5px;
      background: #F5F5F1; 
      font-weight: bold; 
    }
    .title{
      text-align: center;
      background-color: #cbcbcb;
      font-weight: 600;
    }
    .ra{
      width: 3%;
      text-align: right;
    }
    ul.a{ 
      line-height:normal;
      padding-bottom:50px;
      list-style-type: square;
    }
    .lr{
      border-left: none;
      border-right:none;
    }

  </style>
</head>
<body>
  <main>
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <tbody>
          <tr>
            <th class="title" colspan="8">团体同意书</th>
          </tr>
          <tr>
            <td colspan="2">新加坡负责人 / 联络号码</td>
            <td colspan="2">{{$data[0]->UserFullName}}/{{$data[0]->UserPhone}}</td>
            <td colspan="2">国外负责人 / 联络号码</td>
            <td colspan="2">{{$data[0]->ContactName}}/{{$data[0]->ContactNumber}}</td>
          </tr>
            <tr>
              <td width="8%">团体代号</td>
              <td width="12%">{{$data[0]->TourCode}}</td>
              <td width="8%">公司名称</td>
              <td width="12%">{{$data[0]->Customer}}</td>
              <td width="8%">领队名字</td>
              <td width="12%">Others</td>
              <td width="9%">出团名称</td>
              <td width="12%">{{$data[0]->TourLeader}}</td>
            </tr>
            <tr>
                <td width="8%">开票日</td>
                <td width="12%">{{$data[0]->Date}}</td>
                <td width="8%">账单号</td>
                <td width="12%">?</td>
                <td width="8%">新加坡导游</td>
                <td width="12%">{{$data[0]->TourGuide1}}</td>
                <td width="9%">国外导游</td>
                <td width="12%">{{$data[0]->TourGuide2}}</td>
              </tr>
            <tr>
              <td style="border:none;" colspan="8"></td>
            </tr>
            <tr>
              <td class="title" colspan="8">班机资料</td>
            </tr>
            <tr>
              <td>次抵达日期</td>
              <td>{{count($dataFlight) > 0 ? $dataFlight[0]->FlightDate : null}}</td>
              <td>次抵达班机</td>
              <td>{{count($dataFlight) > 0 ? $dataFlight[0]->FlightCode : null}}</td>
              <td>次出境日期</td>
              <td>{{count($dataFlight) > 1 ? $dataFlight[1]->FlightDate : null}}</td>
              <td>次出境班机</td>
              <td>{{count($dataFlight) > 1 ? $dataFlight[1]->FlightCode : null}}</td>
            </tr>
            @if (count($dataFlight) > 2)
            <tr>
              <td>次抵达日期</td>
              <td>{{count($dataFlight) > 2 ? $dataFlight[2]->FlightDate : null}}</td>
              <td>次抵达班机</td>
              <td>{{count($dataFlight) > 2 ? $dataFlight[2]->FlightCode : null}}</td>
              <td>次出境日期</td>
              <td>{{count($dataFlight) > 3 ? $dataFlight[3]->FlightDate : null}}</td>
              <td>次出境班机</td>
              <td>{{count($dataFlight) > 3 ? $dataFlight[3]->FlightCode : null}}</td>
            </tr>
            @endif
          </tbody>
      </table>
      <table>
          <tbody>
            <tr>
              <td class="title" colspan="12">房型安排</td>
            </tr>
            <tr>
              <td width="8%">大人</td>
              <td class="ra">{{$data[0]->ADT}}</td>
              <td width="8%">小孩佔床</td>
              <td class="ra">{{$data[0]->CWB}}</td>
              <td width="8%">小孩不佔床</td>
              <td class="ra">{{$data[0]->CNB}}</td>
              <td width="8%">婴儿</td>
              <td class="ra">{{$data[0]->INF}}</td>
              <td width="8%">领队</td>
              <td class="ra">{{$data[0]->TL}}</td>
              <td width="8%">总人数</td>
              <td class="ra">{{$data[0]->Total1}}</td>
            </tr>
            <tr>
              <td>单人房</td>
              <td class="ra">{{$data[0]->SGL}}</td>
              <td>标间房</td>
              <td class="ra">{{$data[0]->TWN}}</td>
              <td>双人房</td>
              <td class="ra">{{$data[0]->DBL}}</td>
              <td>三人房</td>
              <td class="ra">{{$data[0]->TRP}}</td>
              <td>免费</td>
              <td class="ra">{{$data[0]->FOC}}</td>
              <td>总房数</td>
              <td class="ra">{{$data[0]->Total2}}</td>
            </tr>
        </tbody>
      </table>
      <table>
          <tbody>
              <tr class="title">
                  <td>酒店名称</td>
                  <td>报到</td>
                  <td>查看</td>
                  <td>房型</td>
                  <td>确认号</td>
              </tr>
              <tr>
                  <td>{{$dataItinerary[0]->Hotel}}</td>
                  <td>{{$dataItinerary[0]->DateFrom}}</td>
                  <td>{{$dataItinerary[0]->DateUntil}}</td>
                  <td>{{$data[0]->TWN}}TWN + {{$data[0]->DBL}}DBL + {{$data[0]->SGL}}SGL</td>
                  <td>{{$dataItinerary[0]->CodeReff}}</td>
              </tr>
          </tbody>
      </table>
      <table>
        <tbody>
          <tr class="title">
            <td>日期</td>
            <td>行程安排</td>
            <td colspan="2">餐食安排</td>
          </tr>
          @php $count = 0; @endphp
          @foreach($dataItinerary as $row)
          <tr>
            <td width="5%" rowspan="3">{{$row->Date}}</td>
            <td width="25%" rowspan="3">{!! $row->DescEN !!}</td>
            <td width="3%">早餐</td>
            <td width="15%">{{$row->MB}}</td>
          </tr>
          <tr>
            <td>午餐s</td>
            <td>{{$row->ML}}</td>
          </tr>
          <tr>
            <td>晚餐</td>
            <td>{{$row->MD}}</td>
          </tr>
          @php $count = $count + 1; @endphp
          @endforeach
          @for($i = 0; $i < (4 - $count ); $i++)
            <tr>
              <td width="5%" rowspan="3"></td>
              <td width="25%" rowspan="3"></td>
              <td width="3%">早餐</td>
              <td width="15%"></td>
            </tr>
            <tr>
              <td>午餐</td>
              <td></td>
            </tr>
            <tr>
              <td>晚餐</td>
              <td></td>
            </tr>
          @endfor
      </tbody>
    </table>
    <table style="page-break-inside: avoid;">
      <tbody>
          <tr>
            <td class="title">导游 备注</td>
          </tr>
          <tr>
              <td height="50px">{!! $data[0]->NoteTourGuide !!}</td>
          </tr>
          <tr>
              <td class="lr"></td>
          </tr>
          <tr>
              <td class="title">备注</td>
          </tr>
          <tr>
              <td height="70px">{!! $data[0]->Note !!}</td>
          </tr>
      </tbody>
    </table>
    </div>
  </main>
  <footer>
  </footer>
</body>
</html>