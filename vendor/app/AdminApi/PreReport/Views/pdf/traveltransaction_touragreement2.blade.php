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
    ul.a{ 
      line-height:normal;
      padding-bottom:50px;
      list-style-type: square;
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
            <th class="title" colspan="2">TOUR AGREEMENT</th>
          </tr>
            <tr>
              <td>Tour Code  : {{$data[0]->TourCode}}</td>
              <td>Invoice No : {{$data[0]->SalesInvoiceCode}}</td>
            </tr>
            <tr>
              <td>Your Ref  : {{$data[0]->CodeReff}}</td>
              <td>Company   : {{$data[0]->Customer}}</td>
            </tr>
            <tr>
              <td>{{$data[0]->City}} - In Charge : {{$data[0]->ContactName}} / {{$data[0]->ContactNumber}}</td>
              <td>Driver Detail   : {{$data[0]->DriverDetail}}</td>
            </tr>
            <tr>
              <td>SG - In Charge : {{$data[0]->UserProcess}} / {{$data[0]->UserProcessPhone}}</td>
              <td>Tour Guide : {{$data[0]->TourGuide1}} / {{$data[0]->TourGuide2}}</td>
            </tr>

            <tr>
              <td class="title" colspan="2">FLIGHT DETAILS</td>
            </tr>
            @php
              $flight1arr=null; $flight1dep=null; $flight2arr=null; $flight2dep=null;
              foreach($dataFlight as $f) {
                if ($f->FlightType == 'Arrival' && !$flight1arr) $flight1arr = $f->FlightDate." | 1ST ARR FLT | ".$f->FlightCode;
                elseif ($f->FlightType == 'Arrival' && $flight1arr) $flight2arr = $f->FlightDate." | 2ND ARR FLT | ".$f->FlightCode;
                elseif ($f->FlightType == 'Departure' && !$flight1dep) $flight1dep = $f->FlightDate." | 1ST DEP FLT | ".$f->FlightCode;
                elseif ($f->FlightType == 'Departure' && $flight1dep) $flight2dep = $f->FlightDate." | 2ND DEP FLT | ".$f->FlightCode;
              }
            @endphp
            <tr>
              <td>1ST ARR DATE :<br>&emsp;&emsp;<span>{{$flight1arr}}</span></td>
              <td>1ST DPT DATE :<br>&emsp;&emsp;<span>{{$flight1dep}}</span></td>
            </tr>            
            <tr>
              <td>2ND ARR DATE :<br>&emsp;&emsp;<span>{{$flight2arr}}</span></td>
              <td>2ND DPT DATE :<br>&emsp;&emsp;<span>{{$flight2dep}}</span></td>
            </tr>
          </tbody>
      </table>
      <table>
          <tbody>
            <tr>
              <td class="title" colspan="5">Room & Pax Details</td>
            </tr>
            <tr>
                <td colspan="2">
                  @php
                   $stringRoom = '';
                   if ($dataHotel[0]->DBL) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->DBL.' DBL';
                   if ($dataHotel[0]->SGL) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->SGL.' SGL';
                   if ($dataHotel[0]->TWN) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->TWN.' TWN';
                   if ($dataHotel[0]->ExBed) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->ExBed.' ExBed';
                   if ($dataHotel[0]->Breakfast) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->Breakfast.' Breakfast';
                   if ($dataHotel[0]->SC) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->SC.' SC';
                   if ($dataHotel[0]->FOC) $stringRoom = $stringRoom.($stringRoom == '' ? : ' + ').$dataHotel[0]->FOC.' FOC';
                  @endphp
                  {{substr($stringRoom,1)}}
                  | Total Room: {{$dataHotel[0]->TotalRoom}}
                </td> 
                <td colspan="3">
                  @php
                   $stringPax = '';
                   if ($data[0]->ADT) $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->ADT.' ADT';
                   if ($data[0]->CWB) $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->CWB.' CWB';
                   if ($data[0]->CNB) $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->CNB.' CNB';
                   if ($data[0]->INF) $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->INF.' INF';
                   if ($data[0]->ExBed) $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->ExBed.' ExBed';
                   if ($data[0]->FOC) $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->FOC.' FOC';
                   if ($data[0]->TL)  $stringPax = $stringPax.($stringPax == '' ? : ' + ').$data[0]->TL.' TL';
                  @endphp
                  {{substr($stringPax,1)}}
                  | Total Pax: {{$data[0]->Total1}}
                </td>
            </tr>

            <tr class="title">
                <td width="10%">Date</td>
                <td width="40%">Hotel</td>
                <td width="15%">Breakfast</td>
                <td width="15%">Lunch</td>
                <td width="15%">Dinner</td>
            </tr>
            @foreach($dataItinerary as $rowItinerary)
            <tr>
                <td>{{$rowItinerary->Date}}</td>
                <td>{{$rowItinerary->Hotel}}</td>
                <td>{{$rowItinerary->MB}}</td>
                <td>{{$rowItinerary->ML}}</td>
                <td>{{$rowItinerary->MD}}</td>
            </tr>
            @endforeach
        </tbody>
      </table>
      <table>
        <tbody>
          <tr class="title">
            <td colspan="2">Planned Itinerary</td>
          </tr>
          @php $count = 0; @endphp
          @foreach($dataItinerary as $row)
          <tr>
            <td width="10%">{{$row->Date}}</td>
            <td width="90%">{!! $row->DescEN !!}</td>
          </tr>
          @php $count = $count + 1; @endphp
          @endforeach
          <tr>
              <td style="border:none;"></td>
          </tr>
          <tr class="title">
              <td colspan="2">Tour Guide Notes</td>
          </tr>
          <tr>
            <td height="50px" colspan="2">{!! $data[0]->NoteTourGuide !!}</td>
          </tr>
          <tr>
            <td class="lr"></td>
          </tr>
          <div style="page-break-inside: avoid;">
            <tr class="title">
                <td colspan="2">General Notes</td>
            </tr>
            <tr><td height="70px" colspan="2">{!! $data[0]->Note !!}</td></tr>
          </div>
          </tbody>
    </table>
    </div>
  </main>
  <footer>
  </footer>
</body>
</html>