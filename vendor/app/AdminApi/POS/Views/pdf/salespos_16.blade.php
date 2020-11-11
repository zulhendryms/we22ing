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
                    <th class="firstcol" style="width:30px">Comp</th>
                    <th style="width:40px">PAYMENT METHOD</th>
                    <th style="width:250px">PAYMENT METHOD</th>
                    <th style="width:30px">CURRENCY</th>
                    <th style="width:80px">AMOUNT</th>
                    <th class="lastcol" style="width:80px">PAYMENT BASE</th>
                    </tr>
                </thead>
                <tbody>

                    {{-- DECLARATION --}}
                    @php 
                        $group="";
                        $totalgroup = reportVarCreate(['PaymentAmount', 'PaymentBase']);
                        $totalall = reportVarCreate(['PaymentAmount', 'PaymentBase']);
                    @endphp
                    {{-- DECLARATION --}}

                    @foreach($data as $row)

                        {{-- GROUP 1 --}}
                        @if ($group != $row->Currency)
                            @if ($group !="")
                                <tr> {{-- TOTAL FOR GROUP 1 --}}
                                    <td colspan="4" class="total" align="right"><strong>Total For {{$group}}</strong></td>
                                    <td class="total" align="right"><strong>{{number_format($totalgroup['PaymentAmount'],2,',','.')}}</strong></td>
                                <td class="total" align="right"><strong>{{number_format($totalgroup['PaymentBase'],2,',','.')}}</strong></td>
                                </tr> {{-- TOTAL FOR GROUP 1 --}}
                            @endif

                            <tr><td colspan="6" class="group"><strong>{{$row->Currency}}</strong></td></tr>
                            @php 
                                $group1 = $row->Currency; 
                                $totalgroup = reportVarReset($totalgroup); 
                            @endphp
                        @endif 
                        {{-- GROUP 1 --}}
              
                        {{-- DETAIL --}}
                        @php
                            $totalgroup = reportVarAddValue($totalgroup, $row);
                            $totalall = reportVarAddValue($totalall, $row);
                        @endphp
                        <tr>
                            <td class="firstcol">{{$row->Comp}}</td>
                            <td>{{$row->Type}}</td>
                            <td>{{$row->PaymentMethod}}</td>
                            <td>{{$row->Currency}}</td>
                            <td align="right">{{number_format($row->PaymentAmount ,2,',','.')}}</td>
                            <td class="lastcol" align="right">{{number_format($row->PaymentBase ,2,',','.')}}</td>
                        </tr>
                        {{-- DETAIL --}}

                    @endforeach

                    <tr> {{-- TOTAL FOR GROUP 1 --}}
                      <td colspan="4" class="total" align="right"><strong>Total For {{$group}}</strong></td>
                      <td class="total" align="right"><strong>{{number_format($totalgroup['PaymentAmount'],2,',','.')}}</strong></td>
                      <td class="total" align="right"><strong>{{number_format($totalgroup['PaymentBase'],2,',','.')}}</strong></td>
                    </tr> {{-- TOTAL FOR GROUP 1 --}}
              
                    <tr> {{-- GRAND TOTAL FOR ALL --}}
                      <td colspan="4" class="total" align="right"><strong>GRAND TOTAL</strong></td>
                      <td class="total" align="right"><strong>{{number_format($totalall['PaymentAmount'],2,',','.')}}</strong></td>
                      <td class="total" align="right"><strong>{{number_format($totalall['PaymentBase'],2,',','.')}}</strong></td>
                    </tr> {{-- GRAND TOTAL FOR ALL --}}
                </tbody>
            </table>
            <div style="padding: 13px 20px 13px 20px;">
                <div style="font-size: 14px; color: #858585;"></div>
            </div>
        </main>
    </body>
</html>