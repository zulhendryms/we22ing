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
        <tr> {{--width:675px / 525px--}}
          <th class="firstcol" style="width:70px">COMP</th>
          <th style="width:60px" align="left" >DATE</th>
          <th style="width:20px" align="left" >QTY</th>
          <th style="width:20px" align="left" >CUR</th>
          <th style="width:90px" align="right" >SUBTOTAL DTL.</th>
          <th style="width:80px" align="right" >DISC. DETAIL</th>
          <th style="width:100px" align="right" >SUBTOTAL</th>
          <th style="width:90px" align="right" >DISCOUNT</th>
          <th style="width:120px" align="right" >TOTAL</th>
          <th style="width:120px" align="right" >CASH TRANS.</th>
          <th class="lastcol" style="width:120px" align="right" >FINAL AMOUNT</th>
        </tr>
      </thead>
      <tbody> 
                
        {{-- DECLARATION --}}
        @php 
          $group=""; 
          $totalgroup = reportVarCreate(['DetailSubtotal', 'DetailDiscount', 'SubtotalAmount', 'DiscountAmount', 'TotalAmount','TotalSessionAmount','TotalAmountAndSession']);
          $totalall = reportVarCreate(['DetailSubtotal', 'DetailDiscount', 'SubtotalAmount', 'DiscountAmount', 'TotalAmount','TotalSessionAmount','TotalAmountAndSession']);          
        @endphp {{-- DECLARATION --}}
  
        @foreach($data as $row)

          {{-- DETAIL --}}
          @php
            $row->TotalAmountAndSession = $row->TotalAmount + $row->TotalSessionAmount;
            $totalgroup = reportVarAddValue($totalgroup, $row);
            $totalall = reportVarAddValue($totalall, $row);  
          @endphp
          <tr>
            <td class="firstcol">{{$row->Comp}}</td>
            <td align="left">{{$row->Date}}</td>
            <td align="left">{{$row->Qty}}</td>
            <td align="left">{{$row->CurrencyCode}}</td>
            <td align="right">{{number_format($row->DetailSubtotal ,2,',','.')}}</td>
            <td align="right">{{number_format($row->DetailDiscount ,2,',','.')}}</td>
            <td align="right">{{number_format($row->SubtotalAmount ,2,',','.')}}</td>
            <td align="right">{{number_format($row->DiscountAmount ,2,',','.')}}</td>
            <td align="right">{{number_format($row->TotalAmount ,2,',','.')}}</td>
            <td align="right">{{number_format($row->TotalSessionAmount ,2,',','.')}}</td>
            <td class="lastcol" align="right">{{number_format($row->TotalAmountAndSession ,2,',','.')}}</td>
          </tr> {{-- DETAIL --}}
          
        @endforeach
      </tbody>

      <tr> {{-- GRAND TOTAL FOR ALL --}}
        <td colspan="4" class="total" align="right"><strong>Total For {{$group}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['DetailSubtotal'] ,2,',','.')}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['DetailDiscount'],2,',','.')}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['SubtotalAmount'],2,',','.')}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['DiscountAmount'],2,',','.')}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['TotalAmount'],2,',','.')}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['TotalSessionAmount'],2,',','.')}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($totalall['TotalAmountAndSession'],2,',','.')}}</strong></td>
      </tr> {{-- GRAND TOTAL FOR ALL --}}
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>