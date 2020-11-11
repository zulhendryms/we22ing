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
          <th>PR CODE</th>
          <th>PO CODE</th>
          <th>DATE</th>
          <th>SUPPLIER</th>
          <th class="lastcol" align="right">TOTAL</th>
        </tr>
      </thead>
      <tbody> 
        @php $group = ""; $sumTotal=0; @endphp
        @foreach($report as $row)
          @if ($group != $row->Date)
            <tr><td colspan="6" class="group"><strong>{{date('d M Y', strtotime($row->Date))}}</strong></td></tr>
            @php $group = $row->Date; @endphp
          @endif
          <tr>
            <td class="firstcol"> @php $v = null; try {  $v = $row->CompanyObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td>{{$row->RequestCode}}</td>
            <td>{{$row->Code}}</td>
            <td>{{$row->Date}}</td>
            <td> @php $v = null; try {  $v = $row->BusinessPartnerObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td class="lastcol" align="right">{{number_format($row->TotalAmount ,2)}}</td>
          </tr>
          @php $sumTotal = $sumTotal + $row->TotalAmount; @endphp
        @endforeach
        <tr>
          <td colspan="5" style="font-size:13px" align="right"><strong>Total <strong></td>
          <td class="total" align="right"><strong>{{number_format($sumTotal ,2)}}<strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>