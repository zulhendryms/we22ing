<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['Qty', 'Subtotal', 'Discount','TotalAmount'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Date', 'f'=>'Medium'];
    $fields[] = ['c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['c'=>'Qty', 't'=>'int'];
    $fields[] = ['c'=>'Subtotal', 't'=>'double', 'n'=>'SUBTOTAL'];
    $fields[] = ['c'=>'Discount', 't'=>'double', 'n'=>'DISCOUNT'];
    // $fields[] = ['c'=>'DiscountAmount', 't'=>'double', 'n'=>'DISCOUNT'];
    $fields[] = ['c'=>'TotalAmount', 't'=>'double', 'n'=>'TOTAL'];
  @endphp {{-- DECLARATION --}}

</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      @php echo reportHeader($fields) @endphp
      <tbody> 
        @foreach($data as $row)      
          @php 

            if ($group != $row->GroupName) { // GROUP 1 //
              if ($group != '') echo reportTotal($totalgroup, 2, $group);
              echo "<tr><td colspan='12' class='group'><strong>{$row->GroupName}</strong></td></tr>";
              $group = $row->GroupName; 
              $totalgroup = reportVarReset($totalgroup); 
            } // GROUP 1 //
          
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