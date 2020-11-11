<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['TotalAmount', 'DiscountAmount', 'SubtotalAmount'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['w'=>40, 'c'=>'Comp'];
    $fields[] = ['w'=>40, 'c'=>'Code'];
    $fields[] = ['w'=>30, 'c'=>'Date', 'f'=>'Short'];
    $fields[] = ['w'=>30, 'c'=>'BusinessPartner', 'n'=>'B. PARTNER'];
    $fields[] = ['w'=>30, 'c'=>'PaymentMethod', 'n'=>'PAYMENT'];
    $fields[] = ['w'=>30, 'c'=>'Warehouse'];
    $fields[] = ['w'=>30, 'c'=>'TableName', 'n'=>'TABLE'];
    $fields[] = ['w'=>30, 'c'=>'Cashier'];
    $fields[] = ['w'=>30, 'c'=>'Quantity', 't'=>'int'];
    $fields[] = ['w'=>30, 'c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['w'=>100, 'c'=>'SubtotalAmount', 't'=>'double', 'n'=>'SUBTOTAL'];
    $fields[] = ['w'=>100, 'c'=>'DiscountAmount', 't'=>'double', 'n'=>'DISCOUNT'];
    $fields[] = ['w'=>100, 'c'=>'TotalAmount', 't'=>'double', 'n'=>'TOTAL'];
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
              if ($group != '') echo reportTotal($totalgroup,10, $group);
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

      @php echo reportTotal($totalgroup,10, $group); @endphp
      @php echo reportTotal($totalall,10); @endphp

    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>