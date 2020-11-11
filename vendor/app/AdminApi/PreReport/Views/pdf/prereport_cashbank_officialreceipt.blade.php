<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$reporttitle}}</title>
    <style type="text/css">
    html,body{
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        border: solid black;
        border-width: thin;
        overflow:hidden;
        display:block;
        box-sizing: border-box;
    }
    .w100{
        width: 100%;
    }
    .Lh{
         line-height: 35px;
    }
    .b{
        font-weight: bold;
    }
    .st{
        text-decoration: line-through;
    }
    p{
      line-height: normal;
      padding: 0px;
      margin: 0px;
    }
    .bc{
        font-weight: bold;
        font-family: 'Courier New', Courier, monospace; !important
    }
    </style>
 

</head>
<body style="font-family:Tahoma, Geneva, sans-serif;">
    <table class="w100 Lh">
        <tbody>
            <tr>
                <td width="300px">
                    <span><img src="{{$data[0]->LogoPrint}}" width="700px" height="auto"></span>
                </td>
                <td width="200px">
                    <center><h3 class="bc">OFFICIAL RECEIPT</h3></center>
                    <p>
                        NO<span>: </span>{{$data[0]->CashBankCode}}</br>
                        DATE<span>: </span>{{$data[0]->Date}}</br>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
    <table class="w100 Lh" style="padding-left: 20px">
        <tbody>
            <tr>
                <td width=215px>Received From</td>
                <td colspan="3" style="line-height:5px" class="bc"> {{$data[0]->BusinessPartner}}
                    <br>______________________________________________________________________________________</td>
            </tr>
            <tr>
                <td>the Sum of dollars</td>
                <td colspan="3" style="line-height:5px" class="bc"> {{$data[0]->TotalAmountWording}}
                    <br>______________________________________________________________________________________</td>
            </tr>
            <tr>
                <td>being <b>Payment</b>/<strike>Refund of</strike></br></td>
                <td style="line-height:5px" class="bc">
                    @php 
                        $invoiceNumber = '';
                        foreach($data as $row) $invoiceNumber = $invoiceNumber.($invoiceNumber ? ", " : "").$row->InvoiceNumber
                    @endphp
                    {{ $invoiceNumber }}
                    <br>____________________________________</br>
                </td>
                <td>Received By:
                </td>
                <td style="line-height:5px">
                    <b class="bc"> {{$data[0]->Receivedby}}</b><br>
                    ____________________________________<br>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span style="padding-left:220px">(Deposit / Balance / <u class="b">Full Payment)</u></span>
                </td>
            </tr>
            <tr>
                <td colspan="3">Remarks : {!! $data[0]->Note !!}</td>
            </tr>
        </tbody>
    </table>
    <table class="w100" style="padding-top:125px">
        <tr>
            <td align="center">
            </br>
            </br>
            </br>
                <span class="b">$ {{$data[0]->TotalAmount}}</span></br>
                _______________________________</br>
                ( Cash / Nets / Visa / Cheque / <u class="b">Bank</u> )
            </td>
            <td class="b" align="center">
            </br>
            </br>
            </br>
            </br>
                Customer's Copy
            </td>
            <td align="center">
                <b>{{$data[0]->CompanyName}}</b></br>
            </br>
            </br>
            </br>
            _______________________________</br>
            AUTHORIZED SIGNATURE(s)
            </td>
        </tr>
    </table>
    
</body>
</html>