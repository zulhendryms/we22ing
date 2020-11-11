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
          <th align="right">{{$periode1}}</th>
          <th align="right">{{$periode2}}</th>
          <th class="lastcol" align="right">{{$periode3}}</th>
        </tr>
      </thead>
      <tbody>
        @php $ProfitLossGroup = ""; $group1=""; $ProfitLossTotal = ""; $totalgroup=0; $totalAmount1=0; $totalAmount2=0; $totalAmount3=0; $group=""; $totalperiode1=0; $totalperiode2=0;  $totalperiode3=0;   @endphp
        @foreach($data as $row)
          @if ($group !=  $row->ProfitLossGroup & $group1 != $row->Name2  )
            @if ($group != "")
              <tr>
                <td colspan="2" align="centre"><strong>{{$row->ProfitLossTotal}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($totalperiode1 ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($totalperiode2 ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($totalperiode3 ,2,',','.')}}</strong></td>
              </tr>
            @endif
            <tr>
              <td colspan="5" class="group" align="centre"><strong>{{ $row->ProfitLossGroup }}</strong></td>
            </tr>
            <tr>
              <td colspan="5" class="group"><strong>{{ $row->Name2 }} ( {{ $row->Code2 }} )</strong></td>
            </tr>
            @php $ProfitLossGroup = $row->ProfitLossGroup; $group =  $row->ProfitLossGroup; $group1 = $row->Name2;  @endphp
          @endif
          <tr>
            <td class="firstcol">{{ $row->Comp}}</td>
            <td>{{ $row->Name3}} - {{$row->Code3}}</td>
            <td align="right">{{number_format($row->p3amt0  ,2,',','.')}}</td>
            <td align="right">{{number_format($row->p2amt0  ,2,',','.')}}</td>
            @php 
              $totalperiode1= $totalperiode1 + $row->p3amt0; 
              $totalperiode2= $totalperiode2 + $row->p2amt0; 
              $totalperiode3= $totalperiode3 + $row->p1amt0;

              $totalAmount1= $totalAmount1 + $row->p3amt0;
              $totalAmount2= $totalAmount2 + $row->p2amt0; 
              $totalAmount3= $totalAmount3 + $row->p1amt0;
            @endphp
            <td class="lastcol" align="right">{{number_format($row->p1amt0  ,2,',','.')}}</td>
          </tr>
        @endforeach
        <tr>
          <td align="right" colspan="2"><strong>TOTAL PROFIT</strong></td>
          <td class="total" align="right"><strong>{{ number_format( $totalAmount1 ,2,',','.')}}</strong></td>
          <td class="total" align="right"><strong>{{ number_format( $totalAmount2 ,2,',','.')}}</strong></td>
          <td class="total" align="right"><strong>{{ number_format( $totalAmount3 ,2,',','.')}}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>