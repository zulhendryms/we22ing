<!DOCTYPE html>
<html>
    <head>
        <style>
        </style>
    </head>
<body>
    <header>
        <div style="text-align: left;">
            <img src="{{$user->CompanyObj->Image}}" width="70" style="float:right;" />
            <div style="font-size: 26px;">{{strtoupper($reporttitle)}}</div>
            <strong style="font-size:13px">{{strtoupper($user->CompanyObj->Name)}}</strong><br />
            <div style="clear:both"></div><br />
        </div>
    </header>
</body>
</html>