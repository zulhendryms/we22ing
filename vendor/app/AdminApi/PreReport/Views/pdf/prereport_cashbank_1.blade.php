<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$dataReport->reporttitle}}</title>
    <style type="text/css">
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
      border-bottom: 1px solid black;
      white-space: nowrap;
      font-weight: bold; 
      color: #000000;
      border: 1px solid #5D6975;
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid black;
      vertical-align: top;
      font-size: 13px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
    }
    .z{
        border-top: none;
        border-bottom: none;
        border-left: none;
        border-right: none;
    }
    .B{
        border-top: none;
        border-left: none;
        border-right: none;
        text-align:center;
    }
    .f12{
        font-size: 12pt;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    </style>
</head>
<body>
    <table width="100%">
        <td width="100px" class="z">
            <img src="{{$data->CompanyObj->Image}}" width="auto" height="auto">
        </td>
        <td width="500px" class="z">
            <span style="font-size: 22px !important"><strong>{{strtoupper($data->CompanyObj->Name)}}</strong></span></br>
            <span style="font-size: 10px">
                Address : {{$data->CompanyObj->FullAddress}}</br>
                @if($data->CompanyObj->Phone) Phone   : {{$data->CompanyObj->PhoneNo}}</br> @endif
                @if($data->CompanyObj->Email) E-mail  : {{$data->CompanyObj->Email}}</br> @endif
            </span>
        </td>
        <td width="400px" align="left" class="z">
            <span style="font-size: 30px !important"><strong>
                {{-- payment & exepese bkk income receipt official receipt --}}
                @php 
                    if ($data->AccountObj->AccountTypeObj->Code == 'CASH') $type = 'KAS'; else $type = 'BANK';
                    switch ($data->Type) {
                        case 0: echo "OFFICIAL RECEIPT"; break;
                        case 1: echo "BUKTI ".$type." KELUAR"; break;
                        case 2: echo "OFFICIAL RECEIPT"; break;
                        case 3: echo "BUKTI ".$type." KELUAR"; break;
                        case 3: echo "TRANSFER RECEIPT"; break;
                        default: echo "BUKTI ".$type." KELUAR"; break;
                    }
                @endphp
            </strong></span>
        </td>
        <tr>
            <td width="600px" colspan="2" class="z f12">
                Nomor <span style="margin-right: 52px"></span>: {{$data->Code}}</br>
                Tanggal <span style="margin-right: 49px"></span>: {{date('d F Y', strtotime($data->Date))}}</br>
                Dibayar Kpd <span style="margin-right: 16px"></span>: {{$data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null}}</br>
            </td>
            <td width="400px" class="z f12">
                Account <span style="margin-right: 69px"></span>: {{$data->AccountObj ? $data->AccountObj->Name : null}}</br>
                No. Reff <span style="margin-right: 67px"></span>: {{$data->CodeReff}}</br>
            </td>
            <td class="z"></td>
        </tr>
    </table>
    <!-- Transfer -->
    @if ($data->Type == 4)
          <table height="50px" style="padding-top:2px; padding-bottom:1px">
            <tbody>
              <tr>
                <td>
                  FROM ACCOUNT: {{$data->AccountObj->Name}}</br></br>
                  TRANSFER AMOUNT: {{$data->CurrencyObj->Code}} {{number_format($data->TotalAmount)}}</br></br>
                  TO ACCOUNT: {{$data->TransferAccountObj->Name}}</br></br>
                  TRANSFER AMOUNT: {{$data->TransferCurrencyObj->Code}} {{number_format($data->TransferAmount * $data->TransferRateBase)}}</br></br>
                </td>
              </tr>
            </tbody>
          </table>
      @endif
    <!--  -->
    <!-- cashbank bbk -->
    @if ($data->Type != 4)
    <table width="100%">
        <thead>
            <tr>
                <th width="500px">DESCRIPTION</th>
                <th width="30px">CUR</th>
                <th width="70px">AMOUNT</th>
                <th width="70px">EX. RATE</th>
                <th width="70px">TOTAL</th>
            </tr>
        </thead>
        <tbody>
        @php $sumAmount = 0; $totalAmount=0; @endphp
        @foreach($data->Details as $row)
        <tr>
            <td>
                {{$row->Description}}
                @if ($row->Note)
                </br><b style="line-height:normal">Note : {{$row->Note}}</b>
                @endif
            </td>
            <td align="center">{{$row->CurrencyObj->Code}}</td>
            <td align="right">{{number_format($row->AmountInvoice,2)}} </td>
            <td align="right">{{number_format($row->AmountCashBank / $row->AmountInvoice,2)}} </td>
            @php $totalAmount = $totalAmount + $row->AmountCashBank; @endphp
            <td align="right">{{number_format($row->AmountCashBank,2)}} </td>
        </tr>
        @endforeach
        <tr>
            <td colspan="4" align="right"><b> Total : </b> </td>
            <td align="right"><b>{{number_format($totalAmount,2)}} </b></td>
        </tr>
        </tbody>
    </table>
    @endif
<!--  -->
    <table width="100%">
        <tr>
            <td class="z" width="5%">Terbilang </td>
            <td class="z" width="95%"> : {{$data->TotalAmountWording}} {{$data->CurrencyObj->Name}}</td>
        </tr>
        @if ($data->Note)
        <tr>
            <td class="z">Note </td>
            <td class="z"> : {!! $data->Note !!}</td>
        </tr>
        @endif
    </table>
    <div style="page-break-inside: avoid;">
        <div style="border:1px solid black; height:100px; margin-right:5px; width:120px; float: left;">
            <table>
                <td class="B"><strong>Disetujui</strong></td>
            </table>
        </div>

        <div style="border:1px solid black; height:100px; margin-right:5px; width:230px; float: left;">
            <table>
                <td class="B"><strong>Diketahui</strong></td>
            </table>
        </div>

        <div style="border:1px solid black; height:100px; margin-right:5px; width:230px; float: left;">
            <table>
                <td class="B"><strong>Dibayarkan Oleh</strong></td>
            </table>
        </div>

        <div style=" float: right; padding-right:40px;margin-top:0px">
            <p align="left">Batam, ..................... 20....</br>
                Diterima Oleh</p>
        </div>
    </div>
    
    
</body>
</html>