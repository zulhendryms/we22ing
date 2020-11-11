<!DOCTYPE html>
<html>
<head>
    <meta http-equiv=Content-Type content="text/html; charset=UTF-8">
    {{-- <title>{{$reporttitle}}</title> --}}
    <script type="text/php"></script>
    <style type="text/css">
        span.cls_003 {
            font-family: Arial, serif;
            font-size: 9.0px;
            color: rgb(0, 0, 0);
            font-weight: normal;
            font-style: normal;
            text-decoration: none
        }

        div.cls_003 {
            font-family: Arial, serif;
            font-size: 9.0px;
            color: rgb(0, 0, 0);
            font-weight: normal;
            font-style: normal;
            text-decoration: none
        }

        span.cls_002 {
            font-family: Arial, serif;
            font-size: 10.6px;
            color: rgb(0, 0, 0);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        div.cls_002 {
            font-family: Arial, serif;
            font-size: 10.6px;
            color: rgb(0, 0, 0);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        span.cls_004 {
            font-family: Arial, serif;
            font-size: 8.8px;
            color: rgb(0, 0, 0);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        div.cls_004 {
            font-family: Arial, serif;
            font-size: 8.8px;
            color: rgb(0, 0, 0);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        span.cls_005 {
            font-family: Arial, serif;
            font-size: 9.7px;
            color: rgb(0, 0, 255);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        div.cls_005 {
            font-family: Arial, serif;
            font-size: 9.7px;
            color: rgb(0, 0, 255);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        span.cls_006 {
            font-family: Arial, serif;
            font-size: 7.9px;
            color: rgb(0, 0, 255);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        div.cls_006 {
            font-family: Arial, serif;
            font-size: 7.9px;
            color: rgb(0, 0, 255);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        span.cls_007 {
            font-family: Arial, serif;
            font-size: 8.8px;
            color: rgb(0, 0, 255);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        div.cls_007 {
            font-family: Arial, serif;
            font-size: 8.8px;
            color: rgb(0, 0, 255);
            font-weight: bold;
            font-style: normal;
            text-decoration: none
        }

        span.cls_008 {
            font-family: Arial, serif;
            font-size: 8.2px;
            color: rgb(0, 0, 255);
            font-weight: normal;
            font-style: normal;
            text-decoration: none
        }

        div.cls_008 {
            font-family: Arial, serif;
            font-size: 8.2px;
            color: rgb(0, 0, 255);
            font-weight: normal;
            font-style: normal;
            text-decoration: none
        }
    </style>
    {{-- <script type="text/javascript" src="adaa5fcc-4d79-11ea-a5fd-0cc47a792c0a_id_adaa5fcc-4d79-11ea-a5fd-0cc47a792c0a_files/wz_jsgraphics.js"></script> --}}
</head>

<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">

    <div style="position:absolute;left:50%;margin-left:-306px;top:0px;width:612px;height:792px;border-style:outset;overflow:hidden">
        <div style="position:absolute;left:0px;top:0px">
        <img src="pdf/background1.jpg" width=612 height=792></div>
        <div style="position:absolute;left:472.00px;top:20.30px" class="cls_003"><span class="cls_003">LOA No :</span></div>
        <div style="position:absolute;left:523.50px;top:18.75px" class="cls_003"><span class="cls_003">32166</span></div>
        <div style="position:absolute;left:227.80px;top:21.75px" class="cls_002"><span class="cls_002">{{$data[0]->CompanyName}}</span></div>
        <div style="position:absolute;left:225.30px;top:37.80px" class="cls_003"><span class="cls_003">Tel : {{$data[0]->PhoneNo}}</span></div>
        <div style="position:absolute;left:312.00px;top:37.80px" class="cls_003"><span class="cls_003">Fax : {{$data[0]->PhoneNumber}}</span></div>
        <div style="position:absolute;left:472.00px;top:38.55px" class="cls_003"><span class="cls_003">Finance Ref No :</span></div>
        <div style="position:absolute;left:271.10px;top:50.00px" class="cls_004"><span class="cls_004">Letter of Authorization</span></div>
        <div style="position:absolute;left:438.39px;top:53.30px" class="cls_003"><span class="cls_003">{{$data[0]->InvoiceCode}}</span></div>
        <div style="position:absolute;left:492.35px;top:65.30px" class="cls_003"><span class="cls_003">{{$data[0]->InvoiceCodeReff}}</span></div>
        <div style="position:absolute;left:102.00px;top:82.70px" class="cls_003"><span class="cls_003">{{date('d F Y', strtotime($data[0]->Date))}}</span></div>
        <div style="position:absolute;left:333.75px;top:84.25px" class="cls_003"><span class="cls_003">{{$data[0]->CodeReff}},</span></div>
        <div style="position:absolute;left:26.25px;top:86.50px" class="cls_003"><span class="cls_003">Date</span></div>
        <div style="position:absolute;left:306.75px;top:87.50px" class="cls_003"><span class="cls_003">Ref</span></div>
        <div style="position:absolute;left:333.75px;top:96.25px" class="cls_003"><span class="cls_003">{{$data[0]->InvoiceCode}}</span></div>
        <div style="position:absolute;left:100.50px;top:103.70px" class="cls_003"><span class="cls_003">{{$data[0]->BusinessPartner}}</span></div>
        <div style="position:absolute;left:26.25px;top:106.75px" class="cls_003"><span class="cls_003">Hotel/Supplier</span></div>
        <div style="position:absolute;left:101.00px;top:121.95px" class="cls_003"><span class="cls_003">{{$data[0]->AccountName}}</span></div>
        <div style="position:absolute;left:26.25px;top:124.75px" class="cls_003"><span class="cls_003">Guest Name</span></div>
        <div style="position:absolute;left:26.25px;top:142.00px" class="cls_003"><span class="cls_003">To</span></div>
        <div style="position:absolute;left:306.75px;top:142.00px" class="cls_003"><span class="cls_003">Code</span></div>
        <div style="position:absolute;left:336.75px;top:141.20px" class="cls_003"><span class="cls_003">DELUXE,PREMIER,SUPERIOR DELUXE</span></div>
        <div style="position:absolute;left:27.00px;top:158.50px" class="cls_003"><span class="cls_003">Tour Code</span></div>
        <div style="position:absolute;left:99.00px;top:166.45px" class="cls_003"><span class="cls_003">ACE191214SM,IDF191227AKS,PHF191222GTP,PHF191223FUT,VNF191130VHC</span></div>
        <div style="position:absolute;left:26.25px;top:203.45px" class="cls_004"><span class="cls_004">Attention to Reservations / Sales / Credit Department</span></div>
        <div style="position:absolute;left:26.25px;top:219.50px" class="cls_003"><span class="cls_003">Dear Sir / Madam</span></div>
        <div style="position:absolute;left:26.25px;top:234.95px" class="cls_004"><span class="cls_004">AUTHORISATION OF PAYMENT FOR ROOM CHARGES FOR PERIOD</span></div>
        <div style="position:absolute;left:26.25px;top:248.00px" class="cls_003"><span class="cls_003">FROM</span></div>
        <div style="position:absolute;left:303.75px;top:248.00px" class="cls_003"><span class="cls_003">To</span></div>
        <div style="position:absolute;left:27.25px;top:263.50px" class="cls_003"><span class="cls_003">Detail</span></div>
        <div style="position:absolute;left:26.25px;top:297.25px" class="cls_003"><span class="cls_003">I hereby authorize the hotel to charge the room charges as per our confirmed rate to the below credit card account.</span></div>
        <div style="position:absolute;left:161.25px;top:311.45px" class="cls_004"><span class="cls_004">{{$data[0]->AccountName}}</span></div>
        <div style="position:absolute;left:26.25px;top:315.50px" class="cls_003"><span class="cls_003">Name of the Card Holder</span></div>
        <div style="position:absolute;left:162.75px;top:331.95px" class="cls_003"><span class="cls_003">{{$data[0]->CardNumber}}</span></div>
        <div style="position:absolute;left:26.25px;top:335.00px" class="cls_003"><span class="cls_003">Credit Card Number</span></div>
        <div style="position:absolute;left:165.00px;top:350.70px" class="cls_003"><span class="cls_003">{{date('m', strtotime($data[0]->DueDateCard))}}</span></div>
        <div style="position:absolute;left:284.25px;top:350.70px" class="cls_003"><span class="cls_003">{{date('Y', strtotime($data[0]->DueDateCard))}}</span></div>
        <div style="position:absolute;left:26.25px;top:353.50px" class="cls_003"><span class="cls_003">Credit Card Expire Date</span></div>
        <div style="position:absolute;left:228.00px;top:353.50px" class="cls_003"><span class="cls_003">(Month)</span></div>
        <div style="position:absolute;left:354.75px;top:353.50px" class="cls_003"><span class="cls_003">(Year)</span></div>
        <div style="position:absolute;left:26.25px;top:371.75px" class="cls_003"><span class="cls_003">This serves to confirm that the authorized amount charged will be :-</span></div>
        <div style="position:absolute;left:26.25px;top:388.25px" class="cls_003"><span class="cls_003">Singapore Dollars</span></div>
        <div style="position:absolute;left:133.50px;top:387.45px" class="cls_004"><span class="cls_004">{{$data[0]->CurrencyCode}}</span></div>
        <div style="position:absolute;left:169.68px;top:386.45px" class="cls_004"><span class="cls_004">{{$data[0]->TotalAmount}}</span></div>
        <div style="position:absolute;left:26.25px;top:406.00px" class="cls_003"><span class="cls_003">For your kind information, the prepayment balance offset details with your esteemed corporate as per (date according to LOA</span></div>
        <div style="position:absolute;left:26.25px;top:418.00px" class="cls_003"><span class="cls_003">issue date)</span></div>
        <div style="position:absolute;left:26.25px;top:452.25px" class="cls_003"><span class="cls_003">Yours Faithfully</span></div>
        <div style="position:absolute;left:147.00px;top:452.30px" class="cls_005"><span class="cls_005">Remarks</span><span class="cls_006"> PLEASE POST THE ORIGINAL "TAX-INVOICE/SOFTCOPY " TO OUR OFFICE ADDRESS :</span></div>
        <div style="position:absolute;left:147.00px;top:468.20px" class="cls_007"><span class="cls_007">133, New Bridge Road, #19-03/04/05, Chinatown Point, Singapore-059413</span></div>
        <div style="position:absolute;left:147.00px;top:482.45px" class="cls_007"><span class="cls_007">E-Mail : account@acetours.sg</span></div>
        <div style="position:absolute;left:147.00px;top:497.35px" class="cls_008"><span class="cls_008">and reply our payment email with the soft copy of the Tax-Invoice</span></div>
        <div style="position:absolute;left:147.00px;top:510.10px" class="cls_008"><span class="cls_008">(For GST purpose) (As we need to show the hard copy of the Tax-Invoice to Government</span></div>
        <div style="position:absolute;left:147.00px;top:522.85px" class="cls_008"><span class="cls_008">IRAS if they requested)</span></div>
        <div style="position:absolute;left:20px;top:537px" class="cls_008"><span class="cls_008">
            <span><img src='{{$data[0]->Image1}}'  width="200" height="80"></span>
        </div>
        <div style="position:absolute;left:210px;top:532px" class="cls_008"><span class="cls_008">
            <span><img src='{{$data[0]->Image2}}'  width="200" height="80"></span>
        </div>



    </div>

</body>

</html>