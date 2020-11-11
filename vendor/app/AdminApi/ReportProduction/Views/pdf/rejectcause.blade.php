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
      font-size: 12px;
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
  <main>
    <table>
      <thead>
        <tr> {{--width:675px / 525px--}}
          <th class="firstcol" width="30px">Date Reject</th>
          <th width="50px">Order No</th>
          <th width="200px">Customer</th>
          <th width="130px">Process</th>
          <th width="130px">Item Glass</th>
          <th width="130px">Item Product</th>
          <th width="50px">Glass Thickness</th>
          <th width="130px">Qty</th>
          <th width="200px">Reason</th>
          <th class="lastcol" width="350px" >User</th>
        </tr>
      </thead>
      <tbody>
        @foreach($data as $row)
          <tr>
            <td class="firstcol">{{$row->Date}}</td>
            <td>{{$row->OrderNo}}</td>
            <td>{{$row->Customer}}</td>
            <td>{{$row->Process}}</td>
            <td>{{$row->itemGlass1}}</td>
            <td>{{$row->itemProduct1}}</td>
            <td>{{$row->Thickness}}</td>
            <td align="center">{{$row->QuantityReject}}</td>
            <td>{{$row->NoteReject}}</td>
            <td class="lastcol">{{$row->User}}</td>
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