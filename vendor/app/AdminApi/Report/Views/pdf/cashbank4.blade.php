<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Credit Card Report</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
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
      border: 1px solid #6b6b6b;
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
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    <main>
      <div class="container" style="padding-left: 8px; padding-right: 8px;">
        <table>
          <thead>
            <tr>
                <th class="firstcol" >No</th>
                <th>Comp</th>
                <th>Loa No</th>
                <th>Staff Name</th>
                <th>Issue Date</th>
                <th>Tour Code</th>
                <th>Supplier Name</th>
                <th>Supplier Group</th>
                <th>Cash Bank</th>
                <th>Amount</th>
                <th>Remarks</th>
                <th>Fin Ref</th>
                <th class="lastcol" align="right" >Bill Status</th>
              </tr>
          </thead>   
          <tbody>
            @php $count=1; $amountTotal=0; @endphp
            @foreach($data as $row)
              <tr>
                <td class="firstcol">{{$count}}</td>
                <td>{{$row->Comp}}</td>
                <td>{{$row->LOAno}}</td>
                <td>{{$row->StaffName}}</td>
                <td>{{$row->IssueDate}}</td>
                <td>{{$row->TourCode}}</td>
                <td>{{$row->SupplierName}}</td>
                <td>{{$row->SupplierGroup}}</td>
                <td>{{$row->CashBank}}</td>
                <td align="right">{{number_format($row->Amount ,2)}}</td>
                <td>{!! $row->Remark !!}</td>
                <td>{{$row->FinRef}}</td>
                <td class="lastcol">{{$row->BillStatus}}</td>
                    @php $amountTotal = $amountTotal + $row->Amount; @endphp
              </tr>
              @php $count++;@endphp
            @endforeach
              <tr>
                <td align="right" colspan="9"><strong>TOTAL</strong></td>
                <td align="right"><strong>{{number_format($amountTotal ,2)}}</strong></td>
                <td colspan="3"></td>
              </tr>
          </tbody>
        </table>
      </div>
      <div style="padding: 13px 20px 13px 20px;">
        <div style="font-size: 14px; color: #858585;"></div>
      </div>
    </main>
  </body>
</html>