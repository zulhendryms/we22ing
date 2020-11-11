<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['DetailSubtotal', 'DetailDiscount', 'SubtotalAmount', 'DiscountAmount', 'TotalAmount','TotalSessionAmount','TotalAmountAndSession'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['w'=>30, 'c'=>'Comp'];
    $fields[] = ['w'=>30, 'c'=>'Date', 'f'=>'Short'];
    $fields[] = ['w'=>30, 'c'=>'Qty', 't'=>'int'];
    $fields[] = ['w'=>30, 'c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['w'=>100, 'c'=>'DetailSubtotal', 't'=>'double', 'n'=>'SUBTOTAL DTL'];
    $fields[] = ['w'=>100, 'c'=>'DetailDiscount', 't'=>'double', 'n'=>'DISC DETAIL'];
    $fields[] = ['w'=>100, 'c'=>'SubtotalAmount', 't'=>'double', 'n'=>'SUBTOTAL'];
    $fields[] = ['w'=>100, 'c'=>'DiscountAmount', 't'=>'double', 'n'=>'DISCOUNT'];
    $fields[] = ['w'=>100, 'c'=>'TotalAmount', 't'=>'double', 'n'=>'TOTAL'];
    $fields[] = ['w'=>100, 'c'=>'TotalSessionAmount', 't'=>'double', 'n'=>'CASH TRANS.'];
    $fields[] = ['w'=>100, 'c'=>'TotalAmountAndSession', 't'=>'double', 'n'=>'FINAL AMOUNT'];
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
              echo "<tr><td colspan='11' class='group'><strong>{$row->GroupName}</strong></td></tr>";
              $group = $row->GroupName; 
              $totalgroup = reportVarReset($totalgroup); 
            } // GROUP 1 //
          
            // DETAIL //
            $row->TotalAmountAndSession = $row->TotalAmount + $row->TotalSessionAmount;
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