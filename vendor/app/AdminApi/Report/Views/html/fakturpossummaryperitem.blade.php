@extends('AdminApi\Report::html.layouts.app')
@if  (sizeof($data) >0) {
    @section('header')
        @include('AdminApi\Report::html.partials.header')
    @show
@else
    <p style="text-align:center;"> <strong>NO DATA FOR YOUR HEADER</strong></p>
@endif

<main>
    <div class="table">
    <table>
        <thead>
            <tr> {{--width:675px--}}
              <th class="firstcol" style="width:20px">NO</th>
              <th style="width:250px">ITEM</th>
              <th style="width:10px">QTY</th>
              <th style="width:50px">FLAT PRICE</th>
              <th class="lastcol" style="width:50px">TOTAL PRICE</th>
            </tr>
        </thead>    
        <tbody>
            @php $count = 1; $flatprice=0; $total=0; $sumtotal=0; $sumqty=0; $sumdiscount=0; @endphp
            @foreach($data as $row)
              @if ($row->StatusName !== 'CANCELLED') 
                <tr>
                  <td class="firstcol">{{$count}}</td>
                  <td>{{$row->Item}}</td>
                  <td align="left">{{$row->Quantity}}</td>
                  <td align="right">{{number_format($row->Amount ,2,',','.')}}</td>
                  <td align="right">{{number_format($row->TotalAmount ,2,',','.')}}</td>
                  @php 
                    $flatprice = $flatprice + ($row->TotalAmount + $row->Quantity); 
                    $sumqty = $sumqty + $row->Quantity;
                    $sumtotal = $sumtotal + ($row->StatusName !== 'CANCELLED'? $row->TotalAmount : 0);
                  @endphp
                </tr>
              @endif
              @php $count++; @endphp
            @endforeach
            <tr>
                <td colspan="2" align="right"><strong>TOTAL</strong></td>
                <td class="total" align="right"><strong>{{$sumqty}}</strong></td>
                <td class="total" align="right"><strong></strong></td>
                <td class="total" align="right"><strong>{{number_format($sumtotal ,2,',','.')}}</strong></td>
              </tr>
              <tr>
                <td colspan="4" align="right"><strong>DISCOUNT</strong></td>
                @if (sizeof($discount) >0) 
                  <td class="total" align="right"><strong>{{number_format($discount[0]->Discount ,2,',','.')}}</strong></td>
                @else
                  <td class="total" align="right"><strong>No Data</strong></td>
                @endif
              </tr>
              <tr>
                <td colspan="4" align="right"><strong>GRAND TOTAL</strong></td>
                @if (sizeof($discount) >0) 
                  <td class="total" align="right"><strong>{{number_format($sumtotal-$discount[0]->Discount,2,',','.')}}</strong></td>
                @else
                  <td class="total" align="right"><strong>No Data</strong></td>
                @endif
              </tr>
        </tbody>
    </table>
    </div>
    <div style="padding: 13px 20px 13px 20px;">
    <div style="font-size: 14px; color: #858585;"></div>
    </div>
</main>