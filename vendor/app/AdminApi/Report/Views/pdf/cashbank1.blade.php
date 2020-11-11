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
    th.date{
      width:0px;
    }
    th.description{
      width:250px;
    }     
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr>
          <th class="firstcol" >COMP</th>
          <th>CODE</th>
          <th class="date">DATE</th>
          <th class="description">DESCRIPTION</th>
          <th align="right">DEBET</th>
          <th align="right">CREDIT</th>
          <th class="lastcol" align="right" >BALANCE</th>
        </tr>
      </thead>
      <tbody> 
        @php $group = ""; $balance=0; $sumdebet=0; $sumcredit=0; @endphp
        @foreach($data as $row)
          @if ($group != $row->AccountCashBank)
            @if ($group != "")
              <tr>
                <td colspan="4"  class="total" align="right"><strong>Total: {{$group}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumdebet ,2)}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumcredit ,2)}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumBalance ,2)}}</strong></td>
              </tr>
            @endif
            <tr><td colspan="7" class="group"><strong>{{$row->AccountCashBank}}</strong></td></tr>
            @php $group = $row->AccountCashBank;  $sumdebet=0; $sumcredit=0; $sumBalance=0; @endphp
          @endif
          <tr>
            <td class="firstcol" align="left">{{$row->Comp}}</td>
            <td align="left">{{$row->Code}}</td>
            <td align="left">{{date('j/n', strtotime($row->Date))}}</td>
            <td align="left" style="font-size:13px">{{ $row->Description}}</td>
            <td align="right">{{number_format($row->DebetAmount ,2)}}</td>
            <td align="right">{{number_format($row->CreditAmount ,2)}}</td>
            @php 
              $balance=0;
              $balance= $balance + $row->DebetAmount - $row->CreditAmount;
              $sumBalance= $sumBalance + $row->DebetAmount - $row->CreditAmount;
              $sumdebet= $sumdebet + $row->DebetAmount; 
              $sumcredit= $sumcredit + $row->CreditAmount;
            @endphp
            <td class="lastcol" align="right">{{number_format($balance ,2)}}</td>
          </tr>
        @endforeach
        <tr>
          <td colspan="4" style="font-size:13px" align="right"><strong>Total: {{$group}}<strong></td>
          <td class="total" align="right"><strong>{{number_format($sumdebet ,2)}}<strong></td>
          <td class="total" align="right"><strong>{{number_format($sumcredit ,2)}}<strong></td>
          <td class="total" align="right"><strong>{{number_format($sumBalance ,2)}}<strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>