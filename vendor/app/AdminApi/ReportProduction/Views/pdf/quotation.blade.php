<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script type="text/php"></script>
    <style>
        @page { margin: 110px 25px; }
        p { page-break-after: always; }
        p:last-child { page-break-after: never; }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }    
        table th {
            padding: 15px 10px;
            color: #5D6975;
            border-bottom: 1px solid #C1CED9;
            white-space: nowrap;
            font-weight: bold; 
            color: #ffffff;
            border-top: 1px solid  #5D6975;
            border-bottom: 1px solid  #5D6975;
            background: #888888;
            font-size: 14px;
            padding-top:15px;
            padding-bottom:15px;
            padding-left:10px;
            padding-right:10px;
        }
        table td {
            border: 1px solid #dddddd;
            vertical-align: top;
            font-size: 13px;
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
            font-size: 13px;
            padding-bottom:8px;
            background: #F5F5F1; 
            font-weight: bold; }
    </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    <main>
            <small>Kode: {{$data[0]->Code}}</small> </br>
            <small>Tanggal: {{date('Y-m-d', strtotime($data[0]->Date))}}</small> </br>
            <small>Kepada: {{$data[0]->BusinessPartner}}</small> </br>
            <small>Attn: {{$data[0]->ContactPerson}}</small> </br>
            </br>
            <strong style="margin-left: 45%; margin-bottom: px;">QUOTATION</strong> </br>
            <small>.</small>
        <table style="padding-top: 30px;">
            <thead>
                <tr> {{--width:675px--}}
                    <th rowspan="2" class="firstcol" style="width:25px">WIDTH</th>
                    <th rowspan="2" style="width:10px">x</th>
                    <th rowspan="2" style="width:30px">HEIGHT</th>
                    <th rowspan="2" style="width:230px">DESCRIPTION</th>
                    <th rowspan="2" style="width:20px">QTY</th>
                    <th colspan="2" style="width:60px">HARGA KACA</th>
                    <th colspan="2" class="lastcol" style="width:60px">HARGA PLAT</th>
                </tr>
                <tr> {{--width:675px--}}
                    <th style="width:30px">HRG SATUAN</th>
                    <th style="width:30px">TOTAL HRG</th>
                    <th style="width:30px">HRG SATUAN</th>
                    <th class="lastcol" style="width:30px">TOTAL HRG</th>
                </tr>
            </thead>
            <tbody>
                @php $group = ''; $totalSalesAmount = 0; $sumTotalSalesAmount = 0; $sumQuantity=0; @endphp
                @foreach($data as $row)
                    @if ($group != $row->ProductionOrderItem)
                        <tr><td colspan="9" class="group"><strong>{{$row->Description}}</strong></td></tr>
                        @php $group = $row->ProductionOrderItem; $totalSalesAmount = 0;  @endphp
                    @endif
                    <tr>
                        <td class="firstcol" align="left">{{$row->Width}}</td>
                        <td align="left">x</td>
                        <td align="left">{{$row->Height}}</td>
                        <td align="left">{{$row->DetailDescription}}</td>
                        <td align="right">{{$row->Quantity}}</td>
                        <td align="right">{{number_format($row->SalesAmount,2,',','.')}}</td>
                        <td align="right">{{number_format($row->Quantity * $row->SalesAmount,2,',','.')}}</td>
                        <td align="right">{{($row->IsFreeForZeroPrice && $row->SalesAmountGlass == 0) ? 'FREE' : number_format($row->SalesAmountGlass,2,',','.')}}</td>
                    @php 
                        $totalSalesAmount = $totalSalesAmount + $row->Quantity * $row->SalesAmount;
                        $sumTotalSalesAmount = $sumTotalSalesAmount + $totalSalesAmount;
                        $sumQuantity = $sumQuantity + $row->Quantity;
                    @endphp
                        <td class="lastcol" align="right">{{($row->IsFreeForZeroPrice && $row->SalesAmountGlass == 0) ? 'FREE' : number_format($row->Quantity * $row->SalesAmountGlass ,2,',','.')}}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="total" colspan="4" align="right"><strong>Subtotal Kaca & Plat</strong></td>
                    <td class="total" align="right"><strong>{{$sumQuantity}}</strong></td>
                    <td class="total" align="right"><strong>-</strong></td>
                    <td class="total" align="right"><strong>{{number_format($data[0]->SubtotalAmount ,2,',','.')}}</strong></td>
                    <td class="total" align="right"><strong>-</strong></td>
                    <td class="total" align="right"><strong>{{number_format($data[0]->SubtotalAmountGlass ,2,',','.')}}</strong></td>
                </tr>
            </tbody>
        </table>
        <table style="padding-top: 30px;">
            @if ($detail) 
            <thead>
                <tr> {{--width:675px--}}
                    <th class="firstcol" style="width:200px">DESCRIPTION</th>
                    <th style="width:20px">QTY</th>
                    <th style="width:30px">HARGA UNIT</th>
                    <th class="lastcol" style="width:50px">TOTAL</th>
                </tr>
            </thead>      
            @endif
            <tbody>
                @php $group = ''; $totalSalesAmount = 0; $sumTotalSalesAmount = 0; @endphp
                @foreach($detail as $item)
                    <tr>
                        <td class="firstcol" align="left">{{$item->Item}}</td>
                        <td align="right">{{$item->Quantity}}</td>
                        <td align="right">{{number_format($item->Amount ,2,',','.')}}</td>
                        <td class="lastcol" align="right">{{number_format($item->Quantity * $item->Amount,2,',','.')}}</td>
                    </tr>
                @endforeach
                @if ($detail) 
                <tr>
                    <td class="total" colspan="3" align="right"><strong>Total Item</strong></td>
                    <td class="total" align="right"><strong>{{number_format($data[0]->SubtotalAmountItem ,2,',','.')}}</strong></td>
                </tr>  
                @endif
                @if ($data[0]->Discount1 != 0)
                    <tr>
                    <td class="total" colspan="3" align="right">Diskon {{$data[0]->Discount1}}%</td>
                        <td class="total" align="right">{{number_format($data[0]->DiscountAmount1,2,',','.')}}</td>
                    </tr>
                @endif
                @if ($data[0]->Discount2 != 0)
                    <tr>
                        <td class="total" colspan="3" align="right">Diskon II {{$data[0]->Discount2}}%</td>
                        <td class="total" align="right">{{number_format($data[0]->DiscountAmount2,2,',','.')}}</td>
                    </tr>
                @endif
                <tr>
                    <td class="total" colspan="3" align="right"><strong>Final Grand Total</strong></td>
                    <td class="total" align="right"><strong>{{number_format($data[0]->TotalAmount,2,',','.')}}</strong></td>
                </tr>
            </tbody>
        </table>  
        <small>* Janji Pengerjaan: 4 Hari kerja (tidak termasuk hari libur, Sabtu & Minggu)</small> </br>
        <small>* Payment Terms:</small> </br>
        <small>* Bank details:</small> </br>
        <small>Beneficiary name: PT. HOKINDO EKATEHNIK SURYA</small> </br>
        <small>Beneficiary Account: 0613736023</small> </br>
        <small>Beneficiary name: Bank BCA</small> </br>
        <small>{{$data[0]->Note}}</small> </br>
        <td></td> </br>
        <small>Accepted & Confirm</small> <small style="margin-left: 60%;"> Hormat Kami</small> </br>
        <td></td> </br>
        <td></td> </br>
        <td></td> </br>
        <td>--------------------------</td> <small style="margin-left: 50%;">------------------------------------</small></br>
        <small>Tanda tangan & Nama jelas</small> <small style=" margin-left: 50%;"> PT. Hokindo Ekhatehnik Surya</small>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>
    </main>
</body>
</html>