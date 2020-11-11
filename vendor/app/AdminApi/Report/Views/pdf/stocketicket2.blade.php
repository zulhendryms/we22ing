<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['Stock','CostPrice', 'TotalCost'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['w'=>70, 'c'=>'Comp'];
    $fields[] = ['w'=>200, 'c'=>'Code'];
    $fields[] = ['w'=>200, 'c'=>'Name'];
    // $fields[] = ['w'=>50, 'c'=>'Type'];
    $fields[] = ['w'=>50, 'c'=>'DateExpiry', 'n'=>'Nearest<br>Expiry'];
    $fields[] = ['w'=>100, 'c'=>'Uploaded', 'n'=>'Last<br>Uploaded'];
    $fields[] = ['w'=>100, 'c'=>'Stock','t'=>'double'];
    $fields[] = ['w'=>100, 'c'=>'CostPrice','t'=>'double', 'n'=>'Unit Price'];
    $fields[] = ['w'=>100, 'c'=>'TotalCost','t'=>'double', 'n'=>'Total Cost'];
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
              if ($group != '') echo reportTotal($totalgroup,5, $group);
              echo "<tr><td colspan='8' class='group'><strong>{$row->GroupName}</strong></td></tr>";
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

      @php echo reportTotal($totalgroup,5, $group); @endphp
      @php echo reportTotal($totalall,5); @endphp

    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>