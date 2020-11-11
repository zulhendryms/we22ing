<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['Qty','Amount','DetailSubtotal', 'DetailDiscount', 'TotalAmount'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Comp'];
    $fields[] = ['c'=>'Date', 'f'=>'Medium'];
    $fields[] = ['c'=>'Warehouse'];
    $fields[] = ['c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['c'=>'Qty', 't'=>'int', 'n'=>'QTY'];
    $fields[] = ['c'=>'Amount', 't'=>'double', 'n'=>'Amount'];
    $fields[] = ['c'=>'DetailSubtotal', 't'=>'double', 'n'=>'Subtotal'];
    $fields[] = ['c'=>'DetailDiscount', 't'=>'double', 'n'=>'Discount'];
    $fields[] = ['c'=>'TotalAmount', 't'=>'double', 'n'=>'Total'];
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
              if ($group != '') echo reportTotal($totalgroup, 4, $group);
              echo "<tr><td colspan='13' class='group'><strong>{$row->GroupName}</strong></td></tr>";
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

      @php echo reportTotal($totalgroup,4, $group); @endphp
      @php echo reportTotal($totalall, 4); @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>