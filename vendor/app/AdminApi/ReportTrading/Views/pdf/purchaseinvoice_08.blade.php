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
      font-size: 16px;
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
  <main>
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:40px">Comp</th>
            <th style="width:50px">Code</th>
            <th style="width:30px">Date</th>
            <th style="width:150px">Type</th>
            <th style="width:200px">Account</th>
            <th style="width:200px">Description</th>
            <th style="width:150px">Amount</th>
            <th class="lastcol" style="width:150px">Total</th>
          </tr>
        </thead>   
        <tbody>
          @php $totalAmount=0; $totalall=0; @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol">{{$row->Comp}}</td>
              <td>{{$row->Code}}</td>
              <td align="left">{{$row->Date}}</td>
              <td align="left">{{$row->Type}}</td>
              <td align="left">{{$row->Account}}</td>
              <td align="left">{{$row->Description}}</td>
              <td align="right">{{number_format($row->Amount ,2,',','.')}}</td>
              <td class="lastcol" align="right">{{number_format($row->Amount1 ,2,',','.')}}</td>
            </tr>
            @php 
            $Amount1= $row->Amount1;
            $totalAmount += $Amount1; 

            $Amount1= $row->Amount1;
            $totalall += $Amount1; 
            @endphp
          @endforeach
           <tr>
              <td colspan="6" align="right"><strong>TOTAL</strong></td>
              <td class="total" align="right"><strong>{{number_format($totalAmount,2,',','.')}}</strong></td>
              <td class="total" align="right"><strong>{{number_format($totalall,2,',','.')}}</strong></td>
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