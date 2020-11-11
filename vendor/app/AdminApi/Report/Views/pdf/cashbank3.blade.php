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
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #ffffff;
      border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975;
      background: #888888;
      font-size: 15px;
      padding-top:5px;
      padding-bottom:5px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #dddddd;
      vertical-align: top;
      font-size: 13px;
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
          <th class="firstcol" style="width:40px">COMP</th>
          <th style="width:250px">ACCOUNT NAME</th>
          <th class="firstcol" >CUR</th>
          <th style="width:100px" align="right" >BALANCE AMOUNT</th>
          <th class="firstcol" >CUR</th>
          <th class="lastcol" style="width:100px" align="right" >BALANCE BASE AMOUNT</th>
        </tr>
      </thead>
      <tbody> 
        @php $group = ""; $balanceamount=0; $balancebaseamount=0; $SumBaseAmount=0; $SumAmount=0; @endphp
        @foreach($data as $row)
          @if ($group != $row->AccountCashBank)
            @if ($group != "")
              <tr>
                <td class="firstcol" align="left">{{$row->Comp}}</td>
                <td align="left">{{$row->AccountCashBank}}</td>
                <td align="left">{{$row->Currency}}</td>
                <td align="right">{{number_format($balanceamount ,2)}}</td>
                <td align="left">{{$row->basecurrency}}</td>
                <td class="lastcol" align="right">{{number_format($balancebaseamount ,2)}}</td>
              </tr>
            @endif
            @php $group = $row->AccountCashBank; $balancebaseamount=0; $balanceamount=0; @endphp
          @endif
            @php 
              $balanceamount= $balanceamount + $row->DebetAmount - $row->CreditAmount; 
              $balancebaseamount= $balancebaseamount + $row->DebetBase - $row->CreditBase;
              // $SumAmount= $SumAmount + $row->DebetAmount - $row->CreditAmount; 
              // $SumBaseAmount= $SumBaseAmount + $row->DebetBase - $row->CreditBase;
            @endphp
        @endforeach
        {{-- <tr>
          <td class="firstcol"></td>
          <td></td>
          <td align="right"><strong>Total: </strong></td>
          <td align="right"><strong>{{number_format($SumAmount ,2)}}</strong></td>
          <td align="right"><strong>Total: </strong></td>
          <td class="lastcol" align="right"><strong>{{number_format($SumBaseAmount ,2)}}</strong></td>
        </tr> --}}
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>