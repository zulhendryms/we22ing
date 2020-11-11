{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Faktur Payment</title>
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
      color: #000000;
      border: 2px solid #5D6975;
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
      border: 2px solid #5D6975;
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
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <thead>
            <tr> {{--width:675px--}}
                <th class="firstcol" style="width:20px">NO</th>
                <th style="width:150px">Invoice Code</th>
                <th style="width:100px">Code Reff</th>
                <th style="width:50">Cur</th>
                <th style="width:100px">Amount Invoice</th>
                <th style="width:50">Cur</th>
                <th class="lastcol" style="width:100px">Amount Cash Bank</th>
              </tr>
        </thead>   
        <tbody>
          @php $count=1; $totalInvoice=0; $totalCashbank=0; @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol">{{$count}}</td>
              <td>{{$row->InvoiceCode}}</td>
              <td>{{$row->InvoiceCodeReff}}  </td>
              <td>{{$row->CurrencyCodeInvoice}}  </td>
              <td align="right"{{number_format($row->AmountInvoice ,2,',','.')}}</td>
              <td>{{$row->CurrencyCode}}  </td>
              <td align="right">{{number_format($row->AmountCashBank ,2,',','.')}}</td>
                  @php
                //    $qtytotal = $qtytotal + $row->Qty;
                //    $TotalAmount = ($row->Qty * $row->Amount);
                //     $sumtotal = $sumtotal + ($row->Qty * $row->Amount);
                $totalInvoice = $row->AmountInvoice;
                $totalCashbank = $row->AmountCashBank;
                  @endphp
            </tr>
            @php $count++; $total=0;  @endphp
          @endforeach
            <tr>
              <td colspan="4" align="right"><strong></strong></td>
              <td class="total" align="right"><strong>{{number_format($totalCashbank ,2,',','.')}}</strong></td>
              <td></td>
              <td class="total" align="right"><strong>{{number_format($totalCashbank ,2,',','.')}}</strong></td>
            </tr>
            <tr>
              {{-- <td colspan="4" align="right"><strong>GRAND TOTAL</strong></td>
              <td class="total" align="right"><strong>{{number_format($totalCashbank,2,',','.')}}</strong></td>
              <td class="total" align="right"><strong>{{number_format($totalCashbank,2,',','.')}}</strong></td> --}}
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
            <p style="margin-bottom: 40px;">Made By</p>
            <p>Admin</p>
        </div>
        <div style="font-size:11px; margin-right: 100px; float: left;">
            <p style="margin-bottom: 40px;">Check By</p>
            <p>(Technician)</p>
        </div>
        <div style="font-size:11px; margin-right: 100px; float: left;">
          <p style="margin-bottom: 40px;">Check By</p>
          <p>(Customer)</p>
      </div>
        {{-- <div style="font-size:11px; margin-right: 100px; float: left;">
            <p style="margin-bottom: 40px;">Catatan</p>
            <p></p>
        </div> --}}
    </div>
  </footer>
</body>
</html>