<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['DetailSubtotal', 'DetailDiscount', 'SubtotalAmount', 'DiscountAmount', 'TotalAmount'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Comp'];
    $fields[] = ['c'=>'Code'];
    $fields[] = ['c'=>'Date', 'f'=>'Short'];
    $fields[] = ['c'=>'BusinessPartner', 'n'=>'B. PARTNER'];
    $fields[] = ['c'=>'PaymentMethod', 'n'=>'PAYMENT'];
    $fields[] = ['c'=>'Warehouse'];
    $fields[] = ['c'=>'Cashier'];
    $fields[] = ['c'=>'EmployeeName', 'Sales'];
    $fields[] = ['c'=>'Quantity', 't'=>'int', 'n'=>'QTY'];
    $fields[] = ['c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['c'=>'SubtotalAmount', 't'=>'double', 'n'=>'SUBTOTAL'];
    $fields[] = ['c'=>'DiscountAmount', 't'=>'double', 'n'=>'DISCOUNT'];
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

            if ($group != $row->TableName.$row->Oid) { // GROUP 1 //
              if ($group != '') echo reportTotal($totalgroup, 8, $group);
              echo "<tr><td colspan='13' class='group'><strong>{$row->TableName}</strong></td></tr>";
              $group = $row->TableName.$row->Oid; 
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

      @php echo reportTotal($totalgroup,8, $group); @endphp
      @php echo reportTotal($totalall, 8); @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>