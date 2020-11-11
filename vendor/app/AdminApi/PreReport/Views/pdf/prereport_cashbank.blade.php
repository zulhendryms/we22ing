{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>
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
      padding-top:15px;
      padding-bottom:15px;
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
      @if ($data[0]->Type == 'Transfer')
          <table height="50px" style="padding-top:2px; padding-bottom:1px">
            <tbody>
              <tr>
                <td>
                  FROM ACCOUNT: {{$data[0]->AccountName}}</br></br>
                  TRANSFER AMOUNT: {{$data[0]->CurrencyCode}} {{number_format($data[0]->TotalAmount)}}</br></br>
                  TO ACCOUNT: {{$data[0]->TransferAccount}}</br></br>
                  TRANSFER AMOUNT: {{$data[0]->TransferCur}} {{number_format($data[0]->TransferAmount * $data[0]->TransferRateBase)}}</br></br>
                </td>
              </tr>
            </tbody>
          </table>
      @endif
      @if ($data[0]->Note)
          <table height="50px" style="padding-top:2px; padding-bottom:1px">
            <tbody>
              <tr>
                <td>Note : {!! $data[0]->Note !!}</td>
              </tr>
            </tbody>
          </table>
      @endif
      
      @if ($data[0]->InvoiceNumber)
        <table>
          <thead>
            <tr> {{--width:675px--}}
              <th class="firstcol" style="width:30px">NO</th>
              <th style="width:300px">Invoice</th>
              <th style="width:200px">Amount Invoice</th>
              <th class="lastcol" style="width:200px">Amount CashBank</th>
            </tr>
          </thead>   
          <tbody>
            @php $count=1; $totalamount1=0; $totalamount2=0;   @endphp
            @foreach($data as $row)
              <tr>
                <td class="firstcol">{{$count}}</td>
                <td>{{$row->InvoiceNumber}}</td>
                <td align="right">{{number_format($row->AmountInvoice ,2,',','.')}}</td>
                    @php

                      $totalamount1 = $totalamount1 + $row->AmountInvoice;
                      $totalamount2 = $totalamount2 + $row->AmountCashBank;

                    @endphp
                <td class="lastcol" align="right">{{number_format($row->AmountInvoice ,2,',','.')}}</td>
              </tr>
              @php $count++; $total=0;  @endphp
            @endforeach
            @php
            $grandtotal = $totalamount2 + $row->AdditionalAmount - $row->DiscountAmount;
        @endphp
              <tr>
                <td colspan="2" align="right"><strong>SUB TOTAL</strong></td>
                <td class="total" align="right"><strong>{{number_format($totalamount1 ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($totalamount2 ,2,',','.')}}</strong></td>
              </tr>
              @if ($data[0]->AdditionalAmount)
              <tr>
                <td colspan="3" align="right"><strong>{{$data[0]->AdditionalAccount}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($row->AdditionalAmount,2,',','.')}}</strong></td>
              </tr>
              @endif
              @if ($data[0]->DiscountAmount)
              <tr>
                <td colspan="3" align="right"><strong>{{$data[0]->DiscountAccount}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($row->DiscountAmount,2,',','.')}}</strong></td>
              </tr>
              @endif
              <tr>
                <td colspan="3" align="right"><strong>GRAND TOTAL</strong></td>
                <td class="total" align="right"><strong>{{number_format($grandtotal,2,',','.')}}</strong></td>
              </tr>
          </tbody>
        </table>
      @endif
  </main>

</body>
@if($PaperSize == 'A4')
<footer>
    <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
         <div style="font-size:12px; margin-right: 100px; float: right;">
           <p style="margin-bottom: 40px;">Authorized By</p>
           <p>(________________)</p>
         </div>
     </div>
</footer>
@endif
</html>