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
      table td.firstcol { padding-left: 10px; }
      table td.lascol { padding-right: 20px; }
      table th.firstcol { padding-left: 20px; }
      table td.lascol { padding-right: 20px; }
      table td.group {
        padding-left: 10px;
        padding-top:10px;
        font-size: 10px;
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
              <tr> {{--width:675px--}}
                <th class="firstcol" style="width:80px">CODE</th>
                <th style="width:20px">DATE</th>
                <th style="width:250px">DESCRIPTION</th>
                <th style="width:20px">CUR</th>
                <th style="width:50px" align="right">DEBET</th>
                <th style="width:50px" align="right">CREDIT</th>
                <th style="width:50px" align="right">BALANCE</th>
                <th style="width:50px" align="right">DEBET IDR</th>
                <th style="width:50px" align="right">CREDIT IDR</th>
                <th style="width:50px" align="right">BALANCE</th>
                <th class="lastcol" align="right">RATE</th>
                
              </tr>
            </thead>
            <tbody> 
                @php $group = ""; $balance=0; $sumdebet=0; $sumcredit=0; $balanceidr=0; $sumrateall=0; @endphp
                @php $sumdebetall=0; $sumcreditall=0; $sumbalanceall=0; @endphp
                @php $sumcreditidrall=0; $sumdebetidrall=0; $sumbalanceidrall=0; @endphp
                @foreach($data as $row)
                  @if ($group != $row->BusinessPartner)
                    @if ($group != "")
                      <tr>
                        <td colspan="4" align="right">Total: {{$group}}</td>
                        <td class="total" align="right">{{number_format($sumdebet ,2,',','.')}}</td>
                        <td class="total" align="right">{{number_format($sumcredit ,2,',','.')}}</td>
                        <td class="total" align="right">{{number_format($balance ,2,',','.')}}</td>
                        <td class="total" colspan="2" align="right">IDR</td>
                        <td class="total" align="right">{{number_format($balanceidr ,2,',','.')}}</td>
                        <td class="total" align="right"></td>
                      </tr>
                    @endif
                    <tr><td colspan="11" class="group">{{$row->BusinessPartner}} ( {{$row->AccountCashBank}} )</td></tr>
                    @php $group = $row->BusinessPartner; $balance=0; $sumdebet=0; $sumcredit=0;  $balanceidr=0; @endphp
                  @endif
                  <tr>
                    <td class="firstcol" align="left">{{$row->Code}}</td>
                    <td align="centre">{{date('j/n', strtotime($row->Date))}}</td>
                    <td align="left" style="font-size:7px">{{$row->Description}}</td>
                    <td align="left" style="font-size:7px">{{$row->Currency}}</td>
                    <td align="right" >{{number_format($row->DebetAmount ,2,',','.')}}</td>
                    <td align="right" >{{number_format($row->CreditAmount ,2,',','.')}}</td>
                    @php 
                      $balance= $balance + $row->DebetAmount - $row->CreditAmount; 
                      $balanceidr= $balanceidr + $row->DebetBase - $row->CreditBase; 
                      $sumdebet= $sumdebet + $row->DebetAmount; 
                      $sumcredit= $sumcredit + $row->CreditAmount;
                      $sumdebetall= $sumdebetall + $row->DebetAmount; 
                      $sumcreditall= $sumcreditall + $row->CreditAmount;
                      $sumcreditidrall= $sumcreditidrall + $row->CreditBase;
                      $sumdebetidrall= $sumdebetidrall + $row->DebetBase;
                      $sumrateall = $sumrateall + $row->Rate;
                    @endphp
                    <td align="right">{{number_format($balance ,2,',','.')}}</td>
                    <td align="right">{{number_format($row->DebetBase ,2,',','.')}}</td>
                    <td align="right">{{number_format($row->CreditBase ,2,',','.')}}</td>
                    <td align="right">{{number_format($balanceidr ,2,',','.')}}</td>
                    <td class="lastcol" align="right">{{number_format($row->Rate ,2,',','.')}}</td>
                  </tr>
                @endforeach
                <tr>
                  <td colspan="4" align="right">Total: {{$group}}</td>
                  <td class="total" align="right">{{number_format($sumdebet ,2,',','.')}}</td>
                  <td class="total" align="right">{{number_format($sumcredit ,2,',','.')}}</td>
                  <td class="total" align="right">{{number_format($balance ,2,',','.')}}</td>
                  <td class="total" colspan="2" align="right">IDR</td>
                  <td class="total" align="right">{{number_format($balanceidr ,2,',','.')}}</td>
                  <td class="total" align="right"></td>
                </tr>
            </tbody>
          </table>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>

    </main>
</body>

</html>