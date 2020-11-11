<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>{{$reporttitle}}</title>

        @php //DECLARATION
          echo reportStyle1(); 
          $group1=""; $group2="";
          $fieldSum = ['PaymentBase'];
          $totalgroup1 = reportVarCreate($fieldSum);
    $totalgroup2 = reportVarCreate($fieldSum);
          $totalall = reportVarCreate($fieldSum);
          $fields = []; //c = code/field, f = format, t = type, n = name/title
          $fields[] = ['c'=>'Comp'];
          $fields[] = ['c'=>'Code'];
          $fields[] = ['c'=>'PaymentMethod', 'n'=>'PAYMENT'];
          $fields[] = ['c'=>'Currency', 'n'=>'Cur'];
          $fields[] = ['c'=>'PaymentAmount', 't'=>'double', 'n'=>'AMOUNT'];
          $fields[] = ['c'=>'PaymentBase', 't'=>'double', 'n'=>'PAYMENT BASE'];
        @endphp {{-- DECLARATION --}}

    </head>
    <body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
        <main>
            <table>
                @php echo reportHeader($fields); @endphp
                <tbody>
                    @foreach($data as $row) 
                        @php 
            
                        if ($group1 != $row->Type) { // GROUP 1 //                     
                            if ($group2) { // TOTAL GROUP 2 - muncul sewaktu reset ke grup 1 //
                                echo reportTotal($totalgroup2, 5, $group2);
                                $totalgroup2 = reportVarReset($totalgroup2); 
                            } // GROUP 2 //

                            if ($group1 && $totalgroup1['PaymentBase'] !== 0) { // TOTAL FOR GROUP 1 - muncul sewaktu reset ke grup 1 //
                                echo reportTotal($totalgroup1, 5, $group1);
                                $totalgroup1 = reportVarReset($totalgroup1); 
                            } // GROUP 1 //

                            echo "<tr><td colspan='6' class='group'><strong>{$row->Type}</strong></td></tr>";
                            $group1 = $row->Type;
                        } // GROUP 1 //
            
                        if ($group2 != $row->Code) { // GROUP 2 //                     
                            if ($group2 && $totalgroup2['PaymentBase'] !== 0) { // TOTAL GROUP 2 - muncul sewaktu reset grup 2 //
                                echo reportTotal($totalgroup2, 5, $group2);
                                $totalgroup2 = reportVarReset($totalgroup2); 
                            } // GROUP 2 //

                            echo "<tr><td colspan='6' class='group'><strong>{$row->Code}</strong></td></tr>";
                            $group2 = $row->Code;
                        } // GROUP 2 //
            
                        // DETAIL //
                        $totalgroup1 = reportVarAddValue($totalgroup1, $row);
                        $totalgroup2 = reportVarAddValue($totalgroup2, $row);
                        $totalall = reportVarAddValue($totalall, $row);
                        echo reportTableFields($row, $fields);
                        // DETAIL //
            
                        @endphp
                    @endforeach

                </tbody>

                @php 
                    echo reportTotal($totalgroup2, 5, $group2);
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