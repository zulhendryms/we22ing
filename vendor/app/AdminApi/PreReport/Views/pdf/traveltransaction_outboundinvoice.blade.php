<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>SALES REPORT FOR OUTBOUND INVOICE</title>
<style type="text/css">
    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
        }
    .bt{
        border-top: none;
        border-bottom: none;
    }
    table th {
    padding: 15px 10px;
    color: #5d6975;
    white-space: nowrap;
    font-weight: bold;
    color: #000000;
    border-top: 1px solid black;
    border-bottom: 1px solid black;
    font-size: 14px;
    padding-top: 10px;
    padding-bottom: 10px;
    padding-left: 10px;
    padding-right: 10px;
    }
    table td {
    vertical-align: top;
    border-bottom: 1px solid black;
    font-size: 12px;
    padding-top: 10px;
    padding-bottom: 2px;
    padding-left: 2px;
    padding-right: 1px;
    }
    .total{
    content: '';
    margin-top: -1px;
    border-top: 1px solid red;
    }
    .dd{
        border-top: double black;
    }

</style>
</head>
<body>
{{-- <h4>ATG TOURS & TRAVEL</h4> --}}
<h4><center>SALES REPORT FOR OUTBOUND INVOICE</center></h4>
    <p><center style="font-size:9pt;">For the period {{$data[0]->DateFrom}} to {{$data[0]->DateUntil}}</center></p>
<main>
    <table>
        <thead>
            <tr>
                <th>Invoice Number</th>
                <th>Dept. Date<br>Sales Person</th>
                <th>Customer Name</th>
                <th>Invoice Amount</th>
                <th>EO Number</th>
                <th>EO Date</th>
                <th>Supplier Name</th>
                <th>Cost Amount</th>
                <th>Nett Profit</th>
                </tr>
        </thead>
        <tbody>
            @php $group=''; $total = ''; $totalForCost=0; $totalGrandForInvoice=0; $totalGrandForCost=0; @endphp
            @foreach($data as $row)
                @if ($total != $row->Code )
                @if ($total !="")
                <tr style="font-weight: bold;">
                    <td></td>
                    <td align="center">{{$previous->SalesPerson}}</td>
                    <td></td>
                    <td align="right">{{number_format($previous->InvoiceAmount ,2)}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td align="right">{{number_format($totalForCost ,2)}}</td>
                    <td align="right">{{number_format($previous->InvoiceAmount -  $totalForCost,2)}}</td>
                </tr>
                @endif
                    @php $total = $row->Code; $totalForCost=0; @endphp
                @endif

                <tr>
                    @if($group != $row->Code)
                        <td align="center" class="bt">{{$row->Code}}</td>
                        <td align="center" class="bt">{{$row->Date}}</td>
                        <td align="center" class="bt">{{$row->CustomerName}}</td>
                        <td align="right" class="bt">{{number_format($row->InvoiceAmount ,2)}}</td>
                        @php 
                            $previous = $row; 
                            $totalGrandForInvoice = $totalGrandForInvoice + $row->InvoiceAmount; 
                        @endphp
                    @else
                        <td class="bt"></td>
                        <td class="bt"></td>
                        <td class="bt"></td>
                        <td class="bt"></td>
                    @endif
                    <td align="center" class="bt">{{$row->DetailCode}}</td>
                    <td align="center" class="bt">{{$row->DetailDate}}</td>
                    <td align="center" class="bt">{{$row->DetailBusinessPartner}}</td>
                    <td align="right" class="bt">{{number_format($row->DetailAmount ,2)}}</td>
                    <td align="center" class="bt"></td>
                    @php
                        $group = $row->Code;
                        $totalForCost = $totalForCost + $row->DetailAmount;
                        $totalGrandForCost = $totalGrandForCost + $row->DetailAmount;
                    @endphp
                </tr>
            @endforeach
                <tr style="font-weight: bold;">
                    <td></td>
                    <td align="center">{{$row->SalesPerson}}</td>
                    <td></td>
                    <td align="right">{{number_format($row->InvoiceAmount ,2)}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td align="right">{{number_format($totalForCost ,2)}}</td>
                    <td align="right">{{number_format($row->InvoiceAmount -  $totalForCost,2)}}</td>
                </tr>
                <tr style="font-weight: bold;" class="dd">
                    <td>Total </td>
                    <td></td> 
                    <td></td>
                    <td align="right">{{number_format($totalGrandForInvoice ,2)}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td align="right">{{number_format($totalGrandForCost ,2)}}</td>
                    <td align="right">{{number_format($totalGrandForInvoice - $totalGrandForCost,2)}}</td>
                </tr>
        </tbody>
</table>
</main>

</body>
</html>