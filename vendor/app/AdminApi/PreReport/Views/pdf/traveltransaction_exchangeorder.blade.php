<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange Outbound</title>
</head>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
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
            border-bottom: 1px solid  #5D6975;
        background: #888888; */
        font-size: 14px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        padding-right: 10px;
      }
      table td {
        border: 1.5px solid #5d6975;
        vertical-align: top;
        font-size: 12px;
        padding-top: 10px;
        padding-bottom: 2px;
        padding-left: 2px;
        padding-right: 1px;
      }
      b{font-size: 14pt;}
      .floatL{float: left;}
      .floatR{float: right; }
      .b{border-bottom: none;}
      .t{border-top: none;}
      .l{border-left: none;}
      .r{border-right: none;}

</style>
<body>
    @php $customers = ''; @endphp
    @foreach ($data as $row) 
        @if ($customers != $row->Customer) 
            @if ($customers != '')  
            <p><span style="font-size:10pt"> IN EXCHANGE FOR THIS VOUCHER, PLEASE PROVIDE THE FOLLOWING :</span></p>
            <table>
                <thead>
                    <tr>
                        <th colspan="4">NAMES</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    
                    $form = "";
                    $form .= "<tr>";
                        $count = 0;
                        $c = 1;
                        @endphp
                        @foreach ($dataPax as $rowpax) 
                            @php
                                if($count % 2 == 0) $form .='<tr>';
                                $form .= '<td width="3%">'.$c.'.<td width="45%">';
                                $form .= $rowpax->Name;
                                $form .= '</td>';
                                if($count % 2 != 0) $form .= '</td></tr>';
                                $count++;
                                $c++;
                            @endphp
                        @endforeach
                        @php
                        if ($count % 2 != 0) {
                        $form .= '</tr></tbody>';
                        }
                        
                        
                        $form .= '</table>';
                        echo $form;
                    @endphp
                </tbody>
            </table>
            <table border="none">
                <thead>
                    <tr>
                        <td width="5%">SNo.</td>
                        <td width="60%" align="left" colspan="3">Description</td>
                        <td width="5%">Qty</td>
                        <td width="5%">Price</td>
                        <td width="5%">Amount</td>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 1; $total = 0; $subTotal = 0; $sub=0; @endphp
                    @foreach($data as $item)
                        @if ($item->Customer == $customers)
                            <tr>
                                <td class="b t r">{{$count}}.</td>
                                <td class="b t l r">{{$item->Item}}<br>{{$item->DescEn}}<br>{{$item->Note}}</td>
                                <td class="b t l r"></td>
                                <td class="b t l r"></td>
                                <td class="b t l r" align="center">{{$item->Qty}}</td>
                                <td class="b t l r" align="right">{{number_format($item->Amount ,2)}}</td>
                                <td class="b t l" align="right">{{number_format($item->Total ,2)}}</td>
                            </tr>
                            @php $count = $count + 1;  $sub = $sub + $item->Total; @endphp
                        @endif
                    @endforeach
                </tbody>
                <tbody>
                    @php $subTotal = 0; @endphp
                    <tr>
                        <td rowspan="4" colspan="4">REMARKS</td>
                        <td class="r">GROSS </td>
                        <td class="l r" align="right">SGD</td>
                        <td class="l" align="right"> <span style="text-align:right"> {{number_format($sub ,2)}}</span></td>
                    </tr>
                    <tr>
                        <td class="r">DISCOUNT</td>
                        <td class="l r" align="right">SGD</td>
                        <td class="l" align="right"><span style="text-align:right"> {{number_format($row->Discount ,2)}}</span></td>
                    </tr>
                    @php
                        $subTotal = $sub  - $row->Discount;
                    @endphp
                    <tr>
                        <td class="r">SUB-TOTAL</td>
                        <td class="l r" align="right">SGD</td>
                        <td class="l" align="right"><span style="text-align:right"> {{number_format($subTotal ,2)}}</span></td>
                    </tr>
                    <tr>
                        <td class="r">TAX</td>
                        <td class="l r" align="right">SGD</td>
                        <td class="l" align="right"><span style="text-align:right"> {{number_format($row->Tax ,2)}}</span></td>
                    </tr>
                    @php
                        $nettTotal = $subTotal - $row->Tax;
                    @endphp
                    <tr>
                        <td colspan="2" class="r">INVOICE NO. {{$row->InvoiceCode}}</td>
                        <td colspan="2" class="l">CASH/CHEQUE</td>
                        <td class="r">NETT</td>
                        <td class="l r" align="right">SGD</td>
                        <td class="l" align="right"><span style="text-align:right"> {{number_format($nettTotal ,2)}}</span></td>
                    </tr>
                </tbody>
            </table>

            <div style="page-break-after:always;">&nbsp;</div>
            @endif

        <table  style="line-height: normal;">
            <tbody>

                <td class="b t l r" width="75%"><span><img src="{{$row->CompanyLogo}}" width="100%" height="auto"></span></td>
                <td class="b t l r" width="25%"><br><br><br><br>
                    <b>EXCHANGE ORDER</b><br>
                    No. {{$row->ttdCode}}<br>
                </td>
            </tbody>
        </table>
        <table style="line-height: normal;" width="100%">
            <tbody>
                <tr>       
                    <td class="b t l r" width="75%">
                            TO :<span style="padding-left: 20px;">{{$row->Customer}}</span><br>
                    </td>
                    <td class="b t l r"  width="5%">Date <br>
                            Page <br>
                            Invoice <br>
                            Staff <br>
                    </td>
                    <td class="b t l r"  width="20%">{{$row->Date}}<br>
                        : 1<br>
                        : {{$row->InvoiceCode}}<br>
                        : {{$row->StaffName}}<br>
                    </td>
                </tr>
                <tr>
                    <td class="b t l r">TEL-OFF/RES/HP<span style="padding-left: 5px;">: {{$row->PhoneNo}}</span><br>
                        E-MAIL<span style="padding-left: 52px;">: {{$row->Email}}</span>
                    </td>
                    <td class="b t l r">
                        FAX <span style="padding-left: 55px;"> {{$row->Fax}}</span>
                    </td>
                </tr>
            </tbody>
        </table>
        @endif


    @php $customers = $row->Customer; @endphp
