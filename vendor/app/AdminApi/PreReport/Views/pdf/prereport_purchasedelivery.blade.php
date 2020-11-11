{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>prereport-purchasedelivery</title>
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
          <img src="{{$data[0]->CompanyLogo}}" width="auto" height="auto"><br>
          <strong>{{$data[0]->CompanyName}}</strong><br>
          Address : {{$data[0]->CompanyAddress}}<br>
          @if($data[0]->CompanyPhone)
              Phone   : {{$data[0]->CompanyPhone}}<br>
          @endif
          E-mail  : {{$data[0]->CompanyEmail}}
      </td>


      <td width="500px">
          <h1>Purchase Delivery</h1>
          Date       : {{date('d F Y', strtotime($data[0]->Date))}}<br>
          Code       : {{$data[0]->Code}} <br>
          Supplier   : {{$data[0]->Supplier}}<br>
          Warehouse   : {{$data[0]->Warehouse}}<br>
          Shipping Address    : {{$data[0]->ShippingAddress}}<br>
          @if($data[0]->SupplierPhone)
              Phone    : {{$data[0]->SupplierPhone}} <br>
          @endif
          Print Date : {{date("d F Y H:i:s")}}
      </td>
  </table>
</header>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
      @if ($data[0]->Note)
        <table style="min-height: 50px;">
          <tbody>
            <tr>
              <td>Note : {!! $data[0]->Note !!}</td>
            </tr>
          </tbody>
        </table>
    @endif
      <table>
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:20px">NO</th>
            <th style="width:300px">ITEM</th>
            <th class="lastcol" style="width:30px">QTY</th>
          </tr>
        </thead>   
        <tbody>
          @php $count=1; $TotalAmount=0; $sumtotal=0; $qtytotal=0;  @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol">{{$count}}</td>
              <td>{{$row->ItemName}}<br>{{$row->itemNote}}</td>
              <td align="right">{{$row->Qty}}</td>
                  @php
                    $qtytotal = $qtytotal + $row->Qty;
                  @endphp
            </tr>
            @php $count++; $total=0;  @endphp
          @endforeach
            <tr>
              <td colspan="2" align="right"><strong>Total </strong></td>
              <td class="total" align="right"><strong>{{$qtytotal}}</strong></td>
            </tr>
        </tbody>
      </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
  <footer>
   <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
      <div style="font-size:12px; margin-right: 100px; float: right;">
        <p style="margin-bottom: 40px;">Authorized By</p>
        <p>(________________)</p>
      </div>
    </div>
  </footer>
</body>
</html>