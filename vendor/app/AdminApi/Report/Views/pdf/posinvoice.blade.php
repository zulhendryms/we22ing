<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>POS Invoice</title>
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
    .column1 {
        border-collapse: collapse;
        box-sizing: border-box;
        width: 98%;
        padding: 5px;
        height: 100px;
        border: 1px solid black;
    }
    .column2 {
        border-collapse: collapse;
        box-sizing: border-box;
        width: 98%;
        padding: 5px;
        height: fit-content;
        border: 1px solid black;
    }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:30px">S. NO</th>
            <th style="width:40px">Company</th>
            <th style="width:300px">DESCRIPTION</th>
            <th style="width:30px">QTY</th>
            <th style="width:200px">UNIT PRICE</th>
            <th class="lastcol" style="width:200px">AMOUNT (TWD)</th>
          </tr>
        </thead>   
        <tbody>
          @php $count = 1; $total=0; $sumtotal=0;  @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol">{{$count}}</td>
              <td>{{$row->Comp}}</td>
              <td>Attraction: {{$row->ItemName}} <br>
                  Date : {{date('d/m/Y', strtotime($row->Date))}}
              </td>
              <td align="right">{{$row->Quantity}}</td>
              <td align="right">{{number_format($row->Amount ,2,',','.')}}</td>
              @php
                $total = ($total + ($row->Quantity * $row->Amount)) - $row->DiscountPercentageAmount;
                $sumtotal = $sumtotal + $total;
              @endphp
              <td class="lastcol" align="right">{{number_format($total ,2,',','.')}}</td>
            </tr>
            @php $count++; $total=0;  @endphp
          @endforeach
            <tr>
              <td colspan="5" align="right"><strong></strong></td>
              <td class="total" align="right"><strong>NETT AMOUNT TWD {{number_format($sumtotal - $data[0]->DiscountAmountParent + $data[0]->DiscountPercentageAmountParent ,2,',','.')}}</strong></td>
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
            <table>
                <div class="column1" style="font-size: 12px">
                    <div>Remarks :</div>
                </div>
                <div class="column2" style="font-size: 12px">
                    <div>Payable to Remittance :</div>
                    <div>Account Name : 健達顧問有限公司</div>
                    <div>Bank Name : 新光商業銀行（長安分行）</div>
                    <div>Account Number : 0611-10-101077-8</div>
                    <div>Swift Code : MKTBTWTPOBU</div>
                </div>
                <tr>
                    <div style="padding-top:40px; font-size:12px">AUTHORISED SIGNATURE(S)</div>
                </tr>
            </table>
        </div>
  </footer>
</body>
</html>