@endforeach

<p><span style="font-size:10pt"> IN EXCHANGE FOR THIS VOUCHER, PLEASE PROVIDE THE FOLLOWING :</span></p>
<table>
    <thead>
        <tr>
            <th colspan="4">NAMES</th>
        </tr>
    </thead>
    <tbody>
        @php
        
        $form = "";
        $form .= "<tr>";
            $count = 0;
            $c = 1;
            foreach ($dataPax as $rowPax) {

            if($count % 2 == 0) $form .='<tr>';
            $form .= '<td width="3%">'.$c.'.<td width="45%">';
            $form .= $rowPax->Name;
            $form .= '</td>'; 

            if($count % 2 != 0) $form .= '</td></tr>';

            $count++;
            $c++;
            }

            if ($count % 2 != 0) {
            $form .= '</tr></tbody>';
            }
            $form .= '</table>';
            echo $form;
        @endphp
    </tbody>
</table>
<table border="none">
    <thead>
        <tr>
            <td width="5%">No.</td>
            <td width="60%" align="left" colspan="3">Description</td>
            <td width="5%">Qty</td>
            <td width="5%">Price</td>
            <td width="5%">Amount</td>
        </tr>
    </thead>
    <tbody>
        @php $count = 1; $total = 0; $subTotal = 0; $sub=0; @endphp
            @foreach($data as $item)
                @if ($item->Customer == $customers)
                    <tr>
                        <td class="b t r">{{$count}}.</td>
                        <td class="b t l r">{{$item->Item}}<br>{{$item->DescEn}}<br>{{$item->Note}}</td>
                        <td class="b t l r"></td>
                        <td class="b t l r"></td>
                        <td class="b t l r" align="center">{{$item->Qty}}</td>
                        <td class="b t l r" align="right">{{number_format($item->Amount ,2)}}</td>
                        <td class="b t l" align="right">{{number_format($item->Total ,2)}}</td>
                    </tr>
                    @php $count = $count + 1;  $sub = $sub + $item->Total; @endphp
                @endif
            @endforeach
        </tbody>
        <tbody>
            @php $subTotal = 0; @endphp
            <tr>
                <td rowspan="4" colspan="4">REMARKS</td>
                <td class="r">GROSS </td>
                <td class="l r" align="right">SGD</td>
                <td class="l" align="right"> <span style="text-align:right"> {{number_format($sub ,2)}}</span></td>
            </tr>
            <tr>
                <td class="r">DISCOUNT</td>
                <td class="l r" align="right">SGD</td>
                <td class="l" align="right"><span style="text-align:right"> {{number_format($row->Discount ,2)}}</span></td>
            </tr>
            @php
                $subTotal = $sub  - $row->Discount;
            @endphp
            <tr>
                <td class="r">SUB-TOTAL</td>
                <td class="l r" align="right">SGD</td>
                <td class="l" align="right"><span style="text-align:right"> {{number_format($subTotal ,2)}}</span></td>
            </tr>
            <tr>
                <td class="r">TAX</td>
                <td class="l r" align="right">SGD</td>
                <td class="l" align="right"><span style="text-align:right"> {{number_format($row->Tax ,2)}}</span></td>
            </tr>
            @php
                $nettTotal = $subTotal - $row->Tax;
            @endphp
            <tr>
                <td colspan="2" class="r">INVOICE NO. {{$row->InvoiceCode}}</td>
                <td colspan="2" class="l">CASH/CHEQUE</td>
                <td class="r">NETT</td>
                <td class="l r" align="right">SGD</td>
                <td class="l" align="right"><span style="text-align:right"> {{number_format($nettTotal ,2)}}</span></td>
            </tr>
    </tbody>
</table>
<div style="padding-bottom:150px;">
    <p class="floatR bold f12">
        <span>ACE TOURS & TRAVEL PTE LTD</span>
        <br>
        <br>
        <br>
        <span style="padding-left:9px">___________________________</span>
        <br>
        <span style="font-size: 9pt; padding-left:37px">AUTHORISED SIGNATURE(S)</span>
        <br>
       <b style="padding-left:15px"> ATG TOURS PTE LTD </b>
    </p>
</div>


</body>
</html>