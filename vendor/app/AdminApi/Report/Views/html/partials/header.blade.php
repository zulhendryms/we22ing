<header>
    <div class="w-25" >
        <img src="{{$data[0]->CompanyLogo}}" width="80px" height="80px">
        <p style="text-align: center;"><strong>{{$data[0]->CompanyName}}</strong></p>
        <p>-</p>
    </div>
    <div class="w-75" >
        <div><strong>{{$headertitle}}</strong></div>
        <div>Session Day : {{$data[0]->Ended}}<div>
        <div>Name Cashier : {{$data[0]->Cashier}} </div>
        <div>Tgl Cetak : {{date("d F Y H:i:s")}} </div>
    </div>
    <!-- <table class="header w-100">
        <tr>
            <td class="w-50">
                <div class="column11" >
                    <p><strong>{{$data[0]->CompanyName}}</strong></p>
                    <p>-</p>
                </div>
            </td>
            <td class="w-50">
                <div class="column22" >
                    <div><strong>{{$headertitle}}</strong></div>
                    <div>Session Day : {{date('d F Y', strtotime($data[0]->Ended))}}<div>
                    <div>Name Cashier : {{$data[0]->Cashier}} </div>
                    <div>Tgl Cetak : {{date("d F Y H:i:s")}} </div>
                </div>
            </td>
        </tr>
        
    </table> -->
    <!-- <table>
        <div class="column2">
            <div><strong>{{$headertitle}}</strong></div>
            <div>Session Day : {{date('d F Y', strtotime($data[0]->Ended))}}<div>
            <div>Name Cashier : {{$data[0]->Cashier}} </div>
            <div>Tgl Cetak : {{date("d F Y H:i:s")}} </div>
        </div>
    </table> -->
</header>