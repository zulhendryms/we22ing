{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>prereport-purchaseorder</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
    p{
      line-height: normal;
      padding: 0px;
      margin: 0px;
    }
    img {
        display: block;
        margin-left: auto;
        margin-right: auto;
        max-width: 250px;
        max-height: 100px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-bottom: 10px;
    }
    
    table th {
      padding: 15px 10px;
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #000000;
      border: 1px solid #5D6975;
      /* border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975; */
      /* background: #888888; */
      font-size: 14px;
      padding-top:5px;
      padding-bottom:5px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #5D6975;
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
      font-size: 12px;
      padding-bottom:8px;
      background: #F5F5F1; 
      font-weight: bold; }
  </style>
</head>

<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
       @if($data[0]->Type != 'PurchaseOrder')
        @if ($data[0]->Note)
            <table height="50px" style="padding-top:2px; padding-bottom:1px">
              <tbody>
                <tr>
                  <td>Note : {!! $data[0]->Note !!}</td>
                </tr>
              </tbody>
            </table>
        @endif
      @endif
      
      <table width="100%">
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:20px">NO</th>
            <th style="width:400px">ITEM</th>
            <th style="width:15px">QTY</th>
            <th style="width:30px">AMOUNT</th>
            <th class="lastcol" style="width:30px">TOTAL</th>
          </tr>
        </thead>   
        <tbody>
          @php $count=1; $TotalAmount=0; $sumtotal=0; $qtytotal=0;  @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol" align="center">{{$count}}</td>
              <td>{{$row->ItemName}} 
                @if($row->itemNote)</br>{{$row->itemNote}}@endif
                @if($row->CostCenter)</br>For : {{$row->CostCenter}}@endif
              </td>
              <td align="center">{{$row->Qty}} </td>
              <td align="right">{{number_format($row->Amount ,2)}}</td>
                  @php
                    $TotalAmount =  ($row->Qty * $row->Amount);
                    $sumtotal = $sumtotal + $TotalAmount;
                    $qtytotal = $qtytotal + $row->Qty;
                  @endphp
              <td class="lastcol" align="right">{{number_format($TotalAmount ,2)}}</td>
            </tr>
            @php $count++; $total=0;  @endphp
          @endforeach
          @php $grandtotal = $sumtotal - $data[0]->DiscountAmount;   @endphp
            @if ($data[0]->DiscountAmount)
              <tr>
                <td colspan="4" align="right"><strong>Discount Amount</strong></td>
                <td class="total" align="right"><strong>{{number_format($data[0]->DiscountAmount,2)}}</strong></td>
              </tr>
            @endif
            <tr>
              <td colspan="4" align="right"><strong>GRAND TOTAL - {{$data[0]->CurrencyCode}}</strong></td>
              <td class="total" align="right"><strong>{{number_format($grandtotal,2)}}</strong></td>
            </tr>
        </tbody>
      </table>
    
  </main>
</body>
@if($PaperSize == 'A4')

    <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
        <div style="font-size:12px; padding-right: 10px;  width:180px;">
          <p style="text-align:center; width:189px;">Prepared By</br></br></br></br></br>{{$data[0]->Purchaser}}</p>
        </div>
@endif
</html>