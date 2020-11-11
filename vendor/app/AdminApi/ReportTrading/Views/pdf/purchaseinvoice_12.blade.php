<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group1=""; $group2="";
    $fieldSum = ['Amount', 'Amount1'];
    $totalgroup1 = reportVarCreate($fieldSum);
    $totalgroup2 = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Code'];
    $fields[] = ['c'=>'Date', 'f'=>'Medium'];
    $fields[] = ['c'=>'Accaunt', 'n'=>'Accaunt From'];
    $fields[] = ['c'=>'CurrencyCode', 'n'=>'Cur From'];
    $fields[] = ['c'=>'Amount', 't'=>'double', 'n'=>'Amount'];
    $fields[] = ['c'=>'Amount1', 't'=>'double', 'n'=>'Amount Base'];

    $fieldsDtl = []; //c = code/field, f = format, t = type, n = name/title
    $fieldsDtl[] = ['c'=>'', 'n'=>'', 'm'=>2];
    $fieldsDtl[] = ['c'=>'Accaunt', 'n'=>'Accaunt To'];
    $fieldsDtl[] = ['c'=>'CurrencyCode', 'n'=>'Cur To'];
    $fieldsDtl[] = ['c'=>'Amount', 't'=>'double', 'n'=>'Amount'];
    $fieldsDtl[] = ['c'=>'Amount1', 't'=>'double', 'n'=>'Amount Base'];


  
  @endphp {{-- DECLARATION --}}

</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      <thead>
        @php 
          echo reportHeader($fields, true);
          echo reportHeader($fieldsDtl, true);
          // echo reportHeader($fieldsz, true);
        @endphp
      </thead>
      <tbody> 
        @foreach($data as $row)  
          @php 
      if ($group1 != $row->Code) { // GROUP 1 //
              if ($group1 != '') echo reportTotal($totalgroup1, 4, $group1);
              // echo "<tr><td colspan='10' class='group'><strong>{$row->Code}</strong></td></tr>";
              $group1 = $row->Code; 
              $totalgroup1 = reportVarReset($totalgroup1); 
            } // GROUP 1 //
          
         
            if ($group2 != $row->Code) { // GROUP 2 //
              // $row->Amount += $row->Amount;
              $totalgroup1 = reportVarAddValue($totalgroup1, $row);
              $totalgroup2 = reportVarAddValue($totalgroup2, $row);
              $totalall = reportVarAddValue($totalall, $row);
              
              echo reportTableFields($row, $fields);
                            
              // $group2 = $row->Code; 
              $totalgroup2 = reportVarReset($totalgroup2); 
            } // GROUP 2 //

            // DETAIL //
            echo reportTableFields($row, $fieldsDtl);
            // DETAIL //

          @endphp
        @endforeach
      </tbody>

      @php 
        // echo reportTotal($totalgroup1, 4, $group1);
        echo reportTotal($totalall, 4); 
      @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>