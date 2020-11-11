<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>{{$reporttitle}}</title>
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
            font-size: 11px;
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
            <table>
                <thead>
                    <tr> {{--width:675px--}}
                    <th class="firstcol" style="width:40px">COMP</th>
                    <th class="firstcol" style="width:50px">CODE</th>
                    <th style="width:250px">PAYMENT METHODd</th>
                    <th style="width:30px">CURRENCY</th>
                    <th style="width:80px">AMOUNT</th>
                    <th class="lastcol" style="width:80px">PAYMENT BASE</th>
                    </tr>
                </thead>
                <tbody>
                    @php $group1 = ""; $group2 = ""; $total_paymentbase=0; $group1_paymentbase=0; $group2_paymentbase=0; @endphp
                    @foreach($data as $row)

                        @if ($group1 != $row->Type) <!-- HEADER OF GROUP 1 -->

                            @if ($group2 && $group2_paymentbase !== 0) <!-- TOTAL FOR GROUP 2 - muncul sewaktu reset ke grup 1 -->
                            <tr>
                                <td colspan="5" align="centre"><strong>TOTAL FOR {{$group2}}</strong></td>
                                <td class="total" align="right"><strong>{{ number_format($group2_paymentbase ,2,',','.')}}</strong></td>
                                @php $group2_paymentbase = 0; @endphp
                            </tr>
                            @endif <!-- TOTAL FOR GROUP 2 -->                            
                        
                            @if ($group1 && $group1_paymentbase !== 0) <!-- TOTAL FOR GROUP 1 - muncul sewaktu reset ke grup 1 -->
                            <tr>
                                <td colspan="5" align="centre"><strong>TOTAL FOR {{$group1}}</strong></td>
                                <td class="total" align="right"><strong>{{ number_format($group1_paymentbase ,2,',','.')}}</strong></td>
                                @php $group1_paymentbase = 0; @endphp
                            </tr>
                            @endif <!-- TOTAL FOR GROUP 1 -->

                            <tr><td colspan="6" class="group"><strong>{{$row->Type}}</strong></td></tr>
                            @php $group1 = $row->Type; @endphp
                        @endif <!-- HEADER OF GROUP 1 -->

                        @if ($group2 != $row->Code) <!-- HEADER OF GROUP 2 -->
                            @if ($group2 && $group2_paymentbase !== 0) <!-- TOTAL FOR GROUP 2 - muncul sewaktu reset grup 2 -->
                            <tr>
                                <td colspan="5" align="centre"><strong>TOTAL FOR {{$group2}}</strong></td>
                                <td class="total" align="right"><strong>{{ number_format($group2_paymentbase ,2,',','.')}}</strong></td>
                                @php $group2_paymentbase = 0; @endphp
                            </tr>
                            @endif <!-- TOTAL FOR GROUP 2 -->

                            <tr><td colspan="6" class="group"><strong>{{$row->Code}}</strong></td></tr>
                            @php $group2 = $row->Code; @endphp
                        @endif  <!-- HEADER OF GROUP 2 -->


                        <tr> <!-- STARTING OF HEADER OF DETAILS -->
                            <td class="firstcol">{{$row->Comp}}</td>
                            <td class="firstcol">{{$row->Code}}</td>
                            <td>{{$row->PaymentMethod}}</td>
                            <td align="right">{{$row->Currency}}</td>
                            <td align="right">{{number_format($row->PaymentAmount ,2,',','.')}}</td>
                            <td class="lastcol" align="right">{{number_format($row->PaymentBase ,2,',','.')}}</td>
                            @php
                                $total_paymentbase = $total_paymentbase + $row->PaymentBase;
                                $group1_paymentbase = $group1_paymentbase + $row->PaymentBase;
                                $group2_paymentbase = $group2_paymentbase + $row->PaymentBase;
                            @endphp
                        </tr> <!-- ENDING OF HEADER OF DETAILS -->

                    @endforeach

                    @if ($group2 && $group2_paymentbase !== 0) <!-- TOTAL FOR GROUP 2 - muncul pd akhir baris (looping selesai) -->
                        <tr>
                            <td colspan="5" align="centre"><strong>TOTAL FOR {{$group2}}</strong></td>
                            <td class="total" align="right"><strong>{{ number_format($group2_paymentbase ,2,',','.')}}</strong></td>
                            @php $group2_paymentbase = 0; @endphp
                        </tr>
                    @endif <!-- TOTAL FOR GROUP 2 -->                    
                        
                    @if ($group1 && $group1_paymentbase !== 0) <!-- TOTAL FOR GROUP 1 - muncul sewaktu reset ke grup 1 -->
                    <tr>
                        <td colspan="5" align="centre"><strong>TOTAL FOR {{$group1}}</strong></td>
                        <td class="total" align="right"><strong>{{ number_format($group1_paymentbase ,2,',','.')}}</strong></td>
                        @php $group1_paymentbase = 0; @endphp
                    </tr>
                    @endif <!-- TOTAL FOR GROUP 1 -->

                    <tr> <!-- TOTAL FOR ALL -->
                        <td colspan="5" align="centre"><strong>TOTAL ALL</strong></td>
                        <td class="total" align="right"><strong>{{ number_format($total_paymentbase ,2,',','.')}}</strong></td>
                    </tr> <!-- TOTAL FOR ALL -->

                </tbody>
            </table>
            <div style="padding: 13px 20px 13px 20px;">
                <div style="font-size: 14px; color: #858585;"></div>
            </div>
        </main>
    </body>
</html>