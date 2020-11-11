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
                    <th class="firstcol" style="width:80px">CODE</th>
                    <th style="width:50px">DATE</th>
                    <th style="width:50px">ITEM GLASS</th>
                    <th style="width:50px">CUSTOMER</th>
                    <th style="width:80px">ITEM PRD.</th>
                    <th style="width:20px">WIDTH</th>
                    <th style="width:20px">HEIGHT</th>
                    <th class="lastcol" style="width:70px">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @php $group1 = ''; $sumtotal = 0; @endphp
                    @foreach($data as $row)
                        @if ($group1 != $row->Code)
                            <tr>
                            <td colspan="6" class="group"><strong>{{$row->Code}}</strong></td>             
                            </tr>
                            @php $group1 = $row->Code; @endphp
                        @endif
                        <tr>
                            <td class="firstcol" align="left">{{$row->Code}}</td>
                            <td align="left">{{date('Y-m-d', strtotime($row->Date))}}</td>
                            <td align="right">{{$row->ItemGlass}}</td>
                            <td align="right">{{$row->CustomerName}}</td>
                            <td align="right">{{$row->ItemProduct}}</td>
                            <td align="right">{{$row->Width}}</td>
                            <td align="right">{{$row->Height}}</td>
                            <td class="lastcol" align="right">{{number_format($row->SalesAmount ,2,',','.')}}</td>
                        </tr>
                        @php 
                            $sumtotal = $sumtotal + $row->SalesAmount;
                        @endphp
                    @endforeach
                    <tr>
                        <td class="total" colspan="7" align="right"><strong>Total</strong></td>
                        <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
                    </tr>
                </tbody>
            </table>
            <div style="padding: 13px 20px 13px 20px;">
                <div style="font-size: 14px; color: #858585;"></div>
            </div>
        </main>
    </body>
</html>