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
        font-size: 12px;
        padding-top:10px;
        padding-bottom:2px;
        padding-left:2px;
        padding-right:5px;
        }
        table td.firstcol { padding-left: 5px; }
        table td.lascol { padding-right: 5px; }
        table th.firstcol { padding-left: 5px; }
        table td.lascol { padding-right: 5px; }
        table td.group {
        padding-left: 10px;
        padding-top:10px;
        font-size: 14px;
        padding-bottom:10px;
        background: #F5F5F1; 
        font-weight: bold; }     
    </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    <main>
        <table>
            <thead>
                <tr> {{--width:675px / 525px--}}
                    <th class="firstcol" style="width:20px">COMP</th>
                    <th style="width:70px">DATE</th>
                    <th style="width:20px">{{$warehouse[0] ? $warehouse[0]->Code : ''}}</th>
                    <th style="width:20px">{{$warehouse[0] ? $warehouse[0]->Name.' Amt' : ''}}</th>
                    <th style="width:20px">{{$warehouse[1] ? $warehouse[1]->Code : ''}}</th>
                    <th style="width:20px">{{$warehouse[1] ? $warehouse[1]->Name.' Amt' : ''}}</th>
                    <th style="width:20px">{{$warehouse[2] ? $warehouse[2]->Code : ''}}</th>
                    <th class="lastcol" style="width:20px">{{$warehouse[2] ? $warehouse[2]->Name.' Amt' : ''}}</th>
                </tr>
            </thead>
            <tbody> 
                
                {{-- DECLARATION --}}
                @php 
                    $group=""; 
                    $totalgroup = reportVarCreate(['w1qty', 'w1amt', 'w2qty', 'w2amt', 'w3qty','w3amt']);
                    $totalall = reportVarCreate(['w1qty', 'w1amt', 'w2qty', 'w2amt', 'w3qty','w3amt']);
                @endphp {{-- DECLARATION --}}

                @foreach($data as $row)

                    {{-- GROUP 1 --}}
                    @if ($group != $row->GroupName)
                        @if ($group !="")
                            <tr>
                                <td align="right" colspan="2"><strong>Total: </strong></td>
                                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w1qty'])}}</strong></td>
                                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w1amt'] ,2,',','.')}}</strong></td>
                                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w2qty'])}}</strong></td>
                                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w2amt'] ,2,',','.')}}</strong></td>
                                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w3qty'])}}</strong></td>
                                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w3amt'] ,2,',','.')}}</strong></td>
                            </tr>
                        @endif

                        <tr><td colspan="8" class="group" >{{$row->GroupName}}</td></tr>

                        @php 
                            $group = $row->GroupName; 
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
                        <td>{{$row->Date}}</td>
                        <td align="right">{{number_format($row->w1qty)}}</td>
                        <td align="right">{{number_format($row->w1amt ,2,',','.')}}</td>
                        <td align="right">{{number_format($row->w2qty)}}</td>
                        <td align="right">{{number_format($row->w2amt ,2,',','.')}}</td>
                        <td align="right">{{number_format($row->w3qty)}}</td>
                        <td class="lastcol" align="right">{{number_format($row->w3amt ,2,',','.')}}</td>
                    </tr> {{-- DETAIL --}}
                    
                @endforeach
            </tbody>

            <tr> {{-- TOTAL FOR GROUP 1 --}}
                <td align="right" colspan="2"><strong>Total: </strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w1qty'])}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w1amt'] ,2,',','.')}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w2qty'])}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w2amt'] ,2,',','.')}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w3qty'])}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalgroup['w3amt'] ,2,',','.')}}</strong></td>
            </tr> {{-- TOTAL FOR GROUP 1 --}}

            <tr> {{-- TOTAL FOR GROUP 1 --}}
                <td align="right" colspan="2"><strong>GRAND TOTAL: </strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalall['w1qty'])}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalall['w1amt'] ,2,',','.')}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalall['w2qty'])}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalall['w2amt'] ,2,',','.')}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalall['w3qty'])}}</strong></td>
                <td class="total" style="font-size: 12px;" align="right"><strong>{{number_format($totalall['w3amt'] ,2,',','.')}}</strong></td>                
            </tr> {{-- TOTAL FOR GROUP 1 --}}

        </table>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>
    </main>
</body>
</html>