<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$reporttitle}}</title>

  @php //DECLARATION
    echo reportStyle1(); 
    $group1=""; $group2="";
    $fieldSum = ['Qty', 'DetailTotal'];
    $totalgroup1 = reportVarCreate($fieldSum);
    $totalgroup2 = reportVarCreate($fieldSum);
    $totalall = reportVarCreate($fieldSum);
    $fields = []; //c = code/field, f = format, t = type, n = name/title
    $fields[] = ['c'=>'Comp'];
    $fields[] = ['c'=>'Code'];
    $fields[] = ['c'=>'Date', 'f'=>'Medium'];
    $fields[] = ['c'=>'BusinessPartner', 'n'=>'B. PARTNER'];
    $fields[] = ['c'=>'Qty', 't'=>'int', 'n'=>'Qty'];
    $fields[] = ['c'=>'DetailTotal', 't'=>'double', 'n'=>'TOTAL'];

  @endphp {{-- DECLARATION --}}

</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>
      
        @php 
          echo reportHeader($fields, true);
        @endphp
    
      <tbody> 
        @foreach($data as $row)  
          @php 

            if ($group1 != $row->ItemGroup) { // GROUP 1 //
              if ($group1 != '') echo reportTotal($totalgroup1, 4, $group1);
              echo "<tr><td colspan='11' class='group'><strong>{$row->ItemGroup}</strong></td></tr>";
              $group1 = $row->ItemGroup; 
              $totalgroup1 = reportVarReset($totalgroup1); 
            } // GROUP 1 //
          
            if ($group2 != $row->Code) { // GROUP 2 //
            //   $row->DetailTotal = $row->DetailTotal - $row->DiscountAmount;
              $totalgroup1 = reportVarAddValue($totalgroup1, $row);
              $totalgroup2 = reportVarAddValue($totalgroup2, $row);
              $totalall = reportVarAddValue($totalall, $row);
              
              echo reportTableFields($row, $fields);
            //   if ($row->Note) echo "<tr>
            //                 <td class='firstcol'></td><td class='lastcol' colspan='9'><strong>NOTE:  {{$row->Note}}</strong></td>
            //                 </tr>";
              
              $group2 = $row->Code; 
              $totalgroup2 = reportVarReset($totalgroup2); 
            } // GROUP 2 //


          @endphp
        @endforeach
      </tbody>

      @php 
        echo reportTotal($totalgroup1, 4, $group1);
        echo reportTotal($totalall, 4); 
      @endphp
      
    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>