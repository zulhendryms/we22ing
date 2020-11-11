<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>

{{-- PURCHASE ORDER --}}
@if($reportname == 'purchaseorder')
<body>
    <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
        <div style="font-size:12px; padding-right: 10px;  width:180px;">
          <p style="text-align:center; width:189px;">Prepared By</br></br></br>{{$data[0]->Purchaser}}</p>
        </div>
</body>
@endif
{{-- PURCHASE-ORDER --}}
{{-- PREREPORT CASHBANK --}}
@if($reportname == 'cashbank')
<footer>
    <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
         <div style="font-size:12px; margin-right: 100px; float: right;">
           <p style="margin-bottom: 40px;">Authorized By</p>
           <p>(________________)</p>
         </div>
     </div>
</footer>
@endif
{{-- PREREPORT CASHBANK --}}
{{-- PURCHASE INVOICE --}}
@if($reportname == 'purchaseinvoice')
<footer>
    <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center;">
 
         <div style="font-size:12px; margin-right: 100px; float: right;">
           <p style="margin-bottom: 40px;">Authorized By</p>
           <p>(________________)</p>
         </div>
     </div>
   </footer>
   @endif
{{-- PURCHASE INVOICE --}}

{{-- PAYMENT REQUEST --}}
@if($reportname == 'paymentrequest')
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
{{-- PAYMENT REQUEST --}}
{{-- PURCHASE-REQUEST --}}
@if($reportname == 'purchaserequest')
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
         @if ($data[0]->Requestor3)
         <div style="font-size:11px; padding-right: 20px; float: left;">
           <p style="margin-bottom: 40px; ">{{$data[0]->Requestor3}}</p>
           <p>{{$data[0]->ap3}}</p>
         </div>
         @endif 
         
         @if ($data[0]->Purchaser)
           <div style="font-size:12px; padding-right: 10px; float: right; ">
             <p style="margin-bottom: 40px;">Purchaser<br>{{$data[0]->Purchaser}}</p>
             <p></p>
           </div>
         @endif
         @if ($data[0]->Approval3)
           <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
             <p style="margin-bottom: 40px;">Approval 3 : {{$data[0]->Approval3}}
               <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval3Date}}
               <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval3Hour}}</p>
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
           @if ($data[0]->Approval1)
           <div style="font-size:12px; padding-right: 10px; float: right; width:140px;">
             <p style="margin-bottom: 40px;">Approval 1 : {{$data[0]->Approval1}}
               <br>Date &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval1Date}}
               <br>Hour &emsp;&emsp;&ensp;&nbsp;: {{$data[0]->Approval1Hour}}</p>
             <p></p>
             </div>
           @endif
     </div>
   </footer>
   @endif
{{-- PURCHASE-REQUEST --}}

</html>