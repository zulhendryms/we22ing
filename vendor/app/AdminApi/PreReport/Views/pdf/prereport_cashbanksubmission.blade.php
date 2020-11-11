<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{$reporttitle}}</title>
  <script type="text/php"></script>
  <style>
    @page {
      margin: 110px 25px;
    }

    p {
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
      padding-top: 15px;
      padding-bottom: 15px;
      padding-left: 10px;
      padding-right: 10px;
    }

    table td {
      border: 1px solid #5D6975;
      vertical-align: top;
      font-size: 12px;
      padding-top: 10px;
      padding-bottom: 2px;
      padding-left: 2px;
      padding-right: 1px;
    }

    table td.firstcol {
      padding-left: 5px;
    }

    table td.lascol {
      padding-right: 5px;
    }

    table th.firstcol {
      padding-left: 5px;
    }

    table th.lascol {
      padding-right: 5px;
    }

    table td.group {
      padding-left: 8px;
      padding-top: 8px;
      font-size: 12px;
      padding-bottom: 8px;
      background: #F5F5F1;
      font-weight: bold;
    }

    @media print {
      footer {
        position: fixed;
        bottom: 0;
      }

      .content-block,
      p {
        page-break-inside: avoid;
      }

      html,
      body {
        width: 210mm;
        height: 297mm;
      }
    }
  </style>
</head>
<header style="padding-bottom: 5px; ">

  <table width="100%">
    <td width="200px" align="center">
      <img src="{{$data[0]->CompanyLogo}}" width="auto" height="auto"><br>
      <strong>{{$data[0]->CompanyName}}</strong><br>
      Address : {{$data[0]->CompanyAddress}}<br>
      @if($data[0]->CompanyPhone)
      Phone : {{$data[0]->CompanyPhone}}<br>
      @endif
      E-mail : {{$data[0]->CompanyEmail}}
    </td>


    <td width="500px">
      <h1>Request Payment Supplier</h1>
      Date Payment : {{date('d F Y', strtotime($data[0]->DatePayment))}}</br>
      Code : {{$data[0]->Code}} </br>
      Department : {{$data[0]->Department}} </br>
      Print Date : {{date("d F Y H:i:s")}}
    </td>
  </table>
</header>

<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">

  <table>
    <thead>
      <tr> {{--width:675px--}}
        <th class="firstcol" style="width:30px">NO</th>
        <th width="50px">PERIOD</th>
        <th width="40px">AMOUNT</th>
        <th class="lastcol" width="300px">Note</th>
      </tr>
    </thead>
    <tbody>
      @php $count=1; $sumAmount=0; $group=""; @endphp
      @foreach($data as $row)
      @if ($group != $row->Description)
      <tr>
        <td colspan="4" class="group"><strong>{{$row->Description}}</strong></td>
      </tr>
      @php $group = $row->Description; @endphp
      @endif
      <tr>
        <td class="firstcol">{{$count}}</td>
        <td>{{$row->Period}}</td>
        <td align="right">{{number_format($row->Amount ,2)}}</td>
        <td class="lastcol">{{$row->InvoiceNote}}</td>
        @php
        $sumAmount = $sumAmount + $row->Amount;
        @endphp
      </tr>
      @php $count++; @endphp
      @endforeach
      <tr>
        <td colspan="2" align="right"><strong>TOTAL {{$data[0]->CurrencyCode}}</strong></td>
        <td class="total" align="right"><strong>{{number_format($sumAmount ,2)}}</strong></td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <div style="padding: 13px 20px 13px 20px;">
    <div style="font-size: 14px; color: #858585;"></div>
  </div>
  <div class="container" style="padding-left: 8px; padding-right: 8px; display: flex; width: 100%; justify-content: center; page-break-inside: avoid;">
    @if ($data[0]->Requestor)
    <div style="font-size:12px; padding-right: 10px;  width:180px;">
      <p style="margin-bottom: 40px; text-align:center; width:189px;">Requestor By</br>{{$data[0]->Requestor}}</p>
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

</body>

</html>