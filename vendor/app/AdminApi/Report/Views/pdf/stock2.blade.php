<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
    p { page-break-after: always; }
    p:last-child { page-break-after: never; }

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
      color: #ffffff;
      border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975;
      background: #888888;
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #dddddd;
      vertical-align: top;
      font-size: 11px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:5px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 10px;
      padding-top:10px;
      font-size: 14px;
      padding-bottom:10px;
      background: #F5F5F1; 
      font-weight: bold; }     
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr> {{--width:675px--}}
          <th class="firstcol" style="width:30px">Comp</th>
          <th style="width:30px">DATE</th>
          <th style="width:10px">CODE</th>
          <th style="width:150px">NAME</th>
          <th style="width:100px">WAREHOUSE</th>
          <th style="width:80px" align="right">TYPE</th>
          <th style="width:50px" align="right">QUANTITY</th>
          <th class="lastcol" style="width:50px"  align="right">BALANCE</th>
        </tr>
      </thead>
      <tbody> 
        @php $group = ""; $group1 = ""; $sumqty=0; @endphp
        @foreach($data as $row)
          @if ($group != $row->Warehouse)
            <tr>
              <td class="group" colspan="12"><strong>{{$row->Warehouse}}</strong></td>
            </tr>
            @php $group = $row->Warehouse; @endphp
          @endif
          @if ($group1 != $row->Name)
            @if ($group1 !="")
              <tr>
                <td colspan="7" align="centre"><strong>Total: </strong></td>
                <td class="total" align="right"><strong>{{ number_format($sumqty ,2,',','.') }}</strong></td>
              </tr>
            @endif
            <tr>
              <td colspan="10" class="group"><strong></strong>{{$row->Name}}</td>
            </tr>
            @php $group1 = $row->Name; $sumqty =0; $balance=0; @endphp
          @endif  
          <tr>
            <td class="firstcol" align="centre">{{ $row->Comp }} </td>
            <td align="left">{{ date('j/n', strtotime($row->Date)) }}</td>
            <td align="centre">{{ $row->Code }} </td>
            <td align="left">{{ $row->Name }} {{$row->ItemCode}}</td>
            <td align="left">{{ $row->Warehouse }}</td>
            <td align="right">{{$row->Type}}</td>
            <td align="right">{{$row->Quantity}}</td>
            @php 
              $sumqty= $sumqty + $row->Quantity;
              $balance= $row->Quantity + $balance; 
            @endphp
            <td class="lastcol" style="width:50px" align="right">{{ number_format($balance ,2,',','.')}}</td>
          </tr>
        @endforeach
        <tr>
          <td colspan="7" class="group" align="centre"><strong>Total: </strong></td>
          <td class="total" align="right"><strong>{{ number_format($sumqty ,2,',','.') }}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>