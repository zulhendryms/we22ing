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
      width: 10px;
    }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr>
          <th class="firstcol">COMP</th>
          <th>CODE</th>
          <th>Date</th>
          <th>Prime Mover</th>
          <th>Department</th>
          <th>Quantity</th>
          <th  class="lastcol" >Note</th>
        </tr>
      </thead>
      <tbody>
        @php $group=""; @endphp
        @foreach($data as $row)
          @if ($group != $row->PrimeMover)
          <tr>
            <td colspan="7" class="group"><strong>{{$row->PrimeMover}}</strong></td>
          </tr>
          @php $group = $row->PrimeMover;  @endphp
          @endif
          <tr>
            <td class="firstcol">{{$row->Company}}</td>
            <td>{{$row->Code}}</td>
            <td>{{$row->Date}}</td>
            <td>{{$row->PrimeMover}}</td>
            <td>{{$row->Department}}</td>
            <td>{{$row->Quantity}}</td>
            <td class="lastcol">{{$row->Note}}</td>
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