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
                border-right: none;
            }
            .column2 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: right;
                width: 70%;
                padding: 5px;
                height: 170px;
                border: 2px solid black;
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
            <div class="column1" align="middle">
                <div class="image" >
                {{-- <img src="{{$data[0]->CompanyLogo}}" width="auto" height="80"> --}}
                <p><strong>{{$data[0]->CompanyName}}</strong></p>
                <p><strong>{{$reportname}}</strong></p>
            </div>
                            
            </div>
            <div class="column2" >
                <div>Date           : {{date('d F Y', strtotime($data[0]->Date))}}</div>
                <div>Code           : {{$data[0]->Code}} </div>
                <div>Account Name   : {{$data[0]->AccountName}} </div>
                <div>Warehouse      : {{$data[0]->Warehouse}} </div>
                <div>Costumer       : {{$data[0]->BusinessPartner}}</div>
                <div>Print Date     : {{date("d F Y H:i:s")}} </div>
            
            </div>
        </table>
    </header>
</body>
</html>