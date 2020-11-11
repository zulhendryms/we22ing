<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group1=""; $group2="";
    $fieldSum = ['Qty'];
    $totalgroup1 = reportVarCreate($fieldSum);
    $totalgroup2 = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Comp'];
    $fields[] = ['c'=>'Code'];
    $fields[] = ['c'=>'Date', 'f'=>'Medium'];
    $fields[] = ['c'=>'BusinessPartner', 'n'=>'B. PARTNER'];
    $fields[] = ['c'=>'CurrencyCode', 'n'=>'Cur', 'm'=>2];

    $fieldsDtl = []; //c = code/field, f = format, t = type, n = name/title
    $fieldsDtl[] = ['c'=>'', 'n'=>'', 'm'=>1];
    $fieldsDtl[] = ['c'=>'ItemName', 'n'=>'Item Name', 'm'=>4];
    $fieldsDtl[] = ['c'=>'Qty', 't'=>'int', 'n'=>'Qty'];


  
  @endphp {{-- DECLARATION --}}

</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      
        @php 
          echo reportHeader2($fields, $fieldsDtl, true);
        @endphp
    
      <tbody> 
        @foreach($data as $row)  
          @php 

            if ($group1 != $row->Warehouse) { // GROUP 1 //
              if ($group1 != '') echo reportTotal($totalgroup1, 5, $group1);
              echo "<tr><td colspan='10' class='group'><strong>{$row->Warehouse}</strong></td></tr>";
              $group1 = $row->Warehouse; 
              $totalgroup1 = reportVarReset($totalgroup1); 
            } // GROUP 1 //
          
            if ($group2 != $row->Code) { // GROUP 2 //
              $totalgroup1 = reportVarAddValue($totalgroup1, $row);
              $totalgroup2 = reportVarAddValue($totalgroup2, $row);
              $totalall = reportVarAddValue($totalall, $row);
              
              echo reportTableFields($row, $fields);
              if ($row->Note) echo "<tr>
                            <td class='firstcol'></td><td class='lastcol' colspan='10'><strong>NOTE:  {{$row->Note}}</strong></td>
                            </tr>";
              
              $group2 = $row->Code; 
              $totalgroup2 = reportVarReset($totalgroup2); 
            } // GROUP 2 //

            // DETAIL //
            echo reportTableFields($row, $fieldsDtl);
            // DETAIL //

          @endphp
        @endforeach
      </tbody>

      @php 
        echo reportTotal($totalgroup1, 5, $group1);
        echo reportTotal($totalall, 5); 
      @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>