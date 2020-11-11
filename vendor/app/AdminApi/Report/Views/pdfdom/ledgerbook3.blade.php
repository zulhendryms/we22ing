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
        width: auto;
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
        font-size: 11px;
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
        font-size: 10px;
        padding-bottom:8px;
        background: #F5F5F1; 
        font-weight: bold; }
      th.firstcol {
        width: 2px;
      }
      th.source {
        width: 10px;
      }
      th.code {
        width: 60px;
      }
      th.description {
        width: 350px;
      }
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
          <th class="firstcol" >DATE</th>
          <th class="code">CODE</th>
          <th class="source" align="left">SOURCE</th>
          <th class="description" align="left" >DESCRIPTION</th>
          <th ></th>
          <th align="right" >DEBET </th>
          <th align="right" >CREDIT </th>
          <th class="lastcol" align="right" >BALANCE </th>
        </tr>
      </thead>
      <tbody>
        @php  $accountcode= ""; $sumcreditall=0; $sumdebetall=0; $balance=0; $group=""; @endphp
        @foreach($data as $row)
          @if ($group != $row->Account)
            @if ($group != "")
              <tr>
                <td colspan="5" align="centre">Account :  {{$group}}</td>
                <td class="total" align="right">{{number_format($sumdebetall ,2,',','.')}}</td>
                <td class="total" align="right">{{number_format($sumcreditall ,2,',','.')}}</td>
                <td class="total" align="right">{{number_format($balance ,2,',','.')}}</td>
              </tr>
            @endif     
            <tr>
              <td colspan="8" class="group" >Account : {{$row->AccountCode}} - {{$row->Account}}</td>
            </tr>
            @php $group = $row->Account;  $balance=0; $sumdebetall=0; $sumcreditall=0; @endphp
          @endif
          <tr>
            <td class="firstcol" align="centre">{{date('j/n', strtotime($row->Date))}}</td>
            <td align="left" style="font-size:10px">{{$row->Code}}</td>
            <td align="left">{{$row->Source}}</td>
            <td align="left" style="font-size:10px">{{ $row->Description}}</td>
            <td align="left" style="font-size:10px">{{ $row->AmountDescription}}</td>
            <td align="right" >{{number_format($row->DebetAmount ,2,',','.')}}</td>
            <td align="right" >{{number_format($row->CreditAmount ,2,',','.')}}</td>
            @php 
              $balance= $balance + $row->DebetAmount - $row->CreditAmount; 
              $sumdebetall= $sumdebetall + $row->DebetAmount; 
              $sumcreditall= $sumcreditall + $row->CreditAmount;
            @endphp
            <td class="lastcol" align="right" >{{number_format($balance ,2,',','.')}}</td>
          </tr>
        @endforeach
        <tr>
          <td colspan="5" align="centre">Account :  {{$group}}</td>
          <td class="total" align="right">{{number_format($sumdebetall ,2,',','.')}}</td>
          <td class="total" align="right">{{number_format($sumcreditall ,2,',','.')}}</td>
          <td class="total" align="right">{{number_format($balance ,2,',','.')}}</td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
        <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>