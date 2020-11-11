<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{$reporttitle}}</title>
    <style type="text/css">
      table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
      }
      p{
        display: inline;
      }

      table th {
        padding: 15px 10px;
        color: #5d6975;
        border-bottom: 1px solid #c1ced9;
        white-space: nowrap;
        font-weight: bold;
        color: #000000;
        border: 2px solid #5d6975;
        /* border-top: 1px solid  #5D6975;
            border-bottom: 1px solid  #5D6975; */
        background: #888888;
        font-size: 14px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        padding-right: 10px;
      }
      table td {
        border: 2px solid #5d6975;
        vertical-align: top;
        font-size: 12px;
        padding-top: 10px;
        padding-bottom: 2px;
        padding-left: 2px;
        padding-right: 1px;
      }
      .bold {
        font-weight: 600;
      }
      .floatR {
        float: right;
      }
      .floatL {
        float: left;
      }
      .alignR {
        text-align: right;
      }
      .alignM {
        text-align: center;
      }
      .f14{
          font-size: 14pt;
          font-weight: 600;
      }
      .uline{
        text-decoration: underline;
      }
      table td.firstcol {
        padding-left: 5px;
      }
      table td.lascol {
        padding-right: 5px;
      }
      table th.firstcol {
        padding-left: 5px;
      }
      table th.lascol {
        padding-right: 5px;
      }
      table td.group {
        padding-left: 8px;
        padding-top: 8px;
        font-size: 12px;
        padding-bottom: 8px;
        background: #f5f5f1;
        font-weight: bold;
      }
      h2,h3{
        color: #2a4886;
        line-height: 15px;
      }
      td.z{
        border-top: none;
        border-bottom: none;
        border-left: none;
        border-right: none;
      }
    </style>
  </head>

@if($data[0]->CompanyLogo)
    <header>
      <span><img src="{{$data[0]->CompanyLogo}}" width="100%" height="auto"></span>
  </header>
@endif
  <body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    <main>
    <table width="100%" style="border:none !important">
      <tr>
        <td width="65%" class="z">
          To  :{{$data[0]->Customer}}</br>
          Address : {!! $data[0]->BillingAddress !!}</br>
          <br>
          Attn <span style="padding-right:36px;"></span>: {{$data[0]->ContactPerson}}</br>
          Tel <span style="padding-right:44px;"></span>: {{$data[0]->PhoneNo}}</br>
          Fax <span style="padding-right:42px;"></span>: {{$data[0]->FaxNo}}
        </td>
        <td width="35%" class="z">
          <span class="f14">TAX INVOICE</span><br>
            Invoice No <span style="padding-right:12px;"></span>: {{$data[0]->Code}}</br>
            Tour Code <span style="padding-right:12px;"></span>: {{$data[0]->TourCode}}</br>
            Terms <span style="padding-right:37px;"></span>: {{$data[0]->PaymentTerm}}</br>
            {{-- Your Ref <span style="padding-right:23px;"></span>: {{$data[0]->YourRef}}</br> --}}
            Our Ref <span style="padding-right:30px;"></span>: {{$data[0]->YourRef}}</br>
            Salesperson <span style="padding-right:3px;"></span>: {{$data[0]->SalesPerson}}</br>
            Date <span style="padding-right:47px;"></span>: {{$data[0]->Date}}
        </td>
      </tr>
    </table>

