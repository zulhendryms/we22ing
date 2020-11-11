<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['Qty','DetailSubtotal', 'DetailDiscount', 'SubtotalAmount', 'DiscountAmount', 'TotalAmount'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['w'=>20, 'c'=>'Comp'];
    $fields[] = ['w'=>10, 'c'=>'Date'];
    $fields[] = ['w'=>10, 'c'=>'Qty', 't'=>'int'];
    $fields[] = ['w'=>10, 'c'=>'CurrencyCode', 'n'=>'Cur'];
    $fields[] = ['c'=>'DetailSubtotal', 't'=>'double', 'n'=>'SUBTOTAL DTL'];
    $fields[] = ['c'=>'DetailDiscount', 't'=>'double', 'n'=>'DISC DETAIL'];
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
  
              if ($group != $row->GroupName) { // GROUP 1 //
                if ($group != '') echo reportTotal($totalgroup, 3, $group);
                echo "<tr><td colspan='9' class='group'><strong>{$row->GroupName}</strong></td></tr>";
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
  
        @php echo reportTotal($totalgroup,3, $group); @endphp
        @php echo reportTotal($totalall, 3); @endphp
        
      </table>
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>