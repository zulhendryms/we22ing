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
        padding-left: 10px;
        padding-top:10px;
        font-size: 12px;
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
                <th class="firstcol" style="width:337.5px">NAME</th>
                <th align="right">Period 1</th>
                <th align="right">Period 2</th>
                <th class="lastcol" align="right">Period 3</th>
              </tr>
            </thead>
            <tbody>
              @php $ProfitLossGroup = ""; $ProfitLossTotal = ""; $totalgroup=0; $totalAmount=0; $group=""; $totalperiode1=0; $totalperiode2=0;  $totalperiode3=0;   @endphp
              @foreach($data as $row)
              @if ($group !=  $row->Code2 )
                  @if ($group != "")
                    <tr>
                      <td colspan="1" align="centre">{{$row->ProfitLossTotal}}</td>
                      <td class="total" align="right">{{number_format($totalperiode1 ,2,',','.')}}</td>
                      <td class="total" align="right">{{number_format($totalperiode2 ,2,',','.')}}</td>
                      <td class="total" align="right">{{number_format($totalperiode3 ,2,',','.')}}</td>
                    </tr>
                  @endif
                  <tr>
                    <td colspan="4" class="group" align="centre">{{ $row->ProfitLossGroup }}</td>
                  </tr>
                  <tr>
                    <td colspan="4" class="group">{{ $row->Name2 }} ( {{ $row->Code2 }} )</td>
                  </tr>
                  @php $ProfitLossGroup = $row->ProfitLossGroup; $group =  $row->Code2; $totalperiode1=0; $totalperiode2=0; $totalperiode3=0; @endphp
                @endif
                <tr>
                  <td class="firstcol">{{ $row->Name3}}</td>
                  <td align="right">{{number_format($row->Amount0  ,2,',','.')}}</td>
                  <td align="right">{{number_format($row->Amount1  ,2,',','.')}}</td>
                  
                  @php $totalAmount= $totalAmount + $row->Amount2; @endphp
                  @php $totalperiode1= $totalperiode1 + $row->Amount0; @endphp
                  @php $totalperiode2= $totalperiode2 + $row->Amount1; @endphp
                  @php $totalperiode3= $totalperiode3 + $row->Amount2; @endphp
                  <td class="lastcol" align="right">{{number_format($row->Amount2  ,2,',','.')}}</td>
                </tr>
              @endforeach
              <tr>
                <td colspan="3" align="centre">TOTAL PROFIT</td>
                <td class="total" align="right">{{ number_format( $totalAmount ,2,',','.')}}</td>
              </tr>
            </tbody>
          </table>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>

    </main>
</body>

</html>