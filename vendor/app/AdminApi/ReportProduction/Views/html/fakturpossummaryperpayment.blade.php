@extends('AdminApi\Report::html.layouts.app')
@section('header')
    @include('AdminApi\Report::html.partials.header')
@show
<main>
  <div class="table">
    <table>
        <thead>
          <tr> {{--width:675px--}}
            <th class="firstcol" style="width:130px">PAYMENT</th>
            <th style="width:10px">COUNT</th>
            <th style="width:10px">CUR</th>
            <th style="width:50px">AMOUNT</th>
            <th style="width:30px">RATE</th>
            <th class="lastcol" style="width:50px">AMOUNT BASE</th>
          </tr>
        </thead>    
        <tbody>
            @php $count = 1; $rate=0; $sumbase=0; $sumrate=0; $sumtotal=0; $group=""; @endphp
            @foreach($data as $row)
              @if ($group != $row->Currency)
                @if ($group !="")
                  <tr class="total">
                    <td colspan="3" class="total" align="right"><strong>Total</strong></td>
                    <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
                    <td class="total" align="right"><strong>{{number_format($sumbase / $sumtotal ,2,',','.')}}</strong></td>
                    <td class="total" align="right"><strong>{{number_format($sumbase ,2,',','.')}}</strong></td>
                  </tr>
                @endif
                <tr class="currency">
                  <td colspan="6" class="group" >{{$row->Currency}}</td>
                </tr>
                @php $group = $row->Currency;  $sumbase=0; $sumrate=0; $sumtotal=0; @endphp
              @endif
              <tr>
                <td class="firstcol">{{$row->Item}}</td>
                <td align="right">{{$row->CountBill}}</td>
                <td align="left">{{$row->Currency}}</td>
                <td align="right">{{number_format($row->PaymentAmount ,2,',','.')}}</td>
                <td align="right">{{number_format($row->PaymentRate ,2,',','.')}}</td>
                <td align="right">{{number_format($row->PaymentAmountBase ,2,',','.')}}</td>
                @php
                  $rate = $rate + ($row->PaymentAmountBase / $row->PaymentAmount);
                  $sumtotal = $sumtotal + $row->PaymentAmount;
                  $sumrate = $sumrate + $row->PaymentRate;
                  $sumbase = $sumbase + $row->PaymentAmountBase;
                @endphp
              </tr>
              @php $count++; @endphp
            @endforeach
            <tr class="finaltotal">
                <td colspan="3" class="total" align="right"><strong>Total</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumbase / $sumtotal ,2,',','.')}}</strong></td>
                <td class="total" align="right"><strong>{{number_format($sumbase ,2,',','.')}}</strong></td>
              </tr>
        </tbody>
    </table>
  </div>
    <div style="padding: 13px 20px 13px 20px;">
    <div style="font-size: 14px; color: #858585;"></div>
    </div>
</main>