{{-- @include('AdminApi\Trading::pdf.headerfaktur') --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
    p{
      line-height: normal;
      padding: 0px;
      margin: 0px;
    }
    img {
        display: block;
        margin-left: auto;
        margin-right: auto;
        max-width: 250px;
        max-height: 100px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-bottom: 10px;
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
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #5D6975;
      vertical-align: top;
      font-size: 12px;
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
      font-weight: bold; }
  </style>
</head>
  
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
      @if ($data[0]->Type == 'Transfer')
          <table height="50px" style="padding-top:2px; padding-bottom:1px">
            <tbody>
              <tr>
                <td>
                  FROM ACCOUNT: {{$data[0]->AccountCashBank}}</br></br>
                  TRANSFER AMOUNT: {{$data[0]->Cur}} {{number_format($data[0]->TotalAmount)}}</br></br>
                  TO ACCOUNT: {{$data[0]->TransferAccount}}</br></br>
                  TRANSFER AMOUNT: {{$data[0]->TransferCur}} {{number_format($data[0]->TransferAmount * $data[0]->TransferRateBase)}}</br></br>
                </td>
              </tr>
            </tbody>
          </table>
      @endif
      @if ($data[0]->Note)
        <table height="50px" style="padding-top:2px; padding-bottom:1px">
          <tbody>
            <tr>
              <td>
                Note : {!! $data[0]->Note !!}
                @if ($data[0]->Approval1Note) </br><strong>Note dari {{$data[0]->Approval1}}:</strong> {{$data[0]->Approval1Note}} @endif
                @if ($data[0]->Approval2Note) </br><strong>Note dari {{$data[0]->Approval2}}:</strong> {{$data[0]->Approval2Note}} @endif
                @if ($data[0]->Approval3Note) </br><strong>Note dari {{$data[0]->Approval3}}:</strong> {{$data[0]->Approval3Note}} @endif
              </td>
            </tr>
          </tbody>
        </table>
      @endif
      @if (!$data[0]->TransferAccount)
        <table>
          <thead>{{--width:675px--}}
              <th class="firstcol" style="width:30px">NO</th>
              <th style="width:250px">Account</th>
              <th style="width:300px">Description</th>
              <th class="lastcol" style="width:30px">Amount</th>
            </tr>
          </thead>   
          <tbody>
            @php $count=1;  $totalAmount=0; @endphp
            @foreach($data as $row)
              <tr>
                <td class="firstcol">{{$count}}</td>
                <td>{{$row->Account}} 
                </td>
                <td>{{$row->Description}}
                  @if($row->CostCenter)</br>For: {{$row->CostCenter}}@endif
                  @if($row->Notedetail)</br>Note : {{$row->Notedetail}}@endif</td>
                <td class="lastcol" align="right">{{number_format($row->Amount ,2)}}</td>
                    @php
                      $totalAmount = $totalAmount + $row->Amount; 
                    @endphp
              </tr>
              @php $count++;  @endphp
            @endforeach
            
              <tr>
                <td colspan="3" align="right"><strong>TOTAL {{$data[0]->Cur}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($totalAmount ,2)}}</strong></td>
              </tr>
          </tbody>
        </table>
      @endif
  </main>
  @if($PaperSize == 'A4')
  <footer>
    <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
         @if ($data[0]->Requestor1)
           <div style="font-size:11px; padding-right: 27px; float: left;">
               <p style="margin-bottom: 40px;">{{$data[0]->Requestor1}}</p>
               <p>{{$data[0]->ap1}}</p>
           </div>
         @endif
         @if ($data[0]->Requestor2)
           <div style="font-size:11px; padding-right: 27px; float: left;">
               <p style="margin-bottom: 40px;">{{$data[0]->Requestor2}}</p>
               <p>{{$data[0]->ap2}}</p>
           </div>
         @endif
       
         @if ($data[0]->Approval1)
           <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
             <p style="margin-bottom: 40px;">Approval 3 : {{$data[0]->Approval1}}
               <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval1Date}}
               <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval1Hour}}</p>
             <p></p>
           </div>
         @endif
         @if ($data[0]->Approval2)
         <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
           <p style="margin-bottom: 40px; ">Approval 2 : {{$data[0]->Approval2}}
             <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval2Date}}
             <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval2Hour}}</p>
             <p></p>
           </div>
           @endif
           @if ($data[0]->Approval3)
           <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
             <p style="margin-bottom: 40px;">Approval 1 : {{$data[0]->Approval3}}
               <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval3Date}}
               <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval3Hour}}</p>
             <p></p>
             </div>
           @endif
     </div>
   </footer>
   @endif
</body>
</html>