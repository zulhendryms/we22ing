{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>prereport-purchaserequestversion</title>
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
  <header style="padding-bottom:1px">

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
                  <h1>Purchase Request</h1>
                  Date: {{date('d F Y', strtotime($data[0]->Date))}}<br>
                  Code: {{$data[0]->Code}}<br>
                  Code Reff: {{$data[0]->CodeReff}}<br> 
                  Department: {{$data[0]->Department}}<br>
                  Print Date : {{date("d F Y H:i:s")}} 
              </td>
        
      </table>
  </header>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
   
      @if ($data[0]->Note)
        <table height="50px" style="padding-top:2px; padding-bottom:1px">
          <tbody>
            <tr>
              <td>
                Note : {!! $data[0]->Note !!}
                @if ($data[0]->Approval1Note) </br><strong>Note dari {{$data[0]->Approval1}}:</strong> {{$data[0]->Approval1Note}} @endif
                @if ($data[0]->Approval2Note) </br><strong>Note dari {{$data[0]->Approval2}}:</strong> {{$data[0]->Approval2Note}} @endif
                @if ($data[0]->Approval3Note) </br><strong>Note dari {{$data[0]->Approval3}}:</strong> {{$data[0]->Approval3Note}} @endif       
              </td>
            </tr>
          </tbody>
        </table>
      @endif

      <table width="100%">
        <thead>{{--width:675px--}}
            <th class="firstcol" style="width:30px">NO</th>
            <th style="width:300px">ITEM</th>
            <th style="width:30px">QTY</th>
            <th colspan = "2"  style="width:200px; {{$data[0]->SupplierChoosen == $data[0]->Supplier1 ? 'background-color:gray' : ''}}">
              {{$data[0]->Supplier1 ?: 'Supplier Name'}}<br>{{$data[0]->PaymentTerm1}}
              @if ($data[0]->SupplierChoosen == $data[0]->Supplier1)<br><i style="font-size:9pt">Chosen Supplier</i>@endif
            </th>
            @if ($data[0]->Supplier2)
            <th colspan = "2"  style="width:200px; {{$data[0]->SupplierChoosen == $data[0]->Supplier2 ? 'background-color:gray' : ''}}">
              {{$data[0]->Supplier2}}<br>{{$data[0]->PaymentTerm2}}
              @if ($data[0]->SupplierChoosen == $data[0]->Supplier2)<br><i style="font-size:9pt">Chosen Supplier</i>@endif
            </th>
            @endif
            @if ($data[0]->Supplier3)
            <th colspan = "2" class="lastcol" style="width:200px; {{$data[0]->SupplierChoosen == $data[0]->Supplier3 ? 'background-color:gray' : ''}}">
              {{$data[0]->Supplier3}}<br>{{$data[0]->PaymentTerm3}}</th>
              @if ($data[0]->SupplierChoosen == $data[0]->Supplier3)<br><i style="font-size:9pt">Chosen Supplier</i>@endif
            @endif
          </tr>
        </thead>   
        <tbody>
          @php $count=1;  $totalamount1=0; $totalamount2=0; $totalamount3=0; @endphp
          @foreach($data as $row)
            <tr>
              <td class="firstcol">{{$count}}</td>
              <td>{{$row->ItemName}} @if($row->ItemNote) - {{$row->ItemNote}}@endif @if($row->CostCenter)</br>For : {{$row->CostCenter}}@endif</td>
              <td align="right">{{$row->Qty}} {{$row->ItemUnit}}</td>
              <td align="right">{{number_format($row->Price1 ,2)}}</td>
              <td align="right">{{number_format($row->Price1 * $row->Qty ,2)}}</td>
              @if ($data[0]->Supplier2)
              <td align="right">{{number_format($row->Price2 ,2)}}</td>
              <td align="right">{{number_format($row->Price2 * $row->Qty ,2)}}</td>
              @endif
              @if ($data[0]->Supplier3)
              <td align="right">{{number_format($row->Price3 ,2)}}</td>
              <td align="right">{{number_format($row->Price3 * $row->Qty ,2)}}</td>
              @endif
                  @php
                    $totalamount1 = $totalamount1 + ($row->Qty * $row->Price1);
                    $totalamount2 = $totalamount2 + ($row->Qty * $row->Price2);
                    $totalamount3 = $totalamount3 + ($row->Qty * $row->Price3);
                    $discountTotal1 = $totalamount1 - $row->DiscountAmount1;
                    $discountTotal2 = $totalamount2 - $row->DiscountAmount2;
                    $discountTotal3 = $totalamount3 - $row->DiscountAmount3;
                  @endphp
            </tr>
            @php $count++; $total=0;  @endphp
          @endforeach
          
            <tr>
              <td colspan="3" align="right"><strong>Discount / TOTAL {{$data[0]->Cur}}</strong></td>
              
              <td class="total" align="right"><strong>{{number_format($row->DiscountAmount1 ,2)}}</strong></td>
              <td class="total" align="right"><strong>{{number_format($discountTotal1 ,2)}}</strong></td>
              
              @if ($data[0]->Supplier2)
              <td class="total" align="right"><strong>{{number_format($row->DiscountAmount2 ,2)}}</strong></td>
              <td class="total" align="right"><strong>{{number_format($discountTotal2 ,2)}}</strong></td>
              @endif
              @if ($data[0]->Supplier3)
              <td class="total" align="right"><strong>{{number_format($row->DiscountAmount3 ,2)}}</strong></td>
              <td class="total" align="right"><strong>{{number_format($discountTotal3 ,2)}}</strong></td>
              @endif
            </tr>
        </tbody>
      </table>
    
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
  <footer>
   <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
        @if ($data[0]->Requestor1)
        <div style="font-size:11px; padding-right: 27px; float: left;">
          <p style="margin-bottom: 40px;">{{$data[0]->Requestor1}}</p>
          <p>{{$data[0]->ap1}}</p>
        </div>
        @endif
        @if ($data[0]->Requestor2)
        <div style="font-size:11px; padding-right: 27px; float: left;">
          <p style="margin-bottom: 40px;">{{$data[0]->Requestor2}}</p>
          <p>{{$data[0]->ap2}}</p>
        </div>
        @endif
        @if ($data[0]->Requestor3)
        <div style="font-size:11px; padding-right: 20px; float: left;">
          <p style="margin-bottom: 40px; ">{{$data[0]->Requestor3}}</p>
          <p>{{$data[0]->ap3}}</p>
        </div>
        @endif 
        
        @if ($data[0]->Purchaser)
          <div style="font-size:12px; padding-right: 10px; float: right; ">
            <p style="margin-bottom: 40px;">{{$data[0]->Purchaser}}<br>Purchaser</p>
            <p></p>
          </div>
        @endif
        @if ($data[0]->Approval3)
          <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
            <p style="margin-bottom: 40px;">Approval 3 : {{$data[0]->Approval3}}
              <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval3Date}}
              <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval3Hour}}</p>
            <p></p>
          </div>
        @endif
        @if ($data[0]->Approval2)
        <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
          <p style="margin-bottom: 40px; ">Approval 2 : {{$data[0]->Approval2}}
            <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval2Date}}
            <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval2Hour}}</p>
            <p></p>
          </div>
          @endif
          @if ($data[0]->Approval1)
          <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
            <p style="margin-bottom: 40px;">Approval 1 : {{$data[0]->Approval1}}
              <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval1Date}}
              <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval1Hour}}</p>
            <p></p>
            </div>
          @endif
    </div>
  </footer>
</body>
</html>