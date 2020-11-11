<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Attraction Ticket Listing</title>
    <style type="text/css">
   table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
      }

      table th {
        padding: 15px 10px;
        color: #5d6975;
        border-bottom: 1px solid #c1ced9;
        white-space: nowrap;
        font-weight: bold;
        color: #000000;
        border-top: 1px solid  #5D6975;
        border-bottom: 1px solid  #5D6975;
        font-size: 14px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        padding-right: 10px;
      }
      table td {
        vertical-align: top;
        font-size: 12px;
        padding-top: 10px;
        padding-bottom: 2px;
        padding-left: 2px;
        padding-right: 1px;
      }
      .tic{
          padding-left: 75px;
          font-size: 9pt;
      }


    </style>
</head>
<body>
    <h4>ACE TOURS & TRAVEL PTE LTD</h4>
    <br>
    <h5>Attraction Ticket Listing For Tour Code : <span style="padding-left: 50px;">{{$data[0]->Code}}</span></h5>
    <table width="100%">
        <thead>
            <tr>
                <th width="30px">Issue-Date</th>
                <th width="200px">Attraction Name</th>
                <th width="30px">Type</th>
                <th width="30px">Qty</th>
                <th width="30px">Allocated from Stock</th>
            </tr>
        </thead>
        <tbody>
            @php
                $qtyTotal = 0;
                $ticket = '';
            @endphp
            @foreach($data as $row)
            @if ($ticket != $row->AttractionName) 
            <tr>
                <td align="center" style="font-weight:bold">{{$row->Date}}</td>
                <td align="center" style="font-weight:bold">{{$row->AttractionName}}</td>
                <td align="center" style="font-weight:bold">{{$row->Type}}</td>
                <td align="center" style="font-weight:bold">{{$row->Qty}}</td>
                <td align="center" style="font-weight:bold">{{$row->StockAllocation}}</td>
            </tr>
            @endif
            @php $ticket = $row->AttractionName @endphp

            @endforeach
            @php
            $qtyTotal = $qtyTotal + $row->Qty;
            $form = "";
            $form .= "";
                $count = 0;
                foreach ($data as $row) {
 
                if($count % 5 == 0) $form .='<tr><td colspan="5" style="padding-left:55px;">';
                    $form .= $row->TicketCode.'<span class="tic">';
                    $form .= '</span>';
                if($count % 1 != 0) $form .= '</td></tr>';
                $count++;
                }
                if ($count % 2 != 0) {
                $form .= '</tr></tbody>';
                }
                echo $form;
                @endphp
                
        </tbody>
    </table>
    <p style="font-size:9pt;">Total number of tickets issued from stock : <b>{{$qtyTotal }}</b></p>

    <table>
        <tbody>
            <tr>
                <td>Issued By : <u>{{$data[0]->UserName}}</u>_______________________________</td>
                <td>Received By : _______________________________</td>
            </tr>
        </tbody>
    </table>
</body>
</html>