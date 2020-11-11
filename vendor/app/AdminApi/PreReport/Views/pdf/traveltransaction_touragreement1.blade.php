<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>tour-agreement</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
    p { margin-top: 0px; }
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
      color: #000000;
      border: 1px solid #5D6975;
      /* border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975; */
      /* background: #888888; */
      font-size: 10pt;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #5D6975;
      vertical-align: top;
      font-size: 9pt;
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
      font-size: 12px;
      padding-bottom:8px;
      background: #F5F5F1; 
      font-weight: bold; 
    }
    .title{
      text-align: center;
      background-color: #cbcbcb;
      font-weight: 600;
    }
    .ra{
      width: 3%;
      text-align: right;
    }
    .lr{
      border-left: none;
      border-right:none;
    }

  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <div class="container" style="padding-left: 8px; padding-right: 8px;">
      <table>
        <tbody>
          <tr>
            <th class="title" colspan="8">TOUR AGREEMENT</th>
          </tr>
          <tr>
            <td colspan="2">SIN-IN-CHRG/CONTACT</td>
            <td colspan="2">{{$data[0]->UserFullName}} / {{$data[0]->UserPhone}}</td>
            <td colspan="2">OVERSEA-IN-CHRG.CONTACT</td>
            <td colspan="2">{{$data[0]->ContactName}} / {{$data[0]->ContactNumber}}</td>
          </tr>
            <tr>
              <td width="8%">TOUR CODE</td>
              <td width="12%">{{$data[0]->TourCode}}</td>
              <td width="8%">COMPANY</td>
              <td width="12%">{{$data[0]->Customer}}</td>
              <td width="8%">TOUR LEADER</td>
              <td width="12%">{{$data[0]->TourLeader}}</td>
              <td width="9%">YOUR REF</td>
              <td width="12%">{{$data[0]->CodeReff}}</td>
            </tr>
            <tr>
              <td>ISSUED DATE</td>
              <td>{{$data[0]->Date}}</td>
              <td>INVOICE NO.</td>
              <td></td>
              <td>SIN TG</td>
              <td>{{$data[0]->TourGuide1}}</td>
              <td>OVERSEA TG</td>
              <td>{{$data[0]->TourGuide2}}</td>
            </tr>
            <tr>
              <td colspan="8"></td>
            </tr>
            <tr>
              <td class="title" colspan="8">FLIGHT DETAILS</td>
            </tr>
            <tr>
              <td>1ST ARR DATE</td>
              <td>{{count($dataFlight) > 0 ? $dataFlight[0]->FlightDate : null}}</td>
              <td>1ST ARR FLT</td>
              <td>{{count($dataFlight) > 0 ? $dataFlight[0]->FlightCode : null}}</td>
              <td>1ST DPT DATE</td>
              <td>{{count($dataFlight) > 1 ? $dataFlight[1]->FlightDate : null}}</td>
              <td>1ST DEPART FLT</td>
              <td>{{count($dataFlight) > 1 ? $dataFlight[1]->FlightCode : null}}</td>
            </tr>
            @if (count($dataFlight) > 2)
            <tr>
              <td>2ST ARR DATE</td>
              <td>{{count($dataFlight) > 2 ? $dataFlight[2]->FlightDate : null}}</td>
              <td>2ST ARR FLT</td>
              <td>{{count($dataFlight) > 2 ? $dataFlight[2]->FlightCode : null}}</td>
              <td>2ST DPT DATE</td>
              <td>{{count($dataFlight) > 3 ? $dataFlight[3]->FlightDate : null}}</td>
              <td>2ST DEPART FLT</td>
              <td>{{count($dataFlight) > 3 ? $dataFlight[3]->FlightCode : null}}</td>
            </tr>
            @endif
          </tbody>
      </table>
      <table>
          <tbody>
            <tr>
              <td class="title" colspan="12">ROOM ARRANGEMENT</td>
            </tr>
            <tr>
              <td width="8%">ADULT</td>
              <td class="ra">{{$data[0]->ADT}}</td>
              <td width="8%">CWB/CWEB</td>
              <td class="ra">{{$data[0]->CWB}}</td>
              <td width="8%">CNB</td>
              <td class="ra">{{$data[0]->CNB}}</td>
              <td width="8%">INF</td>
              <td class="ra">{{$data[0]->INF}}</td>
              <td width="8%">TL</td>
              <td class="ra">{{$data[0]->TL}}</td>
              <td width="8%">TOTAL</td>
              <td class="ra">{{$data[0]->Total1}}</td>
            </tr>
            <tr>
              <td>SINGLE</td>
              <td class="ra">{{$data[0]->SGL}}</td>
              <td>TWIN</td>
              <td class="ra">{{$data[0]->TWN}}</td>
              <td>DOUBLE</td>
              <td class="ra">{{$data[0]->DBL}}</td>
              <td>TRIPLE</td>
              <td class="ra">{{$data[0]->TRP}}</td>
              <td>FOC</td>
              <td class="ra">{{$data[0]->FOC}}</td>
              <td>TOTAL</td>
              <td class="ra">{{$data[0]->Total2}}</td>
            </tr>
        </tbody>
      </table>
      <table>
        <tbody>
          <tr class="title">
            <td>DATE</td>
            <td>ITINERARY</td>
            <td colspan="2">MEALS ARRANGEMENT</td>
            <td>HOTEL</td>
          </tr>
          @php $count = 0; @endphp
          @foreach($dataItinerary as $row)
          <tr>
            <td width="5%" rowspan="5">{{$row->Date}}</td>
            {{-- <td width="25%" rowspan="5">{{$row->DescEN}}</td> --}}
            <td width="25%" rowspan="5">{!! $row->DescEN !!}</td>
            <td width="3%">BFF</td>
            <td width="15%">{{$row->MB}}</td>
            <td width="10%" rowspan="5">{{$row->Hotel}}</td>
          </tr>
          <tr>
            <td>HI-TEA</td>
            <td>{{$row->MH1}}</td>
          </tr>
          <tr>
            <td>LNH</td>
            <td>{{$row->ML}}</td>
          </tr>
          <tr>
            <td>HI-TEA</td>
            <td>{{$row->MH2}}</td>
          </tr>
          <tr>
            <td>DNR</td>
            <td>{{$row->MD}}</td>
          </tr>
          <tr>
            <td colspan="5"></td>
          </tr>
          @php $count = $count + 1; @endphp
          @endforeach
          @for($i = 0; $i < (3 - $count ); $i++)
            <tr>
              <td width="5%" rowspan="5"></td>
              <td width="25%" rowspan="5"></td>
              <td width="3%">BFF</td>
              <td width="15%"></td>
              <td width="10%" rowspan="5"></td>
            </tr>
            <tr>
              <td>HI-TEA</td>
              <td></td>
            </tr>
            <tr>
              <td>LNH</td>
              <td></td>
            </tr>
            <tr>
              <td>HI-TEA</td>
              <td></td>
            </tr>
            <tr>
              <td>DNR</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="5"></td>
            </tr>
          @endfor
      </tbody>
    </table>
    <table style="page-break-inside: avoid;">
      <tbody>
        <tr>
          <td class="title">TOUR GUIDE NOTES</td>
        </tr>
        <tr>
          <td  height="70px">{!! $data[0]->NoteTourGuide !!}</td>
        </tr>
      </tbody>
    </table>
    <table style="page-break-inside: avoid;">
      <tbody>
        <tr>
          <td class="title">REMARKS</td>
        </tr>
        <tr>
          <td height="100px">{!! $data[0]->Note !!}</td>
        </tr>
      </tbody>
    </table>
    </div>
  </main>
  <footer>
  </footer>
</body>
</html>