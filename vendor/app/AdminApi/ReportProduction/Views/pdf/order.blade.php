<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script type="text/php"></script>
    <style>
        @page { margin: 110px 25px; }
        /* p { page-break-after: always; } */
        /* p:last-child { page-break-after: never; } */
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;            
        }    
        table th {  
            padding: 5px 5px;
            border-bottom: 0px solid #ffffff;
            white-space: nowrap;
            font-weight: bold;
            border-top: 0px solid  #ffffff;
            border-bottom: 0px solid  #ffffff;
            font-size: 26px;
            padding-top:5px;
            padding-bottom:5px;
            padding-left:5px;
            padding-right:5px;
        }
        table td {
            border: 0px solid #ffffff;
            vertical-align: top;
            font-size: 36px;
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
            font-size: 18px;
            padding-bottom:8px;
            background: #F5F5F1; 
            font-weight: bold; 
        }
        img {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    @php $group = ''; @endphp
    @foreach($parent as $row)
        <main>
                <div style="page-break-after: always;"></div>
                <div style="height:50px"></div>
                <table style="padding-top: 30px">
                    <tr> {{--width:675px--}}
                        <td align="left" style="width:350px">{{date('d-m-Y', strtotime($row->Date))}}</th>
                        <td align="left">{{$row->BusinessPartner}}/
                            @if($row->Department){{$row->Department}}/@endif
                            {{$row->Code}}</th>
                    </tr>
                </table>
                <h1 style="padding-top: 30px">{{$row->Description}}</h1>
                <p style="font-size:24px">Catatan : {{$row->NoteParent}}</p>
                <div style="padding-top: 40px"></div>
                <img src="{{$row->Image}}" alt="No Image" style="object-fit:contain;max-width:500px;max-height:320px" class="center"><br>
                <div style="padding-top: 2%"></div>
                <table>
                    <thead>
                        <tr> {{--width:675px--}}
                            <th align="left" class="firstcol" style="width:120px;vertical-align: top;">KODE</th>
                            <th align="right" style="width:20px;vertical-align: top;"></th>
                            <th align="left" style="width:30px;vertical-align: top;">L</th>
                            <th align="center" style="width:60px;vertical-align: top;">x</br></br></th>
                            <th align="right" style="width:20px;vertical-align: top;"></th>
                            <th align="left" style="width:30px;vertical-align: top;">T</th>
                            <th align="right" style="width:80px;vertical-align: top;"></th>
                            <th align="left" style="width:80px;vertical-align: top;">QTY</th>
                            <th align="right" style="width:20px;vertical-align: top;"></th>
                            <th align="right" class="lastcol" style="width:100px;vertical-align: top;">CATATAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $rowdetail)
                        @if ($rowdetail->ProductionOrderItem == $row->ProductionOrderItem)
                            <tr>
                                <td align="left" class="firstcol" style="word-wrap: break-word;max-width:120px;vertical-align: top;">{{$rowdetail->Code}}</td>
                                <td align="center" style="font-size:30px;width:30px;vertical-align: top;">@if($rowdetail->Apprx==true)±@endif</td>
                                <td align="right" style="width:26px;vertical-align: top;
                                    @if(strip_tags($rowdetail->Line1)=='1')border-bottom:4px solid;@endif
                                    @if(strip_tags($rowdetail->Line1)=='2')border-bottom:10px double;@endif
                                    ">{{$rowdetail->Width}}</td>
                                <td align="center" style="width:60px;vertical-align: top;">x</br></td>
                                <td align="center" style="font-size:30px;width:30px;vertical-align: top;">@if($rowdetail->Apprx==true)±@endif</td>
                                <td align="right" style="width:26px;vertical-align: top;
                                    @if(strip_tags($rowdetail->Line2)=='1')border-bottom:4px solid;@endif
                                    @if(strip_tags($rowdetail->Line2)=='2')border-bottom:10px double;@endif
                                    ">{{$rowdetail->Height}}</td>
                                <td align="right" style="width:80px;vertical-align: top;">{{$rowdetail->Quantity}}</td>
                                <td align="right" style="width:80px;vertical-align: top;">{{$rowdetail->ItemGroup}}</td>
                                <td align="center" style="width:30px"></td>
                                <td align="left" class="lastcol" style="font-size:18px;width:100px;vertical-align: top;">{{$rowdetail->Note}}</td>
                            </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
        </main>
    @endforeach
    {{-- <style>
        table tr td, table tr th{
            border: 1px solid black;
        }
    </style> --}}
</body>
</html>