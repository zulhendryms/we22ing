<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{$reportname}}</title>
    <script type="text/php">
  </script>
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
      table tr:nth-child(2n-1) td { background: #F5F5F5; }
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
        font-size: 9px;
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
        font-size: 12px;
        padding-bottom:10px;
        background: #F5F5F1; 
        font-weight: bold; }     
    </style>
</head>

<body style="margin:0;">
    <main>
        <table>
            <thead>
              <tr> {{--width:675px--}}
                <th class="firstcol" style="width:60px">CODE</th>
                <th style="width:10px">DATE</th>
                <th style="width:250px">DESCRIPTION</th>
                <th style="width:80px" align="right" >DEBET</th>
                <th style="width:80px" align="right" >CREDIT</th>
                <th class="lastcol"  align="right" >BALANCE</th>
              </tr>
            </thead>
            <tbody> 
                @php $group = ""; $sumdebet=0; $sumcredit=0; $balance=0; @endphp
                @foreach($data as $row)
                  @if ($group != $row->BusinessPartner)
                    @if ($group !="")
                      <tr>
                        <td colspan="3" class="group" align="centre">Total: </td>
                        <td class="total" align="right">{{ number_format($sumdebet ,2,',','.') }}</td>
                        <td class="total" align="right">{{ number_format($sumcredit ,2,',','.') }}</td>
                        <td class="total" align="right">{{ number_format($balance ,2,',','.') }}</td>
                      </tr>
                    @endif
                    <tr>
                      <td colspan="6" class="group">Account : {{$row->BusinessPartner}} ( {{$row->AccountCashBank}} )</td>
                    </tr>
                    @php $group = $row->BusinessPartner; $sumdebet =0; $sumcredit =0; $balance =0;  @endphp
                  @endif  
                <tr>
                  <td class="firstcol" align="left">{{ $row->Code }}</td>
                  <td align="centre">{{ date('j/n', strtotime($row->Date)) }}</td>
                  <td align="left" style="font-size:8px">{{ $row->Description }}</td>
                  <td align="right" >{{ number_format($row->DebetAmount ,2,',','.') }}</td>
                  <td align="right" >{{ number_format($row->CreditAmount ,2,',','.') }}</td>
                  @if ($loop->first)
                    @php $balance= $row->DebetAmount - $row->CreditAmount; @endphp
                    <td class="lastcol" align="right" >{{ number_format($balance ,2,',','.') }}</td>
                    @continue
                  @endif
                  @php 
                    $balance= $balance + $row->DebetAmount - $row->CreditAmount; 
                    $sumdebet= $sumdebet + $row->DebetAmount;
                    $sumcredit= $sumcredit + $row->CreditAmount;
                  @endphp
                  <td class="lastcol" style="width:50px" align="right" >{{ number_format($balance ,2,',','.')}}</td>
                </tr>
                @endforeach
                {{--
              <tr>
                <td colspan="4">SUBTOTAL</td><td class="total">$5,200.00</td>
              </tr>
              <tr>
                <td colspan="4">TAX 25%</td><td class="total">$1,300.00</td>
              </tr>
              <tr>
                <td colspan="4" class="grand total">GRAND TOTAL</td><td class="grand total">$6,500.00</td>
              </tr>
                  --}}
            </tbody>
          </table>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>
    </main>
</body>
</html>