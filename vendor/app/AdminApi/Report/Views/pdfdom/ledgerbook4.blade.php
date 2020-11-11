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
        font-size: 10px;
        padding-bottom:8px;
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
              <tr> {{--width:675px --}}
                <th class="firstcol" style="width:50px">DATE</th>
                <th style="width:40px">CODE</th>
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
                      <td colspan="6" class="group" >{{$row->Code}} ( {{$row->Account}} )</td>
                    </tr>
                    @php $Account = $row->Account; $balance=0; @endphp
                  @endif
                  <tr>
                    <td class="firstcol" align="centre">{{date('d-m-Y', strtotime($row->Date))}}</td>
                    <td align="left">{{$row->Code}}</td>
                    <td align="left">{{$row->Source}}</td>
                    <td align="left" style="font-size:10px">{{ $row->Description}}</td>
                    <td align="centre" style="font-size:10px">{{ $row->Currency}}</td>
                    <td class="lastcol" style="width:100px" align="right">{{0}}</td>
                @endforeach
                <tr>
                  <td colspan="4" align="right">TOTAL</td>
                  <td class="total" align="right">{{0}}</td>
                </tr>
            </tbody>
          </table>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>
    </main>
</body>

</html>