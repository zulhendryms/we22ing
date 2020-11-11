<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Expense Report</title>
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
      <div class="container" style="padding-left: 8px; padding-right: 8px;">
        <table>
          <thead>
            <tr>
                <th class="firstcol" >No</th>
                <th>COMP</th>
                <th>TOUR CODE</th>
                <th>SOURCE</th>
                <th>DATE</th>
                <th>DESCRIPTION</th>
                <th>ACCOUNT PAYABLE</th>
                <th>CUR</th>
                <th>GROSS AMT</th>
                <th>GST%</th>
                <th>GST AMT</th>
                <th>NETT AMT</th>
                <th>REMARKS</th>
                <th>PAID BY</th>
                <th class="lastcol" align="right">TOUR STATUS</th>
              </tr>
          </thead>   
          <tbody>
            @php $count=1; $grosstotal=0; $gsttotal=0; $gstamounttotal=0; $netamounttotal=0;  @endphp
            @foreach($data as $row)
              <tr>
                <td class="firstcol">{{$count}}</td>
                <td>{{$row->Comp}}</td>
                <td>{{$row->TourCode}}</td>
                <td>{{$row->Source}}</td>
                <td style="width:100px">{{$row->Date}}</td>
                <td>{{$row->Description}}</td>
                <td>{{$row->Accountpay}}</td>
                <td>{{$row->Currency}}</td>
                <td align="right">{{$row->GrossAmt}}</td>
                <td align="right">{{$row->Gst}}</td>
                <td align="right">{{$row->GstAmount}}</td>
                <td align="right">{{$row->NettAmount}}</td>
                <td>{!! $row->Remarks !!}</td>
                <td>{{$row->PaidBy}}</td>
                <td class="lastcol" >{{$row->TourStatus}}</td>
                    @php
                    $grosstotal =$grosstotal + $row->GrossAmt;
                    $gsttotal =$gsttotal + $row->Gst;
                    $gstamounttotal =$gstamounttotal + $row->GstAmount;
                    $netamounttotal =$netamounttotal + $row->NettAmount;
                    @endphp
              </tr>
              @php $count++;@endphp
            @endforeach
              <tr>
                <td align="right" colspan="8" style="font-weight:600"><strong>TOTAL</strong></td>
                <td align="right"><strong>{{number_format($grosstotal ,2,',','.')}}</strong></td>
                <td align="right"><strong>{{number_format($gsttotal ,2,',','.')}}</strong></td>
                <td align="right"><strong>{{number_format($gstamounttotal ,2,',','.')}}</strong></td>
                <td align="right"><strong>{{number_format($netamounttotal ,2,',','.')}}</strong></td>
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