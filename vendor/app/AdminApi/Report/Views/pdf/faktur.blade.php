<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Faktur</title>
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
      font-size: 10px;
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
      font-size: 12px;
      padding-bottom:8px;
      background: #F5F5F1; 
      font-weight: bold; }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:30px">NO</th>
            <th style="width:300px">ITEM</th>
            <th style="width:30px">QTY</th>
            <th style="width:200px">HARGA SATUAN</th>
            <th style="width:100px">%</th>
            <th class="lastcol" style="width:200px">NILAI TOTAL</th>
          </tr>
        </thead>   
        <tbody>
          @php $count = 1; $total=0; $sumtotal=0;  @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol">{{$count}}</td>
              <td>{{$row->ItemName}}</td>
              <td align="right">{{$row->Quantity}}</td>
              <td align="right">{{number_format($row->Amount ,2,',','.')}}</td>
              <td align="right">{{number_format($row->DiscountPercentage ,2,',','.')}}</td>
              @php
                $total = ($total + ($row->Quantity * $row->Amount)) - $row->DiscountPercentageAmount;
                $sumtotal = $sumtotal + $total;
              @endphp
              <td class="lastcol" align="right">{{number_format($total ,2,',','.')}}</td>
            </tr>
            @php $count++; $total=0;  @endphp
          @endforeach
            <tr>
              <td colspan="5" align="right"><strong>SUBTOTAL</strong></td>
              <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
            </tr>
            <tr>
              <td colspan="5" align="right"><strong>DISCOUNT</strong></td>
              <td class="total" align="right"><strong>{{number_format($data[0]->DiscountAmount+ $data[0]->DiscountPercentageAmount,2,',','.')}}</strong></td>
            </tr>
            <tr>
              <td colspan="5" align="right"><strong>GRAND TOTAL</strong></td>
              <td class="total" align="right"><strong>{{number_format($sumtotal - $data[0]->DiscountAmount+ $data[0]->DiscountPercentageAmount,2,',','.')}}</strong></td>
            </tr>
        </tbody>
      </table>
    </div>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
  <footer>
   <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
        <div style="font-size:11px; margin-right: 100px; float: left;">
            <p style="margin-bottom: 40px;">Dibuat Oleh</p>
            <p>Cashier</p>
        </div>
        <div style="font-size:11px; margin-right: 100px; float: left;">
            <p style="margin-bottom: 40px;">Diperiksa Oleh</p>
            <p>(..................)</p>
        </div>
        <div style="font-size:11px; margin-right: 100px; float: left;">
            <p style="margin-bottom: 40px;">Catatan</p>
            <p></p>
        </div>
    </div>
  </footer>
</body>
</html>