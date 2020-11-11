<!DOCTYPE html>
<html>
  @php
      // $user = Auth::user();
  @endphp
  <header>
    <div style="text-align: left;">
        <img src="{{$user->CompanyObj->Image}}" width="70" style="float:right;" />
        <div style="font-size: 26px;">Report Outstanding Purchase Invoice</div>
        <strong style="font-size:13px">{{strtoupper($user->CompanyObj->Name)}}</strong><br />
        {{-- <div style="font-size:11px">{{$filter}}</div> --}}
        <div style="clear:both"></div><br />
    </div>
</header>
<head>
  <meta charset="utf-8">
  <title>Report Outstanding Purchase Invoice</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['Amount', 'PaidAmount', 'Sisa'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Supplier', 'n'=>'Supplier'];
    // $fields[] = ['c'=>'Date', 'f'=>'Medium'];
    $fields[] = ['c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['c'=>'Amount', 't'=>'double', 'n'=>'Amount'];
    $fields[] = ['c'=>'PaidAmount', 't'=>'double', 'n'=>'Paid Amount'];
    $fields[] = ['c'=>'Sisa', 't'=>'double', 'n'=>'Balance'];
  @endphp {{-- DECLARATION --}}

</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      @php echo reportHeader($fields) @endphp
      <tbody> 
        @foreach($data as $row)      
          @php 

            // if ($group != $row->Supplier) { // GROUP 1 //
            //   if ($group != '') echo reportTotal($totalgroup, 4, $group);
            //   echo "<tr><td colspan='12' class='group'><strong>{$row->Supplier}</strong></td></tr>";
            //   $group = $row->Supplier; 
            //   $totalgroup = reportVarReset($totalgroup); 
            // } // GROUP 1 //
          
            // DETAIL //
            $totalgroup = reportVarAddValue($totalgroup, $row);
            $totalall = reportVarAddValue($totalall, $row);
            echo reportTableFields($row, $fields)
            // DETAIL //

          @endphp
        @endforeach
      </tbody>

      @php echo reportTotal($totalgroup,2, $group); @endphp
      @php echo reportTotal($totalall, 2); @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>