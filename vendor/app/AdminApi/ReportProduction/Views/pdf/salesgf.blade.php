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
        <tr> {{--width:675px / 525px--}}
          <th class="firstcol" width="100px">Date</th>
          <th width="70px">Code</th>
          {{-- <th>Customer</th> --}}
          <th>Discount 1</th>
          <th>Discount 2</th>
          <th class="lastcol">Total</th>
        </tr>
      </thead>
      <tbody>
        @php $grandTotal = 0; $group="";@endphp
        @foreach($data as $row)
        @if ($group != $row->BusinessPartner)
            <tr>
              <td colspan="5" class="group"><strong>{{$row->BusinessPartner}}</strong></td>
            </tr>
            @php $group = $row->BusinessPartner;  @endphp
          @endif
          <tr>
            <td class="firstcol">{{$row->Date}}</td>
            <td>{{$row->Production}}</td>
            {{-- <td>{{$row->BusinessPartner}}</td> --}}
            <td align="right">{{number_format($row->DiscountAmount1,2)}}</td>
            <td align="right">{{number_format($row->DiscountAmount2,2)}}</td>
            <td align="right" class="lastcol">{{number_format($row->TotalAmount,2)}}</td>
          </tr>
          @php
              $grandTotal = $grandTotal + $row->TotalAmount;
          @endphp
        @endforeach
      </tbody>
      <tbody>
        <tr>
          <td align="right"colspan="4"><b>Grand Total</b></td>
          <td align="right"><b>{{number_format($grandTotal,2)}}</b></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>