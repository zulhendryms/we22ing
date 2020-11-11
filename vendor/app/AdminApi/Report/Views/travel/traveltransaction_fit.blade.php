<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$dataReport->reporttitle}}</title>
    <style type="text/css">
    body{
        line-height: normal;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #ffffff;
      border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975;
      background: #888888;
      font-size: 15px;
      padding-top:5px;
      padding-bottom:5px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #dddddd;
      vertical-align: top;
      font-size: 13px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:5px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    </style>
</head>
<body>
    {{-- <table width="100%">
        <tr>
            <td style="border: none !important" width="10%">
                Report Title </br>
                Date </br>
                Invoice No </br>
                Country </br>
                Invoice From Date </br>
                Invoice To Date </br>
                Agent </br>
                Tour Code </br>
                Type </br>
                Status </br>
                Departure From </br>
                Departure To
            </td>
            <td style="border: none !important" width="90%">
                : {{$dataReport->reporttitle}}</br>
                : {{date("d F Y H:i:s")}}</br>
                : </br>
                : </br>
                : {{date('d F Y', strtotime($datefrom))}}</br>
                : {{date('d F Y', strtotime($dateto))}}</br>
                : Agent</br>
                : TourCode</br>
                : Type</br>
                : Status</br>
                : </br>
                : 
            </td>
        </tr>
    </table> --}}

    <table width="100%">
        <thead>
            <tr>
                <th width="80px" class="firstcol">INVOICE NO</th>
                <th width="80px">INVOICE DATE</th>
                <th width="120px">COUNTRY</th>
                <th width="120px">TOUR CODE</th>
                <th width="100px">INVOICE STATUS</th>
                <th width="100px">CUSTOMER CODE</th>
                <th width="250px">CUSTOMER NAME</th>
                <th width="120px">YOUR REFF</th>
                <th width="200px">PAYMENT TERM</th>
                <th width="150px">CURR</th>
                <th width="150px">INVOICE AMOUNT</th>
                <th width="150px">CONTACT PERSON</th>
                <th width="150px">OFFICE TEL</th>
                <th width="150px">FAX</th>
                <th width="100px" class="lastcol">STATUS</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{$row->Code}}</td>
            <td>{{date('d F y', strtotime($row->Date))}}</td>
            <td> @php $v = null; try {  $v = $row->CustomerObj->CityObj->CountryObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->TravelTransactionObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->StatusObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->CustomerObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->CustomerObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->TravelTransactionObj->CodeReff; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->PaymentMethodObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->CurrencyObj->Code; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td align="right">{{$row->TotalAmount}}</td>
            <td> @php $v = null; try {  $v = $row->UserObj->ContactPerson; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->UserObj->PhoneNumber; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->UserObj->FaxNumber; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
            <td> @php $v = null; try {  $v = $row->StatusObj->Name; } catch (\Exception $ex) {  $err = true; } if ($v) echo $v;@endphp </td>
        </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>