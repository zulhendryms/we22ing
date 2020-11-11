@extends('Core\Admin::master')

@section('content')



<div class="container">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Head 1</th>
                <th>Head 2</th>
                <th>Head 3</th>
                <th>Head 4</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 4; $i++)
                <tr>
                    @for($j = 0; $j < 4; $j++)
                        <td>Data {{$j + 1}}</td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>

@endsection