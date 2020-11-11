<!DOCTYPE html>
<html>
    <head>
        <style>
        </style>
    </head>
<body>
    <header>
        <div style="text-align: left;">
            <img src="{{$dataReport->User->CompanyObj->Image}}" width="70" style="float:right;" />
            <div style="font-size: 26px;">{{strtoupper($dataReport->Title)}}</div>
            <strong style="font-size:13px">{{strtoupper($dataReport->User->CompanyObj->Name)}}</strong><br />
            <div style="font-size:11px">{{$dataReport->Filter}}</div>
            <div style="clear:both"></div><br />
        </div>
    </header>
</body>
</html>