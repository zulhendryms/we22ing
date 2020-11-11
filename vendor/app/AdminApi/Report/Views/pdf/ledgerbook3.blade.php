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
  <main>
    <table>
      <thead>
        <tr> {{--width:675px / 525px--}}
          <th class="firstcol">Comp</th>
          <th width="30px">DATE</th>
          <th class="code" style="width:70px">CODE</th>
          <th class="source" align="left" style="width:70px">SOURCE</th>
          <th class="description" align="left" >DESCRIPTION</th>
          <th style="width:60px"></th>
          <th align="right" style="width:50px">DEBET </th>
          <th align="right" style="width:50px">CREDIT </th>
          <th class="lastcol" align="right" style="width:50px">BALANCE </th>
        </tr>
      </thead>
      <tbody>
        @php  $accountcode= ""; $sumcreditall=0; $sumdebetall=0; $balance=0; $group=""; @endphp
        @foreach($data as $row)
          @if ($group != $row->Account)
            @if ($group != "")
              <tr>
                <td colspan="6" align="centre"><strong>Account :  {{$group}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumdebetall ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumcreditall ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($balance ,2,',','.')}}</strong></td>
              </tr>
            @endif     
            <tr>
              <td colspan="9" class="group"><strong>Account : {{$row->AccountCode}} - {{$row->Account}}</strong></td>
            </tr>
            @php $group = $row->Account;  $balance=0; $sumdebetall=0; $sumcreditall=0; @endphp
          @endif
          <tr>
            <td align="left" style="font-size:10px">{{$row->Comp}}</td>
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
          <td colspan="6" align="centre"><strong>Account :  {{$group}}</strong></td>
          <td class="total" align="right"><strong>{{number_format($sumdebetall ,2,',','.')}}</strong></td>
          <td class="total" align="right"><strong>{{number_format($sumcreditall ,2,',','.')}}</strong></td>
          <td class="total" align="right"><strong>{{number_format($balance ,2,',','.')}}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>