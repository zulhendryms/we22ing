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
                    <th class="firstcol" style="width:auto">COMP</th>
                    <th style="width:auto">{{$data[0]->Judul}}</th>
                    <th style="width:90px" align="right" >1</th>
                    <th style="width:80px" align="right" >2</th>
                    <th style="width:80px" align="right" >3</th>
                    <th style="width:80px" align="right" >4</th>
                    <th style="width:80px" align="right" >5</th>
                    <th style="width:80px" align="right" >6</th>
                    <th style="width:80px" align="right" >7</th>
                    <th style="width:80px" align="right" >8</th>
                    <th style="width:80px" align="right" >9</th>
                    <th style="width:80px" align="right" >10</th>
                    <th style="width:80px" align="right" >11</th>
                    <th style="width:80px" align="right" >12</th>
                    <th style="width:80px" align="right" >13</th>
                    <th style="width:80px" align="right" >14</th>
                    <th style="width:80px" align="right" >15</th>
                    <th style="width:80px" align="right" >16</th>
                    <th style="width:80px" align="right" >17</th>
                    <th style="width:80px" align="right" >18</th>
                    <th style="width:80px" align="right" >19</th>
                    <th style="width:80px" align="right" >20</th>
                    <th style="width:80px" align="right" >21</th>
                    <th style="width:80px" align="right" >22</th>
                    <th style="width:80px" align="right" >23</th>
                    <th style="width:80px" align="right" >24</th>
                    <th style="width:80px" align="right" >25</th>
                    <th style="width:80px" align="right" >26</th>
                    <th style="width:80px" align="right" >27</th>
                    <th style="width:80px" align="right" >28</th>
                    <th style="width:80px" align="right" >29</th>
                    <th style="width:80px" align="right" >30</th>
                    <th style="width:80px" align="right" >31</th>
                    <th class="lastcol" style="width:120px" align="right" >TTL</th>
                </tr>
            </thead>
            <tbody> 
                
                {{-- DECLARATION --}}
                @php 
                $group=""; 
                $Total = reportVarCreate(['Totalall']);
                $totalperday = reportVarCreate(['d1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14','d15','d16','d17','d18','d19','d20',
                                                'd21','d22','d23','d24','d25','d26','d27','d28','d29','d30','d31','Total']);
                $Totalall = reportVarCreate(['d1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14','d15','d16','d17','d18','d19','d20',
                                            'd21','d22','d23','d24','d25','d26','d27','d28','d29','d30','d31','Total']);         
                @endphp {{-- DECLARATION --}}

                @foreach($data as $row)

                    {{-- GROUP 1 --}}
                    @if ($group != $row->GroupName)
                        @if ($group !="")
                            <tr>
                                <td colspan="2" class="total" align="right"><strong>Total Per Day </strong></td>
                                <td class="total" align="right">{{$totalperday['d1']}}</td>
                                <td class="total" align="right">{{$totalperday['d2']}}</td>
                                <td class="total" align="right">{{$totalperday['d3']}}</td>
                                <td class="total" align="right">{{$totalperday['d4']}}</td>
                                <td class="total" align="right">{{$totalperday['d5']}}</td>
                                <td class="total" align="right">{{$totalperday['d6']}}</td>
                                <td class="total" align="right">{{$totalperday['d7']}}</td>
                                <td class="total" align="right">{{$totalperday['d8']}}</td>
                                <td class="total" align="right">{{$totalperday['d9']}}</td>
                                <td class="total" align="right">{{$totalperday['d10']}}</td>
                                <td class="total" align="right">{{$totalperday['d11']}}</td>
                                <td class="total" align="right">{{$totalperday['d12']}}</td>
                                <td class="total" align="right">{{$totalperday['d13']}}</td>
                                <td class="total" align="right">{{$totalperday['d14']}}</td>
                                <td class="total" align="right">{{$totalperday['d15']}}</td>
                                <td class="total" align="right">{{$totalperday['d16']}}</td>
                                <td class="total" align="right">{{$totalperday['d17']}}</td>
                                <td class="total" align="right">{{$totalperday['d18']}}</td>
                                <td class="total" align="right">{{$totalperday['d19']}}</td>
                                <td class="total" align="right">{{$totalperday['d20']}}</td>
                                <td class="total" align="right">{{$totalperday['d21']}}</td>
                                <td class="total" align="right">{{$totalperday['d22']}}</td>
                                <td class="total" align="right">{{$totalperday['d23']}}</td>
                                <td class="total" align="right">{{$totalperday['d24']}}</td>
                                <td class="total" align="right">{{$totalperday['d25']}}</td>
                                <td class="total" align="right">{{$totalperday['d26']}}</td>
                                <td class="total" align="right">{{$totalperday['d27']}}</td>
                                <td class="total" align="right">{{$totalperday['d28']}}</td>
                                <td class="total" align="right">{{$totalperday['d29']}}</td>
                                <td class="total" align="right">{{$totalperday['d30']}}</td>
                                <td class="total" align="right">{{$totalperday['d31']}}</td>
                                <td class="total" align="right">{{$totalperday['Total']}}</td>
                            </tr>
                        @endif

                        <tr><td colspan="34" class="group" >{{$row->GroupName}}</td></tr>

                        @php 
                            $group = $row->GroupName; 
                            $totalperday = reportVarReset($totalperday);  
                        @endphp
                    @endif 
                    {{-- GROUP 1 --}}
                    

                    {{-- DETAIL --}}
                    @php
                                           
                        $row->Total = $row->d1 + $row->d2+ $row->d3+ $row->d4+ $row->d5+ $row->d6+ $row->d7+ $row->d8+ $row->d9+ $row->d10+ 
                      $row->d11+ $row->d12+ $row->d13+ $row->d14+ $row->d15+ $row->d16+ $row->d17+ $row->d18+ $row->d19+ $row->d20+ 
                      $row->d21+ $row->d22+ $row->d23+ $row->d24+ $row->d25+ $row->d26+ $row->d27+ $row->d28+ $row->d29+ $row->d30+ 
                      $row->d31;
                      $totalperday = reportVarAddValue($totalperday, $row); 
                      $Totalall = reportVarAddValue($Totalall, $row);
                    @endphp
                    <tr>
                        <td class="firstcol" align="left">{{$row->Comp}}</td>
                        <td align="left">{{$row->Item}}</td>
                        <td align="right">{{$row->d1==0 ? '' : $row->d1}}</td>
                        <td align="right">{{$row->d2==0 ? '' : $row->d2}}</td>
                        <td align="right">{{$row->d3==0 ? '' : $row->d3}}</td>
                        <td align="right">{{$row->d4==0 ? '' : $row->d4}}</td>
                        <td align="right">{{$row->d5==0 ? '' : $row->d5}}</td>
                        <td align="right">{{$row->d6==0 ? '' : $row->d6}}</td>
                        <td align="right">{{$row->d7==0 ? '' : $row->d7}}</td>
                        <td align="right">{{$row->d8==0 ? '' : $row->d8}}</td>
                        <td align="right">{{$row->d9==0 ? '' : $row->d9}}</td>
                        <td align="right">{{$row->d10==0 ? '' : $row->d10}}</td>
                        <td align="right">{{$row->d11==0 ? '' : $row->d11}}</td>
                        <td align="right">{{$row->d12==0 ? '' : $row->d12}}</td>
                        <td align="right">{{$row->d13==0 ? '' : $row->d13}}</td>
                        <td align="right">{{$row->d14==0 ? '' : $row->d14}}</td>
                        <td align="right">{{$row->d15==0 ? '' : $row->d15}}</td>
                        <td align="right">{{$row->d16==0 ? '' : $row->d16}}</td>
                        <td align="right">{{$row->d17==0 ? '' : $row->d17}}</td>
                        <td align="right">{{$row->d18==0 ? '' : $row->d18}}</td>
                        <td align="right">{{$row->d19==0 ? '' : $row->d19}}</td>
                        <td align="right">{{$row->d20==0 ? '' : $row->d20}}</td>
                        <td align="right">{{$row->d21==0 ? '' : $row->d21}}</td>
                        <td align="right">{{$row->d22==0 ? '' : $row->d22}}</td>
                        <td align="right">{{$row->d23==0 ? '' : $row->d23}}</td>
                        <td align="right">{{$row->d24==0 ? '' : $row->d24}}</td>
                        <td align="right">{{$row->d25==0 ? '' : $row->d25}}</td>
                        <td align="right">{{$row->d26==0 ? '' : $row->d26}}</td>
                        <td align="right">{{$row->d27==0 ? '' : $row->d27}}</td>
                        <td align="right">{{$row->d28==0 ? '' : $row->d28}}</td>
                        <td align="right">{{$row->d29==0 ? '' : $row->d29}}</td>
                        <td align="right">{{$row->d30==0 ? '' : $row->d30}}</td>
                        <td align="right">{{$row->d31==0 ? '' : $row->d31}}</td>
                        <td class="lastcol" align="right">{{$row->Total}}</td>
                    </tr> {{-- DETAIL --}}
                    
                @endforeach
            </tbody>

            <tr> {{-- TOTAL FOR GROUP 1 --}}
                <td align="right" colspan="2"><strong>Total Per Day </strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d1']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d2']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d3']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d4']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d5']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d6']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d7']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d8']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d9']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d10']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d11']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d12']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d13']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d14']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d15']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d16']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d17']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d18']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d19']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d20']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d21']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d22']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d23']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d24']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d25']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d26']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d27']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d28']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d29']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d30']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['d31']}}</strong></td> 
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$totalperday['Total']}}</strong></td>
            </tr> {{-- TOTAL FOR GROUP 1 --}}

            <tr> {{-- TOTAL FOR GROUP 1 --}}
                <td align="right" colspan="2"><strong>GRAND TOTAL: </strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d1']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d2']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d3']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d4']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d5']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d6']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d7']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d8']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d9']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d10']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d11']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d12']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d13']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d14']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d15']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d16']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d17']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d18']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d19']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d20']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d21']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d22']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d23']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d24']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d25']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d26']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d27']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d28']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d29']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d30']}}</strong></td>
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['d31']}}</strong></td> 
                <td class="total" style="font-size: 10px;" align="right"><strong>{{$Totalall['Total']}}</strong></td>
            </tr> {{-- TOTAL FOR GROUP 1 --}}

        </table>
        <div style="padding: 13px 20px 13px 20px;">
            <div style="font-size: 14px; color: #858585;"></div>
        </div>
    </main>
</body>
</html>