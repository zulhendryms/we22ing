<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
      body{
        margin: 0px;
        font-family: Tahoma, Geneva, sans-serif;
        padding-bottom: 10px;
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
          color: #5D6975;
          border-bottom: 1px solid #C1CED9;
          white-space: nowrap;
          font-weight: bold; 
          color: #000000;
          border: 1px solid #5D6975;
          font-size: 14px;
          padding-bottom:15px;
          padding-left:10px;
          padding-right:10px;
        }
        table td {
          border: 1px solid #5D6975;
          vertical-align: top;
          font-size: 12px;
          padding-top:2px;
          padding-bottom:2px;
          padding-left:2px;
          padding-right:1px;
        }
      </style>
</head>

{{-- PURCHASE ORDER --}}
@if($reportname == 'purchaseorder')
    <body>
        <header>
            <table width="100%">
                <td width="200px" align="center">
                    <img src="{{$data[0]->CompanyLogo}}" width="auto" height="60px"><br>
                    <strong>{{$data[0]->CompanyName}}</strong><br>
                    Address : {{$data[0]->CompanyAddress}}<br>
                    @if($data[0]->CompanyPhone)
                        Phone   : {{$data[0]->CompanyPhone}}<br>
                    @endif
                    E-mail  : {{$data[0]->CompanyEmail}}
                </td>
                <td width="500px">
                    <h2>Purchase Order</h2>
                    Date <s style="padding-left:57px"></s>: {{date('d F Y', strtotime($data[0]->Date))}}</br>
                    No. PO<s style="padding-left:48px"></s>: {{$data[0]->Code}} </br>
                    Costumer<s style="padding-left:35px"></s>: {{$data[0]->Customer}}</br>
                    @if($data[0]->CustomerAddress)
                    Address<s style="padding-left:35px"></s>: {{$data[0]->CustomerAddress}} </br>@endif
                    @if($data[0]->CostumerPhone)
                    Phone<s style="padding-left:35px"></s>: {{$data[0]->CostumerPhone}} </br>@endif
                    Warehouse<s style="padding-left:25px"></s>: {{$data[0]->Warehouse}} </br>
                    @if ($data[0]->PaymentTerm)
                    PaymentTerm<s style="padding-left:10px"></s>: {{$data[0]->PaymentTerm}} </br>@endif
                    Print Date<s style="padding-left:32px"></s>: {{date("d F Y H:i:s")}}
                </td>
            </table>
        </header>
    </body>
  @endif
{{-- PURCHASE-ORDER --}}

{{-- PURCHASE-REQUEST --}}
@if($reportname == 'purchaserequest')
<body>
<header style="padding-bottom:1px">
  <table width="100%">
      <td width="200px" align="center">
          <img src="{{$data[0]->CompanyLogo}}" width="auto" height="60px"><br>
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
</body>
@endif
{{-- PURCHASE-REQUEST --}}

{{-- PAYMENT REQUEST --}}
@if($reportname == 'paymentrequest')
  <body>
    <header style="padding-bottom:1px">
      <table width="100%">
          <td width="200px" align="center">
              <img src="{{$data[0]->CompanyLogo}}" width="auto" height="60px"><br>
              <strong>{{$data[0]->CompanyName}}</strong><br>
              Address : {{$data[0]->CompanyAddress}}<br>
              @if($data[0]->CompanyPhone)
                  Phone   : {{$data[0]->CompanyPhone}}<br>
              @endif
              E-mail  : {{$data[0]->CompanyEmail}}
          </td>

              <td width="500px">
                  <h1>Payment Request</h1>
                  @if($data[0]->RequestDate)
                  Date : {{$data[0]->RequestDate}}<br> @endif
                  {{-- @if($data[0]->Date)
                  Date : {{$data[0]->Date}}<br> @endif --}}
                  Code : {{$data[0]->RequestCode}}<br>
                  Code Reff : {{$data[0]->CodeReff}}<br> 
                  Department : {{$data[0]->Department}}<br>
                  Business Partner : {{$data[0]->BusinessPartner}}<br>
                  @if ($data[0]->Type != 'Transfer')
                    Account       : {{$data[0]->AccountCashBank}} <br>
                  @endif
                  Print Date : {{date("d F Y H:i:s")}} 
              </td>
      </table>
    </header>
  </body>
@endif
  {{-- PAYMENT REQUEST --}}

{{-- PURCHASE INVOICE --}}
@if($reportname == 'purchaseinvoice')
<body>
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
        <h1>Purchase Invoice</h1>
        Date       : {{date('d F Y', strtotime($data[0]->Date))}}</br>
        Code       : {{$data[0]->Code}} </br>
        Costumer   : {{$data[0]->Supplier}}</br>
        Address    : {{$data[0]->SupplierAddress}} </br>
        @if($data[0]->SupplierPhone)
            Phone      : {{$data[0]->SupplierPhone}} </br>@endif
        @if($data[0]->Warehouse)
            Warehouse  : {{$data[0]->Warehouse}} </br>@endif
        CodeReff   : {{$data[0]->CodeReff}} </br>
        @if($data[0]->PaymentTerm)
            PaymentTerm : {{$data[0]->PaymentTerm}} </br>@endif
        Print Date : {{date("d F Y H:i:s")}}
    </td>
  </table>
</body>
@endif
{{-- PURCHASE INVOICE --}}
{{-- PURCHASE INVOICE PAYMENT --}}
@if($reportname == 'invoicepayment')
<body>
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
          Code     : {{$data[0]->Code}} </br>
          Print Date : {{date("d F Y H:i:s")}}
      </td>
  </table>
</header>
</body>
@endif
{{-- PURCHASE INVOICE PAYMENT --}}
{{-- PREREPORT CASHBANK --}}
@if($reportname == 'cashbank')
<body>
<header style="padding-bottom: 5px;">
  <table width="100%">
      <td width="200px" align="center">
          <img src="{{$data[0]->CompanyLogo}}" width="auto" height="60px"><br>
          <strong>{{$data[0]->CompanyName}}</strong><br>
          Address : {{$data[0]->CompanyAddress}}<br>
          @if($data[0]->CompanyPhone)
              Phone   : {{$data[0]->CompanyPhone}}<br>
          @endif
          E-mail  : {{$data[0]->CompanyEmail}}
      </td>

      <td width="500px">
          <h1>CashBank</h1>
          Date       : {{date('d F Y', strtotime($data[0]->Date))}}<br>
          Code       : {{$data[0]->CashBankCode}} <br>
          Type       : {{$data[0]->Type}} <br>
          @if ($data[0]->Type == "Income" && $data[0]->Type == "Expense" && $data[0]->Type == "Transfer")
              Business Partner   : {{$data[0]->BusinessPartner}}<br>
          @endif
          @if ($data[0]->Type != 'Transfer')      
            Account       : {{$data[0]->AccountName}} <br>
          @endif
          CodeReff    : {{$data[0]->CodeReff}} <br>
          Print Date : {{date("d F Y H:i:s")}}
      </td>
  </table>
</header>
</body>
@endif
{{-- PREREPORT CASHBANK --}}




</html>