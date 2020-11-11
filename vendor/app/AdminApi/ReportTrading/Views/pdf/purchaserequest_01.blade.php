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
      padding-right:1px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 8px;
      padding-top:8px;
      font-size: 13px;
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
          <th class="firstcol" style="width:20px">COMP</th>
          <th style="width:100px">DATE</th>
          <th style="width:100px">CODE</th>
          <th style="width:150px">B.PARTNER</th>
          <th style="width:50px">DEPT</th>
          <th style="width:100px">COSTCENTER</th>
          <th style="width:70px">PURCHASER</th>
          <th style="width:70px">APPROVAL</th>
          <th style="width:70px">APPRV DATE</th>
          <th class="lastcol" style="width:50px">TERM</th>
        </tr>
        <tr>
          <th style="padding-bottom: 10px; padding-top: 10px;"></th>
          <th style="padding-bottom: 10px; padding-top: 10px;" colspan="3">ITEM</th>
          <th style="padding-bottom: 10px; padding-top: 10px;" colspan="3">NOTE</th>
          <th style="padding-bottom: 10px; padding-top: 10px;">QTY</th>
          <th style="padding-bottom: 10px; padding-top: 10px;">AMOUNT</th>
          <th style="padding-bottom: 10px; padding-top: 10px;">TOTAL</th>
        </tr>
      </thead>
      <tbody>
        @php $group1 = ''; $Total = 0; @endphp
        @foreach($data as $row)
          @if ($group1 != $row->Code)
            @if ($group1 != '')
            <tr>
                <td colspan="6"></td>
                <td align="right">Discount</td>
                <td align="right">{{number_format($row->DiscountAmount,2)}}</td>
                <td align="right">Total IDR</td>
                <td align="right">{{number_format($Total,2)}}</td>
            </tr>
            <tr>
              <td colspan="10" class="group"></td>             
            </tr>
            @endif
            <tr style="font-weight:bold !important">
              <td class="firstcol">{{$row->Comp}}</td>
              <td>{{$row->Date}}</td>
              <td>{{$row->Code}}</td>
              <td>{{$row->BusinessPartner}}</td>
              <td>{{$row->Department}}</td>
              <td>{{$row->CostCenter}}</td>
              <td>{{$row->Purchaser}}</td>
              <td>{{$row->Approval}}</td>
              <td>{{$row->ApprovalDate}}</td>
              <td class="lastcol">{{$row->PaymentTerm}}</td>
            </tr>
            @php $group1 = $row->Code; $Total = 0;@endphp
          @endif
          <tr>
            <td></td>
            <td colspan="3">{{$row->ItemName}}</td>
            <td colspan="3">{{$row->Note}}</td>
            <td align="right">{{$row->Qty}} {{$row->ItemUnit}}</td>
            <td align="right">X {{number_format($row->Amount,2)}}</td>
            <td align="right">{{number_format($row->Qty * $row->Amount,2)}}</td>
          </tr>
          @php $Total = $Total + $row->Qty * $row->Amount; @endphp
          
        @endforeach
        <tr>
          <td colspan="6"></td>
          <td align="right">Discount</td>
          <td align="right">{{number_format($row->DiscountAmount,2)}}</td>
          <td align="right">Total IDR</td>
          <td align="right">{{number_format($Total,2)}}</td>
      </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>