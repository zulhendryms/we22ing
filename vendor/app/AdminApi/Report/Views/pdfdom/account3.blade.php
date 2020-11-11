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
        width: auto;
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
        font-size: 10px;
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
        font-size: 12px;
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
        <tr> {{--width:675px--}}
          <th class="firstcol" style="width:80px">CODE</th>
          <th style="width:350px">NAME</th>
          <th style="width:250px">TYPE</th>
          <th class="lastcol">CURRENCY</th>
        </tr>
      </thead>
      <tbody>
        @php $group=""; @endphp
        @foreach($data as $row)
          @if ($group != $row->AccountGroup )
            <tr>
              <td colspan="4" class="group">{{$row->AccountGroup}}</td>
            </tr>
            <tr>
              <td colspan="4" class="group">{{$row->AccountSection}}</td>
            </tr>
            @php $group = $row->AccountGroup; @endphp
          @endif
          <tr>
            <td class="firstcol">{{$row->Code}}</td>
            <td>{{$row->Name}}</td>
            <td>{{$row->AccountType}}</td>
            <td class="lastcol">{{$row->Currency}}</td>
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