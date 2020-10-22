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
      /* border: 1px solid black; */
      vertical-align: top;
      font-size: 13px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
    }
    .b{ border-bottom: 1px solid black;}
    .t{ border-top: 1px solid black;}
    .r{ border-right: 1px solid black;}
    .l{ border-left: 1px solid black;}
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
    .note{ text-decoration: underline; font-style: italic; font-weight: bold;}
    </style>
    <header>
        <table>
            <td class="z">
                @if($company->Image != null)
                <img src="{{$company->Image}}" width="auto" height="auto">
                @else
                <img src="{{$data[0]->CompanyLogo}}" width="auto" height="auto">
                @endif
            </td>
            <td class="z">
                <h1><strong>{{$company->Name}}</strong></h1>
                <span style="font-size: 10pt">
                    Address : {{$company->FullAddress}}<br>
                    @if($company->PhoneNo)
                        Phone   : {{$company->PhoneNo}}<br>
                    @endif
                    @if($company->Email)
                    E-mail  : {{$company->Email}}</span></br>
                    @endif
            </td>
        </table>
    </header>
    <hr class="hr">
<body>
    <table width="100%">
        <tbody>
            <tr>
                <td></td>
                <td colspan="2"><span style="font-size: 15pt"><strong>BILLING INVOICE</strong></span></td>
            </tr>
            <tr>
                <td width="60%">
                    Messrs : <br />
                    {{$data[0]->BusinessPartner}} </br>
                    {{$data[0]->FullAddress}} </br>
                    Attn: {{$data[0]->ContactPerson}}</br>
                    Phone: {{$data[0]->PhoneNumber}}
                </td>
                <td width="8%">
                    Reff No </br>
                    Date of Issue </br>
                    TOP </br>
                    Project </br>
                    Harbour </br>
                </td>
                <td width="22%">
                    :  {{$data[0]->Code}}</br>
                    :  {{$data[0]->Date}}</br>
                    :  {{$data[0]->PaymentTerm}}</br>
                    :  {{$data[0]->Project}}</br>
                    : </br>
                </td>
            </tr>
        </tbody>
    </table>

    <table width="100%">
        <thead>
            <tr>
                <th align="center" width="70%">Description</th>
                <th align="center" colspan="3" width="10%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $subTotal=0; $sumTotal=0; $count=1;  @endphp
            @foreach($data as $row)
            @php 
                 $total = 0; $total = $row->Amount * $row->Quantity;
                 $subTotal = $subTotal + $row->Amount * $row->Quantity;
                 $count++;
            @endphp
            <tr>
                <td class="l" style="height: 20px;">{{$row->Item}}</td>
                <td class="r" align="right" colspan="3">{{number_format($total,2)}}</td>
            </tr>
            @endforeach
        </tbody>
        <tbody>
            @for($i = 0; $i < (10 - $count); $i++)
            <tr>
                <td colspan="4" class="l r" style="height: 50px;">&nbsp;</td>
            </tr>
          @endfor
            <tr>
                <td class="b t l r" colspan="2">Sub Total </td>
                <td class="b t l" style="font-weight: bold; border-right:none;">{{$data[0]->CurrencyCode}}</td>
                <td class="b t r" style="border-left: none; text-align:right;">{{number_format($data[0]->TotalAmount,2)}}</td>
            </tr>
            <tr>
                <td class="b t l r" colspan="2">Rate </td>
                <td class="b t l" style="font-weight: bold; border-right:none;">{{$data[0]->CurrencyCode}}</td>
                <td class="b t r" style="border-left: none; text-align:right;">{{number_format($data[0]->Rate,2)}}</td>
            </tr>
            @php $sumTotal = $subTotal * $data[0]->Rate; @endphp
            <tr>
                <td class="b t l r" colspan="2">TOTAL </td>
                <td class="b t l" style="font-weight: bold; border-right:none;">IDR</td>
                <td class="b t r" style="border-left: none; text-align:right;">{{number_format($sumTotal,2)}}</td>
            </tr>
        </tbody>
    </table>
    <strong>Remarks :</strong>
    <p>{{$data[0]->Remark}}<p>
        <p><span class="note">Note :</span>
        </p>
    <p>Says: {{$terbilang}}</p>
    <table>
        <tr>
            <td class="z" align="center" width="20%">
                Received By.</br></br></br></br>
                _________________________________</br>
                Sign</br>
                Name And Stamp
            </td>
            <td width="60%"></td>
            <td class="z" align="center" width="20%">
                Prepared By.</br></br></br></br>
                ________________________________</br>
                {{$company->Name}}
            </td>
        </tr>
    </table>
    <p style="text-align: justify; font-size: 10pt;">
        * Please arrange the remittance by the following Bank Accounts:<br>
        {{strtoupper($company->Name)}}<br>
        * BANK MANDIRI<br>
         A/C IDR No. 1090080801616
    </p>
    <p style="text-align: justify; font-size: 10pt;"><u>REMARKS :</u> </br>
        *WE WILL BACK IF ANY CHARGES OCCURED SUCH AS DETENTION, DEMMURAGE, RENOMINATION FEE, SECURITY CHARGES, AGENCY FEE, WASHING, REPAIR, ETC.
    </p>
    
</body>
</html>