<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{$reportname}}</title>
    <script type="text/php">
  </script>
    <style>
      @page { margin: 110px 25px; }
      header {
          position: fixed;
          top: -80px;
          left: 0px;
          right: 0px;
          height: 50px;
      }
      footer {
          position: fixed;
          bottom: -50px;
          left: 0px;
          right: 0px;
          height: 10px;
      }
      p { page-break-after: always; }
      p:last-child { page-break-after: never; }

      table {
        width: 95%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
      }
      table tr:nth-child(2n-1) td { background: #F5F5F5; }
      table th {
        color: #5D6975;
        border-bottom: 1px solid #C1CED9;
        white-space: nowrap;
        font-weight: bold; 
        color: #ffffff;
        border-top: 1px solid  #5D6975;
        border-bottom: 1px solid  #5D6975;
        background: #888888;
        font-size: 12px;
        padding-top:5px;
        padding-bottom:5px;
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
    <header>
        <div style="text-align: left;">
            <img src="{{$user->CompanyObj->Image}}" width="70" style="float:right;padding-right:40px" />
            <div style="font-size: 26px;font-weight: bold;font-family:Georgia;">{{strtoupper($reportname)}}</div>
            <strong style="font-size:13px">{{strtoupper($user->CompanyObj->Name)}}</strong><br />
            <div style="font-size:11px">{{$filter}}</div>
            <div style="clear:both"></div><br />
        </div>
    </header>
    <footer>
      <hr />
      <div style="font-size:11px;padding-right:30px;float:right;">Page 1 of 2</div>
      <div style="font-size:11px;padding-left:10px">Printed by {{$user->UserName}} at  {{now()}}</div>
    </footer>
    <main>
        <table>
          <thead>
            <tr> {{--width:675px / 525px--}}
              <th class="firstcol" style="width:40px">CODE</th>
              <th style="width:30px" align="left" >DATE</th>
              <th style="width:30px" align="left" >B. PARTNER</th>
              <th style="width:30px" align="left" >PAYMENT</th>
              <th style="width:30px" align="left" >WEREHOUSE</th>
              <th style="width:30px" align="left" >TABLE</th>
              <th style="width:30px" align="left" >SALES</th>
              <th style="width:30px" align="left" >QTY</th>
              <th style="width:30px" align="left" >CUR</th>
              <th style="width:100px" align="right" >SUBTOTAL</th>
              <th style="width:100px" align="right" >DISCOUNT</th>
              <th class="lastcol" style="width:100px" align="right" >TOTAL</th>
            </tr>
          </thead>
          <tbody> 
              @php $group=""; $sumsubtotal=0; $sumdiscount=0; $sumtotal=0; @endphp
              @foreach($data as $row)
              @if ($group != $row->Cashier)
                @if ($group !="")
                  <tr>
                    <td colspan="9" class="total" align="right">Total {{$group}} </td>
                    <td class="total" align="right">{{number_format($sumsubtotal ,2,',','.')}}</td>
                    <td class="total" align="right">{{number_format($sumdiscount ,2,',','.')}}</td>
                    <td class="total" align="right">{{number_format($sumtotal ,2,',','.')}}</td>
                  </tr>
                @endif
                <tr>
                  <td colspan="12" class="group" >{{$row->Cashier}}</td>
                </tr>
                @php $group = $row->Cashier; $sumsubtotal=0; $sumdiscount=0; $sumtotal=0; @endphp
              @endif 
                <tr>
                  <td class="firstcol">{{$row->Code}}</td>
                  <td align="left">{{date('j/n', strtotime($row->DateOrder))}}</td>
                  <td align="left">{{$row->BusinessPartner}}</td>
                  <td align="left">{{$row->PaymentMethod}}</td>
                  <td align="left">{{$row->Warehouse}}</td>
                  <td align="left">{{$row->TableName}}</td>
                  <td align="left">{{$row->EmployeeName}}</td>
                  <td align="left">{{$row->Quantity}}</td>
                  <td align="right">{{$row->CurrencyCode}}</td>
                  <td align="right">{{$row->SubtotalAmount}}</td>
                  <td align="right">{{$row->DiscountAmount}}</td>
                  <td class="lastcol" align="right">{{$row->TotalAmount}}</td>
                  @php
                    $sumsubtotal = $sumsubtotal + $row->SubtotalAmount;
                    $sumdiscount = $sumdiscount + $row->DiscountAmount;
                    $sumtotal = $sumtotal + $row->TotalAmount;
                  @endphp
                </tr>
              @endforeach
            </tbody>
            <tr>
              <td colspan="9" class="total" align="right">Total {{$group}} </td>
              <td class="total" align="right">{{number_format($sumsubtotal ,2,',','.')}}</td>
              <td class="total" align="right">{{number_format($sumdiscount ,2,',','.')}}</td>
              <td class="total" align="right">{{number_format($sumtotal ,2,',','.')}}</td>
            </tr>
        </table>
        <div style="padding: 13px 20px 13px 20px;">
          <div style="font-size: 14px; color: #858585;"></div>
        </div>
    </main>
</body>

</html>