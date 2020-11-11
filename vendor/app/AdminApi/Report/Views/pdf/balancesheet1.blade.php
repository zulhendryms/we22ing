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
      font-size: 14px;
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
      font-size: 13px;
      padding-bottom:10px;
      background: #F5F5F1; 
      font-weight: bold; }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr> {{--width:675px--}}
          <th class="firstcol" style="width:40px">COMP</th>
          <th class="firstcol" style="width:300px">NAME</th>
          <th class="lastcol" align="right">AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        @php $grandTotal = 0; $group1=""; $group2=""; $group3="";  @endphp
        @foreach($data as $row)
        <!-- karena sudah pindah grup lain (1) tetapi harus keluar total utk grup sblmnya (3) dlu -->     
        @if ($group3 != $row->GroupName)
              @if ($group3 !="")
                  <tr>
                      <td colspan="2" style="text-align:right"><strong>Total for {{$group3}}</strong></td>
                      <td align="right"><strong>{{number_format($totalAmount ,2)}}</strong></td>
                  </tr>
              @endif
              @php $totalAmount =0; @endphp
          @endif
          @if ($group1 != $row->BalanceSheetGroup )
              @if ($group1 !="")
                <tr>
                    <td colspan="2" style="text-align:right"><strong>Total for {{$group3}}</strong></td>
                    <td align="right"><strong>{{number_format($totalAmount ,2)}}</strong></td>
                </tr>
                @endif
                <tr>
                    <td colspan="3" class="group" align="centre"><strong>{{$row->BalanceSheetGroup}}</strong></td>
                </tr>
              @php $group1 = $row->BalanceSheetGroup; @endphp
            @endif
              @if ($group2 != $row->SectionName)
                <tr>
                    <td colspan="3" class="group">{{$row->SectionName}} ({{$row->SectionCode}})  </td>
                </tr>
              @php $group2 = $row->SectionName; @endphp
              @endif
              @if ($group3 != $row->GroupName)
                <tr>
                    <td colspan="3" class="group">{{$row->GroupName}} ({{$row->GroupCode}})  </td>
                </tr>
              @php $group3 = $row->GroupName; @endphp
            @endif
              <tr>
                  <td class="firstcol">{{$row->Comp}}</td>
                  <td>{{$row->AccountName}} - {{$row->AccountCode}}</td>
                  <td class="lastcol" align="right">{{number_format($row->Amount0 ,2)}}<br>Total: {{$grandTotal}}</td>
              @php 
              $totalAmount = $totalAmount + $row->Amount0;
              $grandTotal = $grandTotal + $row->Amount0;
              @endphp
              </tr>
        @endforeach
        @php
            $totalAmount = 0;
            $totalAmount = $totalAmount + $row->Amount0;
            
        @endphp
        <tr>
          <td colspan="2" style="text-align:right"><strong>Total for {{$group3}}</strong></td>
          <td align="right"><strong>{{number_format($totalAmount ,2)}}</strong></td>
        </tr>
        <tr>
          <td align="left" colspan="2"><strong>Total for {{$group1}}</strong></td>
          <td align="right"><strong>{{number_format($grandTotal ,2)}}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html> 