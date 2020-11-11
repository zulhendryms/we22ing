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
      font-size: 10px;
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
          <th class="firstcol" style="width:40px">Date</th>
          <th style="width:100px">Date From</th>
          <th style="width:80px">Date End</th>
          <th style="width:80px">Activities</th>
        </tr>
      </thead>
      <tbody> 
        @php $group=""; @endphp
        @foreach($data as $row)
          @if ($group != $row->UserName)
            <tr>
              <td colspan="6" class="group"><strong>{{$row->UserName}} {{$row->Name}}</strong></td>
            </tr>
            @php $group = $row->UserName;  @endphp
          @endif
          <tr>
            <td class="firstcol">{{ $row->Date}}</td>
            <td>{{ $row->DateFrom}}</td>
            <td>{{ $row->DateEnd}}</td>
            <td>{{ $row->Activities}}</td>
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