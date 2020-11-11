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
      padding-right:1px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 8px;
      padding-top:8px;
      font-size: 13px;
      padding-bottom:8px;
      background: #F5F5F1; 
      font-weight: bold; }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        <tr> {{--width:675px--}}
          <th class="firstcol" style="width:40px">Comp</th>
          <th style="width:200px">NAME</th>
          <th style="width:60px">GROUP</th>
          <th style="width:50px">PHONE</th>
          <th style="width:100px">E-MAIL</th>
          <th class="lastcol" style="width:100px">WEB SITE</th>
        </tr>
      </thead>
      <tbody>
        @php $group=""; @endphp
        @foreach($data as $row)
          @if ($group != $row->City)
          <tr>
            <td colspan="6" class="group"><strong>{{$row->City}}({{$row->Address}})</strong></td>
          </tr>
          @php $group = $row->City;  @endphp
          @endif
          <tr>
            <td class="firstcol">{{$row->Comp}}</td>
            <td>{{$row->Name}}</td>
            <td>{{$row->BusinessPartnerGroup}}</td>
            <td>{{$row->PhoneNumber}}</td>
            <td>{{$row->Email}}</td>
            <td class="lastcol">{{$row->Website}}</td>
          </tr>
        @endforeach
        {{--
        <tr>
          <td colspan="4">SUBTOTAL</td><td class="total">$5,200.00</td>
        </tr>
        <tr>
          <td colspan="4">TAX 25%</td><td class="total">$1,300.00</td>
        </tr>
        <tr>
          <td colspan="4" class="grand total">GRAND TOTAL</td><td class="grand total">$6,500.00</td>
        </tr>
        --}}
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>