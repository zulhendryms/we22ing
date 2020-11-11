<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group="";
    $fieldSum = ['Qty', 'Amount', 'Total'];
    $totalgroup = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Comp'];
    $fields[] = ['c'=>'Date', 'f'=>'Short'];
    $fields[] = ['c'=>'Code', 'n'=>'Code'];
    $fields[] = ['c'=>'Note', ];
    $fields[] = ['c'=>'Item', ];
    $fields[] = ['c'=>'Qty', 't'=>'int'];
    $fields[] = ['c'=>'Amount', 't'=>'double', 'n'=>'Amount'];
    $fields[] = ['c'=>'Total', 't'=>'double', 'n'=>'Total'];
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
              if ($group != '') echo reportTotal($totalgroup, 5, $group);
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
      @php echo reportTotal($totalall, 5); @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>