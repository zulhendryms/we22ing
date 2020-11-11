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
        <tr> {{--width:675px--}}
            <th class="firstcol">Comp</th>
            <th>Tour Code</th>
            <th>Agent<br>Guide Name</th>
            <th>ADT</th>
            <th>CWB</th>
            <th>CNB</th>
            <th>INF</th>
            <th>TL</th>
            <th>EX</br>BED</th>
            <th>FOC</th>
            <th>PAX</th>
            <th>CUR</th>
            <th>TOUR</br>FARE</th>
            <th>EX</br>RATE</th>
            <th>TOURFARE</br>(SGD)</th>
            <th>SHOP</th>
            <th>DY/OI</th>
            <th>CHOCO</th>
            <th>OPT</br>TOUR</th>
            <th>AMT</br>SALES</th>
            <th>HOTEL</br>AMT</th>
            <th>GUIDE</br>CLAIM</th>
            <th>COACH</br>COMBI</th>
            <th>SERDIZ</th>
            <th>A.C</th>
            <th>TIX</th>
            <th>AMTCOST</th>
            <th class="lastcol">PROFIT</th>
        </tr>
      </thead>
      <tbody> 
        @php 
        $group = ""; 
        $ADT=0; $CWB=0; $CNB=0; $INF=0; $TL=0; $FOC=0; $ExBed=0; $PAX=0; $TourFare=0; 
        $ExRate=0; $TourFareSGD=0; $Shop=0; $OthIncome=0; $Choco=0; $OptTour=0; $AmtSales=0; $HotelAmount=0; 
        $GuideClaim=0; $Coach=0; $Serdiz=0; $AC=0; $Tickets=0; $AmtCost=0; $Profit=0;
        @endphp
        @foreach($data as $row)
          @if ($group != $row->GroupName)
             @if ($group !="")
                  <tr>
                    <td colspan="3" align="centre"><strong>Total: </strong></td>
                    <td class="total" align="right"><strong>{{ number_format($ADT ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($CWB ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($CNB ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($INF ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($TL ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($ExBed ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($FOC ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($PAX ,2) }}</strong></td>
                    <td></td>
                    <td class="total" align="right"><strong>{{ number_format($TourFare ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($ExRate ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($TourFareSGD ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($Shop ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($OthIncome ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($Choco ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($OptTour ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($AmtSales ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($HotelAmount ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($GuideClaim ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($Coach ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($Serdiz ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($AC ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($Tickets ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($AmtCost ,2) }}</strong></td>
                    <td class="total" align="right"><strong>{{ number_format($totalProfit ,2) }}</strong></td>
                </tr>
            @endif
                <tr>
                  <td class="group" colspan="27"><strong>{{$row->GroupName}}</strong></td>
                </tr>
            @php $group = $row->GroupName; 
                $ADT=0; $CWB=0; $CNB=0; $INF=0; $TL=0; $FOC=0; $ExBed=0; $PAX=0; $TourFare=0; 
                $ExRate=0; $TourFareSGD=0; $Shop=0; $OthIncome=0; $Choco=0; $OptTour=0; $AmtSales=0; $HotelAmount=0; 
                $GuideClaim=0; $Coach=0; $Serdiz=0; $AC=0; $Tickets=0; $AmtCost=0; $Profit=0; 
        @endphp
          @endif
 
              
          <tr>
            <td class="firstcol">{{$row->Comp}}</br>
              {{$row->Code}}</td>
            <td>{{$row->TourCode}}</td>
            <td class="small">{{$row->AgentCode}}</br>
              {{$row->AgentName}}</br>
              {{$row->TourGuide1}}
              </td>
            <td align="right">{{number_format($row->ADT ,2)}}</td>
            <td align="right">{{number_format($row->CWB ,2)}}</td>
            <td align="right">{{number_format($row->CNB ,2)}}</td>
            <td align="right">{{number_format($row->INF ,2)}}</td>
            <td align="right">{{number_format($row->TL ,2)}}</td>
            <td align="right">{{number_format($row->ExBed ,2)}}</td>
            <td align="right">{{number_format($row->FOC ,2)}}</td>
            <td align="right">{{number_format($row->PAX ,2)}}</td>
            <td>{{$row->Cur}}</td>
            <td align="right">{{number_format($row->TourFare ,2)}}</td>
            <td align="right">{{number_format($row->ExRate ,2)}}</td>
            <td align="right">{{number_format($row->TourFareSGD ,2)}}</td>
            <td align="right">{{number_format($row->Shop ,2)}}</td>
            <td align="right">{{number_format($row->OthIncome ,2)}}</td>
            <td align="right">{{number_format($row->Choco ,2)}}</td>
            <td align="right">{{number_format($row->OptTour ,2)}}</td>
            <td align="right">{{number_format($row->AmtSales ,2)}}</td>
            <td align="right">{{number_format($row->HotelAmount ,2)}}</td>
            <td align="right">{{number_format($row->GuideClaim ,2)}}</td>
            <td align="right">{{number_format($row->Coach ,2)}}</td>
            <td align="right">{{number_format($row->Serdiz ,2)}}</td>
            <td align="right">{{number_format($row->AC ,2)}}</td>
            <td align="right">{{number_format($row->Tickets ,2)}}</td>
            <td align="right">{{number_format($row->AmtCost ,2)}}</td>
            <td align="right" class="lastcol">{{number_format($row->Profit ,2)}}</td>
        </tr>
        @php
            $ADT = $ADT + $row->ADT;
            $CWB = $CWB + $row->CWB;
            $CNB = $CNB + $row->CNB;
            $INF = $INF + $row->INF;
            $TL = $TL + $row->TL;
            $ExBed = $ExBed + $row->ExBed;
            $FOC = $FOC + $row->FOC;
            $PAX = $PAX + $row->PAX;
            $TourFare = $TourFare + $row->TourFare;
            $ExRate = $ExRate + $row->ExRate;
            $TourFareSGD = $TourFareSGD + $row->TourFareSGD;
            $Shop = $Shop + $row->Shop;
            $OthIncome = $OthIncome + $row->OthIncome;
            $Choco = $Choco + $row->Choco;
            $OptTour = $OptTour + $row->OptTour;
            $AmtSales = $AmtSales + $row->AmtSales;
            $HotelAmount = $HotelAmount + $row->HotelAmount;
            $GuideClaim = $GuideClaim + $row->GuideClaim;
            $Coach = $Coach + $row->Coach;
            $Serdiz = $Serdiz + $row->Serdiz;
            $AC = $AC + $row->AC;
            $Tickets = $Tickets + $row->Tickets;
            $AmtCost = $AmtCost + $row->AmtCost;
            $totalProfit = 0;
            $totalProfit = $totalProfit + $row->Profit;
        @endphp
        @endforeach
        <tr>
              <td colspan="3" align="centre"><strong>Total: </strong></td>
              <td class="total" align="right"><strong>{{ number_format($ADT ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($CWB ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($CNB ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($INF ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($TL ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($ExBed ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($FOC ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($PAX ,2) }}</strong></td>
              <td></td>
              <td class="total" align="right"><strong>{{ number_format($TourFare ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($ExRate ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($TourFareSGD ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($Shop ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($OthIncome ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($Choco ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($OptTour ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($AmtSales ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($HotelAmount ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($GuideClaim ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($Coach ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($Serdiz ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($AC ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($Tickets ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($AmtCost ,2) }}</strong></td>
              <td class="total" align="right"><strong>{{ number_format($totalProfit ,2) }}</strong></td>
        </tr>
      </tbody>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>