@extends('AdminApi\Report::html.layouts.app')
@section('header')
    @include('AdminApi\Report::html.partials.header')
@show
<main>
    <div class="table" >
        <table>
            <thead>
            <tr> {{--width:675px--}}
                <th class="firstcol" style="width:30px">NO</th>
                <th style="width:50px">TRANS. NO</th>
                <th style="width:10px">DATE</th>
                <th style="width:150px">CUSTOMER</th>
                <th style="width:50px">DISC.</th>
                <th style="width:100px">TOTAL PRICE</th>
                <th style="width:100px">PAYMENT</th>
                <th class="lastcol" style="width:30px">AMOUNT</th>
            </tr>
            </thead>   
            <tbody>
            @php $count = 1; $total=0; $sumtotal=0;  @endphp
            @foreach($data as $row)
                <tr>
                    <td class="firstcol">{{$count}}</td>
                    <td>
                        {{$row->Code}}
                        @if ($row->StatusName === 'CANCELLED')
                        ({{$row->StatusName}})
                        @endif
                    </td>
                    <td align="left">{{date("j/n D", strtotime($row->Date))}}</td>
                    <td align="left">
                        {{$row->Customer}}
                        @if ($row->Employee != null)
                        <br>{{$row->Employee}}
                        @endif
                        @if ($row->TableRoom != null)
                        <br>{{$row->TableRoom}}
                        @endif
                    </td>
                    <td align="right">{{number_format($row->DiscountAmount ,2,',','.')}}</td>
                    <td align="right">{{number_format($row->TotalAmount ,2,',','.')}}</td> 
                    <td align="left">
                        @if ($row->PaymentMethod1 != null)
                        {{$row->PaymentMethod1}}<br>
                        @endif
                        @if ($row->PaymentMethod2 != null)
                        {{$row->PaymentMethod2}}<br>
                        @endif
                        @if ($row->PaymentMethod3 != null)
                        {{$row->PaymentMethod3}}<br>
                        @endif
                        @if ($row->PaymentAmount4 != null)
                        {{$row->PaymentAmount4}}<br>
                        @endif
                        @if ($row->PaymentMethod5 != null)
                        {{$row->PaymentMethod5}}
                        @endif
                        @if ($row->PaymentMethodChanges != null)
                        {{$row->PaymentMethodChanges}}
                        @endif
                    </td>
                    <td align="right">
                        @if ($row->PaymentAmount1 != null)
                        {{number_format($row->PaymentAmount1 ,2,',','.')}}<br>
                        @endif
                        @if ($row->PaymentAmount2 != null)
                        {{number_format($row->PaymentAmount2 ,2,',','.')}}<br>
                        @endif
                        @if ($row->PaymentAmount3 != null)
                        {{number_format($row->PaymentAmount3 ,2,',','.')}}<br>
                        @endif
                        @if ($row->PaymentAmount4 != null)
                        {{number_format($row->PaymentAmount4 ,2,',','.')}}<br>
                        @endif
                        @if ($row->PaymentAmount5 != null)
                        {{number_format($row->PaymentAmount5 ,2,',','.')}}
                        @endif
                        @if ($row->PaymentAmountChanges != null)
                        {{number_format($row->PaymentAmountChanges ,2,',','.')}}
                        @endif
                    </td>
                    @php
                        $sumtotal = $sumtotal + ($row->StatusName !== 'CANCELLED'? $row->TotalAmount : 0);
                    @endphp
                </tr>
                @php $count++; @endphp
            @endforeach
            <tr>
                <td colspan="5" align="right"><strong>TOTAL</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong></strong></td>
                <td class="total" align="right"><strong></strong></td>
                </tr>
            </tbody>
        </table>
        <hr />
        <table>
            <thead>
            <tr> {{--width:675px--}}
                <th class="firstcol" style="width:30px">NO</th>
                <th style="width:30px">TYPE</th>
                <th style="width:100px">PAYMENT</th>
                <th style="width:200px">DESCRIPTION</th>
                <th style="width:50px">AMOUNT</th>
                <th class="lastcol" style="width:50px">AMOUNT BASE</th>
            </tr>
            </thead>   
            <tbody>
            @php $count = 1; $total=0; $sumtotal=0;  @endphp
            @foreach($dataamount as $row)
                <tr>
                <td class="firstcol">{{$count}}</td>
                <td>{{$row->Type}}</td>
                <td align="left">{{$row->PaymentMethod}}</td>
                <td align="left">{{$row->Note}}</td>
                <td align="right">{{number_format($row->Amount ,2,',','.')}}</td>
                <td align="right">{{number_format($row->AmountBase ,2,',','.')}}</td> 
                @php
                    $sumtotal = $sumtotal + $row->AmountBase;
                @endphp
                </tr>
                @php $count++; @endphp
            @endforeach
            <tr>
                <td colspan="4" align="right"><strong>TOTAL</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div style="padding: 13px 20px 13px 20px;">
    <div style="font-size: 14px; color: #858585;"></div>
    </div>
</main>