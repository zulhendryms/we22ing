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
      padding-right:5px;
    }
    table td.firstcol { padding-left: 10px; }
    table td.lascol { padding-right: 20px; }
    table th.firstcol { padding-left: 20px; }
    table td.lascol { padding-right: 20px; }
    table td.group {
      padding-left: 8px;
      padding-top:8px;
      font-size: 11px;
      padding-bottom:8px;
      background: #F5F5F1; 
      font-weight: bold; }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr> {{--width:675px --}}
          <th class="firstcol" style="width:50px">COMP</th>
          <th class="firstcol" style="width:50px">DATE</th>
          <th style="width:70px">CODE</th>
          <th style="width:80px">TYPE</th>
          <th style="width:170px" align="left">DESCRIPTION</th>
          <th style="width:50px" align="left">CURRENCY </th>
          <th class="lastcol" style="width:100px" align="right" >TOTAL AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        @php $Account= ""; $sumcreditall=0; $sumdebetall=0; $balance=0; @endphp
        @foreach($data as $row)
          @if ($Account != $row->Account)
            <tr>
              <td colspan="7" class="group"><strong>{{$row->Code}} ({{$row->Account}})</strong></td>
            </tr>
            @php $Account = $row->Account; $balance=0; @endphp
          @endif
          <tr>
            <td align="left">{{$row->Comp}}</td>
            <td class="firstcol" align="centre">{{date('d-m-Y', strtotime($row->Date))}}</td>
            <td align="left">{{$row->Code}}</td>
            <td align="left">{{$row->Source}}</td>
            <td align="left" style="font-size:10px">{{ $row->Description}}</td>
            <td align="centre" style="font-size:10px">{{ $row->Currency}}</td>
            <td class="lastcol" style="width:100px" align="right">{{0}}</td>
          </tr>
        @endforeach
        <tr>
          <td colspan="6" align="right"><strong>TOTAL</strong></td>
          <td class="total" align="right"><strong>{{0}}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>