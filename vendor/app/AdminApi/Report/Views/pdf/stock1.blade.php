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
      font-size: 12px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 8px;
      padding-top:8px;
      font-size: 14px;
      padding-bottom:8px;
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
          <th style="width:200px">NAME</th>
          <th style="width:30px">START</th>
          <th style="width:30px">PURCH.</th>
          <th style="width:30px">SALES</th>
          <th style="width:30px">OTHER</th>
          <th class="lastcol" style="width:30px">ENDING</th>
        </tr>
      </thead>
      <tbody>
        @php 
          $group =''; $group1 ='';
          $sumstart = 0;
          $sumpurch = 0;
          $sumsales = 0;
          $sumother = 0;
          $sumending = 0;
        @endphp
        @foreach($data as $row)
          @if ($group != $row->WarehouseName )
            <tr>
              <td class="group" colspan="9"><strong>{{$row->WarehouseName}}</strong></td>
            </tr>
            @php $group = $row->WarehouseName; @endphp
          @endif
          @if ( $group1 != $row->GroupName)
            <tr>
              <td class="group" colspan="9"><strong>{{$row->GroupName}}</strong></td>
            </tr>
            @php $group1 = $row->GroupName;  $group = $row->WarehouseName; @endphp
          @endif  
          <tr>
            <td class="firstcol">{{$row->Comp}}</td>
            <td>{{$row->ItemName}}</td>
            <td align="right">{{$row->BegQty}}</td>
            <td align="right">{{$row->QtyIn == 0 ? '' : $row->QtyIn}}</td>
            <td align="right">{{$row->QtyOut == 0 ? '' : $row->QtyOut}}</td>
            <td align="right">{{$row->QtyOther == 0 ? '' : $row->QtyOther}}</td>
            <td align="right">{{$row->BegQty + $row->Qty}}</td>
            @php 
              $sumstart = $sumstart + $row->BegQty;
              $sumpurch = $sumpurch + $row->QtyIn;
              $sumsales = $sumsales + $row->QtyOut;
              $sumother = $sumother + $row->QtyOther;
              $sumending = $sumending + $row->BegQty + $row->Qty;
            @endphp
          </tr>
        @endforeach
        <tr>
          <td colspan="2" class="total" align="centre"><strong>Grand Total: </strong></td>
          <td class="total" align="right"><strong>{{ number_format($sumstart ,2) }}</strong></td>
          <td class="total" align="right"><strong>{{ number_format($sumpurch ,2) }}</strong></td>
          <td class="total" align="right"><strong>{{ number_format($sumsales ,2) }}</strong></td>
          <td class="total" align="right"><strong>{{ number_format($sumother ,2) }}</strong></td>
          <td class="total" align="right"><strong>{{ number_format($sumending ,2) }}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>