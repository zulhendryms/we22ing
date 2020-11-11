<!DOCTYPE html>
<html>
    <head>
        <style>
            .column1 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: left;
                width: 50%;
                padding: 5px;
                height: 100px;
                border: 1px solid black;
            }
            .column2 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: left;
                width: 50%;
                padding: 5px;
                height: 100px;
                border: 1px solid black;
            }
            .row:after {
                content: "";
                display: block;
                clear: both;
            }
            img {
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
        </style>
    </head>
<body>
    <header style="padding-bottom: 5px;">
        <table>
            <div class="column1" >
                <p><strong>{{$data[0]->CompanyName}}</strong></p>
                <p>-</p>
            </div>
        </table>
        <table>
            <div class="column2">
            <div><strong>{{$headertitle}}</strong></div>
                <div>Session Day : {{date('d F Y', strtotime($data[0]->Ended))}}<div>
                <div>Name Cashier : {{$data[0]->Cashier}} </div>
                <div>Tgl Cetak : {{date("d F Y H:i:s")}} </div>
            </div>
        </table>
    </header>
</body>
</html>