<hr>


      <div style="padding-left: 8px; padding-right: 8px;">
        <table>
          <thead>
            <tr>
              <th class="firstcol" width="30px">NO</th>
              <th width="500px">Description</th>
              <th>Qty</th>
              <th>Price</th>
              <th class="lastcol" width="55px">Amount</th>
            </tr>
          </thead>
          <tbody>
            @php $count=1; $sumAdult=0; $sumChild=0; $sumAmount=0; @endphp 
            @foreach($data as $row)
            <tr>
              <td class="firstcol alignM">{{$count}}</td>
              <td>{{$row->ItemName}}
                @if($row->Descriptions)<br><span style="text-align: justify">{!! $row->Descriptions !!}</br>@endif
                  @if($row->NoteDetail): </br>&nbsp;{!! $row->NoteDetail !!}</span>@endif
              </td>
              <td class="alignM" style="white-space:nowrap">
                @if ($row->QtyAdult > 0) {{$row->QtyAdult}}  @endif
                @if ($row->QtyChild > 0) </br>{{$row->QtyChild}}  @endif
              </td>
              <td class="alignM" style="white-space:nowrap">
                @if ($row->QtyAdult > 0) {{number_format($row->PriceAdult,2)}} @endif
                @if ($row->QtyChild > 0) </br>{{number_format($row->PriceChild,2)}} @endif
              @php $sumAdult += $row->QtyAdult * $row->PriceAdult;
                   $sumChild += $row->QtyChild * $row->PriceChild;  
                   $sumAmount += $sumAdult + $sumChild;
                   @endphp
              <td class="lastcol alignM">
                {{number_format($sumAdult ,2)}}</br>
                {{number_format($sumChild ,2)}}
              </td>
            </tr>
            @php $count++; $sumAdult=0; $sumChild=0;@endphp 
            @endforeach
            @php
            $grandtotal = $sumAmount + $row->AdditionalAmount - $row->DiscountAmount;
        @endphp
          @if ($data[0]->AdditionalAmount)
          <tr>
            <td colspan="4" align="right"><strong>{{$row->AdditionalAccount}}</strong></td>
            <td class="total alignM" align="right"><strong>{{number_format($row->AdditionalAmount,2)}}</strong></td>
          </tr>
          @endif
          @if ($data[0]->DiscountAmount)
          <tr>
            <td colspan="4" align="right"><strong>{{$row->DiscountAccount}}</strong></td>
            <td class="total alignM" align="right"><strong>{{number_format($row->DiscountAmount,2)}}</strong></td>
          </tr>
          @endif
          <tr>
            <td colspan="4" align="right"><strong>GRAND TOTAL {{$data[0]->Cur}}</strong></td>
            <td class="total alignM" align="right"><strong>{{number_format($grandtotal,2)}}</strong></td>
          </tr>
          </tbody>
        </table>
      </div>
      <div style="padding: 13px 20px 13px 20px;">
        <div style="font-size: 14px; color: #858585;"></div>
      </div>


        <table width="100%" style="padding-left: 8px; padding-right: 8px;">
          <thead>
            <tr><th align="center">Note</th></tr>
          </thead>
          <tbody>
            <tr>
                <td style="text-align: justify; min-height: 25px">
                  <p>{!! $data[0]->Note !!}</p>
                </td>
            </tr>
          </tbody>
        </table>

      <div>
        <hr>
      </div>

      <div style="margin:0px; padding-bottom:90px;">
        
          <span class="bold">Remarks:</span> Cheque should be crossed and payable to <span>ACE TOURS & TRAVEL PTE LTD</span><br>
          <p class="floatL">
          <span class="bold uline">Payable via Remittance:</span><br>
          <span class="bold">Account Name: </span>{{$data[0]->AccountName}}<br>
          <span class="bold">Bank Address: </span>{{$data[0]->bAddress}}<br>
          <span class="bold">Bank Name: </span>{{$data[0]->bName}}<br>
          <span class="bold">Account No: </span>{{$data[0]->AccountNo}}<br>
          <span class="bold">Swift Code: </span>{{$data[0]->SwiftCode}}<br>
        </p>
        <p class="floatL" style="padding-left:200px">
          <br>
          <br>
          <br>
          <br>
          <span class="bold">Bank Code: </span>{{$data[0]->bCode}}<br>
          <span class="bold">Branch Code: </span>{{$data[0]->BranchCode}}<br>
        </p>
        <p class="floatR">
          PAYNOW(SGD)<br>
          <span><img style="width:100px; height:100px;" src="http://public.ezbooking.co/logo/acetours_paylah.jpg" alt=""></span>
        </p>
      </div>
      <div>
        <br>
        <hr style="width:100%;">
      </div>
      <div class="floatL">
        <p>
          <span class="bold">ACE TOURS & TRAVEL PTE LTD</span>
        </p>
        <br>
        <br>
        <p>____________________________<br>
        Authorized Signature</p>
      </div>
    </main>
  </body>
</html>
