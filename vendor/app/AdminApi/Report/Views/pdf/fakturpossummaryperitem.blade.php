<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>SALES REPORT PER ITEM</title>
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
      font-size: 14px;
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
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:20px">NO</th>
            <th style="width:250px">ITEM</th>
            <th style="width:10px">QTY</th>
            <th style="width:50px">FLAT PRICE</th>
            <th class="lastcol" style="width:50px">TOTAL PRICE</th>
          </tr>
        </thead>   
        <tbody>
            @php $count = 1; $flatprice=0; $total=0; $sumtotal=0; $sumqty=0; $sumdiscount=0; @endphp
            @foreach($data as $row)
              <tr>
                <td class="firstcol">{{$count}}</td>
                <td>{{$row->Item}}</td>
                <td align="left">{{$row->Quantity}}</td>
                <td align="right">{{number_format($row->Amount ,2,',','.')}}</td>
                <td align="right">{{number_format($row->Subtotal ,2,',','.')}}</td>
                @php 
                  $flatprice = $flatprice + ($row->Subtotal + $row->Quantity); 
                  $sumqty = $sumqty + $row->Quantity;
                  $sumtotal = $sumtotal + $row->Subtotal;
                  $sumdiscount = $sumdiscount + $row->DiscountAmount;
                @endphp
              </tr>
              @php $count++; @endphp
            @endforeach
            <tr>
                <td colspan="2" align="right"><strong>TOTAL</strong></td>
                <td class="total" align="right"><strong>{{$sumqty}}</strong></td>
                <td class="total" align="right"><strong></strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
              </tr>
              <tr>
                <td colspan="4" align="right"><strong>DISCOUNT</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumdiscount ,2,',','.')}}</strong></td>
              </tr>
              <tr>
                <td colspan="4" align="right"><strong>GRAND TOTAL</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal - $sumdiscount ,2,',','.')}}</strong></td>
              </tr>
        </tbody>
      </table>
    </div>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>