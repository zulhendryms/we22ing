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
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr> {{--width:675px--}}
          <th class="firstcol" style="width:40px">COMP</th>
          <th style="width:60px">DATE</th>
          <th style="width:10px">CODE</th>
          <th style="width:350px">WAREHOUSE</th>
          <th style="width:80px">TYPE</th>
          <th style="width:30px">QTY</th>
        </tr>
      </thead>
      <tbody> 
        @php $group = ""; @endphp
        @foreach($data as $row)
          @if ($group != $row->Name)
            <tr>
              <td colspan="7" class="group"><strong>{{$row->Name}}</strong></td>
            </tr>
            @php $group = $row->Name; @endphp
          @endif  
          <tr>
            <td align="centre">{{ $row->Comp }} </td>
            <td class="firstcol" align="left">{{ date('j/n', strtotime($row->Date)) }}</td>
            <td align="centre">{{ $row->Code }} </td>
            <td align="left" style="font-size:8px">{{ $row->Warehouse }}</td>
            <td align="right">{{$row->Type}}</td>
            <td align="right">{{$row->Quantity}}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>