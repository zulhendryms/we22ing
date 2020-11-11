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
    table td.firstcol { padding-left: 10px; }
    table td.lascol { padding-right: 20px; }
    table th.firstcol { padding-left: 20px; }
    table td.lascol { padding-right: 20px; }
    table td.group {
      padding-left: 10px;
      padding-top:10px;
      font-size: 12px;
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
          <th style="width:337.5px">NAME</th>
          <th class="lastcol" align="right">AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        @php $Name1 = ""; $Code1 = ""; $Name2 = ""; $group1=0; $Code2 = ""; $Amount0 = 0; $totalAmount = 0; $ProfitLossGroup = ""; $totalgroup=0; $group=""; $totalAmount = 0;  @endphp
        @foreach($data as $row)
          @if ($group !=  $row->Code2 )
            @if ($group != "")
              <tr>
                <td colspan="2" align="centre"><strong>{{$row->ProfitLossTotal}}</strong></td>
              </tr>
            @endif
            <tr>
              <td colspan="3" class="group" align="centre" style="font-size:14px;"><strong>{{ $row->ProfitLossGroup }}</strong></td>
            </tr>
            @php $ProfitLossGroup = $row->ProfitLossGroup; $group =  $row->Code2;  @endphp
          @endif
          @if ($group1 != $row->Name2)
            @if ($group1 != "")
              <tr>
                <td class="total" align="right"><strong>{{number_format($totalgroup ,2,',','.')}}</strong></td>
              </tr>
            @endif
            <tr>
              <td colspan="3" class="group"><strong>{{ $row->Name2 }} ( {{ $row->Code2 }} )</strong></td>
            </tr>
            @php $group1 = $row->Name2; $totalgroup=0;  @endphp
          @endif
          <tr>
            <td class="firstcol">{{ $row->Comp }}</td>
            <td>{{ $row->Name3 }} - {{$row->Code3}}</td>
            <td class="lastcol" align="right">{{ number_format($row->Amount1 ,2,',','.') }}</td>
            @php $totalAmount= $totalAmount + $row->Amount1; @endphp
            @php $totalgroup= $totalgroup + $row->Amount1; @endphp
          </tr>
        @endforeach
        <tr>
          <td colspan="2" align="centre"><strong>{{$row->ProfitLossTotal}}</strong></td>
          <td class="total" align="right"><strong>{{number_format($totalgroup ,2,',','.')}}</strong></td>
        </tr>
        <tr>
          <td colspan="2" align="centre"><strong>TOTAL PROFIT</strong></td>
          <td class="total" align="right"><strong>{{ number_format( $totalAmount ,2,',','.')}}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>