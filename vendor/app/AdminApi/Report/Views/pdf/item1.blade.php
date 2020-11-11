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
      table-layout: auto;
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
    th.firstcol{
      width: 50px;
    }   
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr> {{--width:675px--}}
          <th class="firstcol" >COMP</th>
          <th style="width:50px">CODE</th>
          <th style="width:250px">NAME</th>
          <th style="width:100px">ITEM GROUP</th>
          <th style="width:100px">A. GROUP</th>
          <th class="lastcol" style="width:100px">CITY</th>
        </tr>
      </thead>
      <tbody>
        @foreach($data as $row)
          <tr>
            <td class="firstcol">{{$row->Comp}}</td>
            <td>{{$row->Code}}</td>
            <td>{{$row->Name}}</td>
            <td>{{$row->ItemGroup}}</td>
            <td>{{$row->ItemAccountGroup}}</td>
            <td class="lastcol">{{$row->City}}</td>
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