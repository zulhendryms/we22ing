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
      font-size: 9px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:5px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 10px;
      padding-top:10px;
      font-size: 11px;
      padding-bottom:10px;
      background: #F5F5F1; 
      font-weight: bold; }    
    th.firstcol {
      width: 50px;
    }
    th.name {
      width: 235px;
    } 
    th.purcahsecurr {
      width: 20px;
    } 
    th.salescurr {
      width: 20px;
    } 
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    <main>
      <table>
        <thead>
          <tr> 
            <th class="firstcol">COMP</th>
            <th class="firstcol">CODE</th>
            <th class="name">NAME</th>
            <th class="purchasecurr">B.PARTNER</th>
            <th class="salescurr">ACCOUNT GROUP</th>
            <th>PURCH.CUR</th>
            <th class="lastcol">SALES CUR</th>
          </tr>
        </thead>
        <tbody>
          @php $BusinessPartnerGroup = ""; @endphp
          @foreach($data as $row)
            @if ($BusinessPartnerGroup != $row->BusinessPartnerGroup)
              <tr>
                <td colspan="7" class="group"><strong>{{$row->BusinessPartnerGroup}}</strong></td>
              </tr>
              @php $BusinessPartnerGroup = $row->BusinessPartnerGroup; @endphp
            @endif
            <tr>
              <td class="firstcol">{{$row->Comp}}</td>
              <td class="firstcol">{{$row->Code}}</td>
              <td align="left">{{$row->Name}}</td>
              <td>{{$row->BusinessPartnerGroup}}</td>
              <td>{{$row->BusinessPartnerAccountGroup}}</td>
              <td>{{$row->PurchaseCurrency}}</td>
              <td class="lastcol">{{$row->SalesCurrency}}</td>
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