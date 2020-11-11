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
      font-size: 15px;
      padding-top:15px;
      padding-bottom:15px;
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
    table td.firstcol { padding-left: 10px; }
    table td.lascol { padding-right: 20px; }
    table th.firstcol { padding-left: 20px; }
    table td.lascol { padding-right: 20px; }
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
          <th class="firstcol" style="width:40px">COMP</th>
          <th class="firstcol" style="width:337.5px">NAME</th>
          <th align="right">AMOUNT</th>
          <th class="lastcol" align="right">BALANCE</th>
        </tr>
      </thead>
      <tbody>
        @php $group=""; $group1=""; $group2=""; $totalgroup=0; $ProfitLossTotal =0; $totalAmount =0; $balance=0; $totalall = 0; @endphp
        @foreach($data as $row)
          @if ($group !=  $row->ProfitLossGroup & $group1 != $row->Name2 )
            @if ($group != "")
              <tr>
                <td colspan="2" align="centre"><strong>{{$row->ProfitLossTotal}}</strong></td>
                <td align="right"><strong>{{number_format($totalgroup ,2,',','.')}}</strong></td>
                <td align="right"><strong>{{number_format($balance ,2,',','.')}}</strong></td>
              </tr>
            @endif
            <tr>
              <td colspan="4" class="group" align="centre" style="font-size:14px;"><strong>{{ $row->ProfitLossGroup }}</strong></td>
            </tr>
            <tr>
              <td colspan="4" class="group" align="centre" style="font-size:14px;"><strong>{{ $row->Name2 }} {{ $row->Code3 }}</strong></td>
            </tr>
            @php $group =  $row->ProfitLossGroup; $totalgroup =0; @endphp
          @endif
          <tr>
            <td class="firstcol">{{ $row->Comp }}</td>
            <td class="firstcol">{{ $row->Name3 }} - {{$row->Code3}}</td>
            <td align="right">{{ number_format($row->Amount1 ,2,',','.') }}</td>
            @php 
              $totalgroup= $totalgroup + $row->Amount1; 
              $balance= $balance + $row->Amount0;
              $totalall = $totalall + $row->Amount0;
            @endphp
            <td class="lastcol" align="right"></td>
          </tr>
        @endforeach
        <tr>
          <td colspan="2" align="centre"><strong>{{$row->ProfitLossTotal}}</strong></td>
          <td align="right"><strong>{{number_format($totalgroup ,2,',','.')}}</strong></td>
          <td align="right"><strong>{{number_format($balance ,2,',','.')}}</strong></td>
        </tr>
        <tr>
          <td colspan="3" align="centre"><strong>TOTAL PROFIT</strong></td>
          <td class="total" align="right"><strong>{{ number_format($totalall ,2,',','.')}}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
        <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>