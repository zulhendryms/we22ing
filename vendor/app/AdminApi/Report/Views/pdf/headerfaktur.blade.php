<!DOCTYPE html>
<html>
    <head>
        <style>
            .column1 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: left;
                width: 30%;
                padding: 5px;
                height: 170px;
                border: 2px solid black;
            }
            .column2 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: left;
                width: 30%;
                padding: 5px;
                height: 170px;
                
            }
            .column3 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: right;
                width: 30%;
                padding: 5px;
                height: 170px;
                
            }
            .image {
                border-collapse: collapse;

                float: center;
                width: auto;
                height: 170px;
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
                <div class="image" >
                <img src="{{$data[0]->CompanyLogo}}" width="auto" height="80">
                <p><strong>{{$data[0]->CompanyName}}</strong></p>
                <p>{{$data[0]->NoTlp}}</p>
                
                </div>
            </div>
        </table>
        <div style="display:block; border: 2px solid black; height: 166px;">
            <div class="column2">
                <div><strong>FAKTUR PENJUALAN</strong></div>
                <div>No Invoice :{{$data[0]->Code}} </div>
                <div>Date : {{date('d F Y', strtotime($data[0]->Date))}}</div>
                <div>Cashier : {{$data[0]->Cashier}}</div>
                <div>Room : {{$data[0]->Room}} </div>
                    
            </div>
            <div class="column3">
                <p> </p>
                <div>Sales : {{$data[0]->Sales}}</div>
                <div>Payment : {{$data[0]->Payment}}</div>
                <div>Customer : {{$data[0]->Customer}}</div>
                <div>Project : {{$data[0]->Project}}</div>
                <div>Employee : {{$data[0]->Employee2}}</div>
                <div>Date Print : {{date("d F Y H:i:s")}} </div>
            </div>
        </div>
    </header>
</body>
</html>