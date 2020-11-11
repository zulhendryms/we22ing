{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
    p{
      line-height: normal;
      padding: 0px;
      margin: 0px;
    }
    img {
        display: block;
        margin-left: auto;
        margin-right: auto;
        max-width: 250px;
        max-height: 100px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-bottom: 10px;
    }
    
    table th {
      padding: 15px 10px;
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #000000;
      border: 1px solid #5D6975;
      /* border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975; */
      /* background: #888888; */
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #5D6975;
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
<header style="padding-bottom: 5px;">
    <table width="100%">
      <td width="200px" align="center">
          <img src="{{$data[0]->Image}}" width="auto" height="auto"><br>
          <strong>{{$data[0]->CompanyName}}</strong><br>
          Address : {{$data[0]->CompanyAddress}}<br>
          @if($data[0]->CompanyPhone)
              Phone   : {{$data[0]->CompanyPhone}}<br>
          @endif
          E-mail  : {{$data[0]->CompanyEmail}}
      </td>

      <td width="500px">
          <h2>Invoice Payment</h2>
          Date       : {{date('d F Y', strtotime($data[0]->Date))}}</br>
          Code    : {{$data[0]->Code}} </br>
          Print Date : {{date("d F Y H:i:s")}}
      </td>
    </table>
</header>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
      
      <table width="100%">
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:20px">NO</th>
            <th style="width:15px">DATE</th>
            <th style="width:15px">BUSINESSPARTNER</th>
            <th style="width:15px">ACCOUNT</th>
            <th style="width:15px">CUR</th>
            <th class="lastcol" style="width:30px">STATUS</th>
            <th style="width:30px">DEBET</th>
            <th style="width:30px">CREDIT</th>
          </tr>
        </thead>   
        <tbody>
          @php $count=1;  $sumtotal=0; $DebetTotal=0; $CreditTotal=0; @endphp
          @foreach($data as $row)
          <tr>
            <td class="firstcol">{{$count}}</td>
            <td>{{date('d M Y', strtotime($row->Date))}}</td>
            <td>{{$row->BusinessPartner}}</td>
            <td>{{$row->Account}}</td>
            <td>{{$row->Currency}}</td>
            <td>{{$row->Status}}</td>
            <td align="right">{{number_format($row->DebetAmount,2)}}</td>
            <td align="right">{{number_format($row->CreditAmount,2)}}</td>
          </tr>
          @php $count++; 
            $DebetTotal = $DebetTotal + $row->DebetAmount; 
            $CreditTotal = $CreditTotal + $row->CreditAmount; 
          @endphp
        @endforeach
        @php $sumtotal = $sumtotal + $DebetTotal - $CreditTotal; @endphp
            <tr>
              <td colspan="7" align="right"><strong>GRAND TOTAL </strong></td>
              <td class="total" align="right"><strong>{{number_format($sumtotal,2)}}</strong></td>
            </tr>
        </tbody>
      </table>
    
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
  <footer>

  </footer>
</body>
</html>