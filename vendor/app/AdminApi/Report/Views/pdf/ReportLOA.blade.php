<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Letter Of Authorization</title>
  <style type = "text/css">
  @page { margin: 110px 25px; }
  
    p{
        font-family: Verdana, Geneva, Tahoma, sans-serif;
        font-size: 9pt;
        white-space: normal;
        line-height: normal;
    }
    body{
        margin-left: 30px;
    }
    .bold{
      font-weight: 600;
    }
    .fright{
      float:right;
    }
    .fleft{
      float: left;
    }
    .remark {
        vertical-align:text-top;
        width: 99.5%;
        height: 150px;
        border: 1px solid black;
        font-size: 9pt;
        line-height: normal;
    }
    .grid-container {
        display: grid;
        justify-items: center;
        align-items: center;
        grid-template-columns: auto auto auto;
        padding: 10px;
    }
    .grid-item {
        background-color: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(0, 0, 0, 0.8);
        height: 200px;
        width: 350px;
        padding: 20px;
    }
    img{
        width: 320px;
        height: 200px;
        padding-left: 90px;
        padding-bottom: 50px;
        padding-top: 60px;
    }
    .b{
          border-bottom: none;
      }
    .t{
        border-top: none;
    }
    .l{
        border-left: none;
    }
    .r{
        border-right: none;
    }
    
  </style>
</head>
<header>
    <h4 style="text-align: center;">LETTER OF AUTHORIZATION</h4>
</header>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
    <div>
        <p>&emsp;&emsp;I, Cardholder of the credit card (whose details are set per below) hereby authorise<span style="font-weight: 600;"> {{$data[0]->BusinessPartner}}</span>
          to charge <span style="font-weight:600;">{{$data[0]->CurrencyCode}}, {{number_format($data[0]->TotalAmount ,2)}}</span> for the following services in the enclosed attachment :-</p>
    </div>
    <table>
      <tbody>
        <tr style="line-height:101px;">
          <p style="padding-left:50px">CARDHOLDER NAME <span style="margin-left:44px;">: {{$data[0]->CardName}}</span></p>
          <p style="padding-left:50px">CREDIT CARD NUMBER <span style="margin-left:28px;">: {{$data[0]->CardNumber}}</span></p>
          <p style="padding-left:50px">DATE OF EXPIRY <span style="margin-left:68px;">: {{$data[0]->DueDateCard}}</span></p>
          <p style="padding-left:50px">CVV<span style="margin-left:147px;">: </span></p>
          <p style="padding-left:50px">TYPE OF CARD <span style="margin-left:79px;">: {{$data[0]->TypeCard}}</span></p>
          <p style="padding-left:50px">CARDHOLDER’S ADDRESS <span style="margin-left:10px;">: {{$data[0]->CardAddress}}</span></p>
      </tr>
      </tbody>
    </table>
    
   <ul>
        <div class="remark">
            <p style="padding-left:10px;">REMARK : Refer to attachment Annex 1</p>
            <p style="padding-left:10px;">{{$data[0]->Remark}}</p>
        </div>
   </ul>

   <tr class="grid-container" >
       <td class="grid-item"><img src="{{$data[0]->Image1}}"></td>
       <td class="grid-item"><img src="{{$data[0]->Image2}}"></td> 
   </tr>

    <p>&emsp;&emsp;As this form is only valid for 2 weeks, please charge timely and accordingly. If any discrepancy amount as described above, please do not hesitate to approach us for a revise Letter of Authorisation form.
        <br><br>For GST filing purpose, we appreciate that the tax invoices could be sent to :-<br>
        <ul>
            <p><span style="font-weight: 600;">Email to: {{$data[0]->Email}}</span></p>
            <p>Alternatively, mail to address: {{$data[0]->CompanyAddress}}</p>
        </ul>
    <p>&emsp;&emsp;Both parties have responsible to treat this information as confidential and not misuse, copy, disclose, distribute or retain the information in any way that amounts to a breach of confidentiality.</p>
    </p>
    <table width="100%">
      <tbody>
        <tr>
          <td class="b t l r" width="70%">
              <img style="width:120px; height:50px;" src="{{$data[0]->ImageSignature}}">
              <br>______________________<br>
              <span style="font-size:9pt">Agreed & Accepted by<br>Cardholder</span>
          </td>
          <td class="b t l r" width="30%" style="vertical-align: bottom;">
            <span style="font-size:9pt; padding-left:50px;">{{$data[0]->Date}}</span>
            <br>
            <br>
            <br>
            <br>
            ______________________
            <br>
            <br>
            <br>
          </td>
        </tr>
      </tbody>
    </table>
<div style="page-break-after:always;">&nbsp;</div>

<style type ="text/css">   
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
    border: 2px solid #5D6975;
    /* border-top: 1px solid  #5D6975;
    border-bottom: 1px solid  #5D6975; */
    background: #888888;
    font-size: 14px;
    padding-top:10px;
    padding-bottom:10px;
    padding-left:10px;
    padding-right:10px;
  }
  table td {
    border: 2px solid #5D6975;
    vertical-align: top;
    font-size: 12px;
    padding-top:10px;
    padding-bottom:2px;
    padding-left:2px;
    padding-right:1px;
  }
  .alignR{
    text-align: right;
  }
  .alignM{
    text-align: center;
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
<header style="padding-bottom:60px">
  <p><span class="fleft bold">ANNEX 1</span><span class="fright bold">LOA REF No. {{$dataDetail[0]->LOACode}}</span></p>
  <br>
  <p><span class="fleft">MERCHANT NAME :{{$dataDetail[0]->MerchantName}}</span><span class="fright">AMT CHARGED : SGD38,309.03</span></p>
</header>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
<main>
  <div class="container" style="padding-left: 8px; padding-right: 8px;">
    <table>
      <thead>
        
        <tr>
          <th class="firstcol" style="width:30px">NO</th>
          <th style="width:300px">Date</th>
          <th style="width:300px">Confirm No</th>
          <th style="width:300px">Tour Code</th>
          <th style="width:300px">Amount</th>
          <th style="width:200px">Remarks</th>
          <th class="lastcol" style="width:200px">Finance Ref</th>
        </tr>
      </thead>   
      <tbody>
        @php $count=1; $amountTotal=0;@endphp
        @foreach($dataDetail as $row)
          <tr>
            <td class="firstcol alignM">{{$count}}</td>
            <td>{{$row->DateLOA}}</td>
            <td>{{$row->ConfirmNo}}</td>
            <td>{{$row->TourCode}}</td>
            <td class="alignR" >{{$row->AmountLOA}}</td>
                @php
                  $amountTotal = $row->AmountLOA;
                @endphp
            <td align="right">{{$row->Remark}}</td>
            <td class="lastcol alignM" >{{$row->FinanceRef}}</td>
          </tr>
          @php $count++;@endphp
        @endforeach
          <tr>
            <td colspan="4" class="alignM bold"><strong>TOTAL</strong></td>
            <td class="total alignR" ><strong>{{number_format($amountTotal ,2)}}</strong></td>
            <td colspan="2"></td>
          </tr>
      </tbody>
    </table>
  </div>
  <div style="padding: 13px 20px 13px 20px;">
    <div style="font-size: 14px; color: #858585;"></div>
  </div>
</main>
</body>
</html>