<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$reporttitle}}</title>
</head>
<style type="text/css">
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
      border: 1px solid black;
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid black;
      vertical-align: top;
      font-size: 13px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
    }
    .z{
        border-top: none;
        border-bottom: none;
        border-left: none;
        border-right: none;
    }
    .f12{
        font-size: 12pt;
    }
    hr.hr{
        border-top: 3px double black;
    }
    td.r{
        text-align: right;
        font-weight: bold;
    }
    td.bnone{
        border-top: none;
        border-bottom: none;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    </style>
    <header>
        <table>
            <td class="z"><img src="{{$data[0]->CompanyLogo}}" width="auto" height="auto"></td>
            <td class="z">
                <h1><strong>{{$data[0]->CompanyName}}</strong></h1>
                <span style="font-size: 10pt">
                    Address : {{$data[0]->CompanyAddress}}<br>
                    @if($data[0]->CompanyPhone)
                        Phone   : {{$data[0]->CompanyPhone}}<br>
                    @endif
                    @if($data[0]->CompanyEmail)
                    E-mail  : {{$data[0]->CompanyEmail}}</span></br>
                    @endif
            </td>
        </table>
    </header>
    <hr class="hr">
<body>
    <table width="100%">
        <tbody>
            <td width="40%">
                Messrs : {!! $data[0]->FullAddress !!}
            </td>
            <td width="60%">
                <h2 style="font-weight: bold;"> BILLING INVOICE </h2>
                Reff No <span style="padding-right: 51px"></span>:  {{$data[0]->CodeReff}}</br>
                Date Of Issue <span style="padding-right: 19px"></span>: {{$data[0]->Date}}</br>
                Customer <span style="padding-right: 41px"></span>: {{$data[0]->BusinessPartner}}</br>
               @if($data[0]->Project) Project <span style="padding-right: 52px"></span>: {{$data[0]->Project}} @endif
            </td>
        </tbody>
    </table>
    <table width="100%">
        <thead>
            <tr>
                <th align="center" width="5%">No.</th>
                <th align="center" width="85%">Item</th>
                <th align="center" width="10%%">Qty</th>
                <th align="center"  width="10%" colspan="2">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $subTotal=0; $sumTotal=0; $count=1;@endphp
            @foreach($data as $row)
            <tr>
                <td>{{$count}}</td>
                <td class="bnone">{{$row->Item}}</br>Note: {{$row->Note}}</td>
                <td class="bnone" align="center">{{$row->Qty}}</td>
                <td class="bnone" align="right" colspan="2">{{number_format($row->Amount,2)}}</td>
            </tr>
            @php $subTotal = $subTotal + $row->Amount; $count++;@endphp
            @endforeach
        </tbody>
        @php $sumTotal = $sumTotal + $subTotal * $data[0]->Rate; @endphp
        <tbody>
            <tr>
                <td class="r" colspan="3">Sub Total </td>
                <td style="font-weight: bold; border-right:none;">{{$data[0]->CurrencyCode}}</td>
                <td colspan="2" style="border-left: none; text-align:right;">{{number_format($subTotal,2)}}</td>
            </tr>
            <tr>
                <td class="r" colspan="3">Rate </td>
                <td style="font-weight: bold; border-right:none;">{{$data[0]->CurrencyCode}}</td>
                <td  colspan="2" style="border-left: none; text-align:right;">{{number_format($data[0]->Rate,2)}}</td>
            </tr>
            <tr>
                <td class="r" colspan="3">TOTAL </td>
                <td style="font-weight: bold; border-right:none;">{{$data[0]->CurrencyCode}}</td>
                <td colspan="2" style="border-left: none; text-align:right;">{{number_format($sumTotal,2)}}</td>
            </tr>
        </tbody>
    </table>
    
</body>
</html>