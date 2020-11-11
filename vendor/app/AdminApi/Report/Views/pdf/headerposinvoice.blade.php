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
                height: 120px;
            }
            .column2 {
                border-collapse: collapse;
                box-sizing: border-box;
                float: left;
                width: 50%;
                padding: 5px;
                height: 120px;
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
        <div style="text-align:left;">
            <img src="{{$data[0]->CompanyLogo}}" width="900" height="120" style="float:left; padding-right: 5px" />
            <div style="font-size: 20px; float:right; padding-right:150px;">PROFORMA INVOICE</div>
            <div style="clear:both"></div><br />
        </div>
        <table>
            <div class="column1" >
                <div>To : {{$data[0]->CustomerAddress}}</div>
                <div>Attn : {{$data[0]->Customer}}</div>
                <div>Tel : {{$data[0]->CustomerPhone}} - Tax : {{$data[0]->CustomerTax}}</div>
                <div>A/C No : {{$data[0]->CustomerCode}}</div>
            </div>
        </table>
        <table>
            <div class="column2">
                <div>No :{{$data[0]->Code}} </div>
                <div>Date : {{date('d F Y', strtotime($data[0]->Date))}}</div>
                <div>Term : {{$data[0]->TermName}} </div>
                <div>Your Reff : {{$data[0]->CodeReference}}</div>
                <div>Sales Person : {{$data[0]->Sales}}</div>
            </div>
        </table>
    </header>
</body>
</html>