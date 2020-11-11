  @php 
    $s = $dataReport;
    $col = 0; 
    $colTotalCount = 0; 
  @endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{$s->Name}}</title>
  <script type="text/php"></script>
  <style>
    @page { margin: 110px 25px; }
    p { page-break-after: always; }
    p:last-child { page-break-after: never; }

    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin-bottom: 20px;
    }
    
    table th {
      padding: 15px 10px;
      color: #5D6975;
      border-bottom: 1px solid #C1CED9;
      white-space: nowrap;
      font-weight: bold; 
      color: #ffffff;
      border-top: 1px solid  #5D6975;
      border-bottom: 1px solid  #5D6975;
      background: #888888;
      font-size: 14px;
      padding-top:15px;
      padding-bottom:15px;
      padding-left:10px;
      padding-right:10px;
    }
    table td {
      border: 1px solid #dddddd;
      vertical-align: top;
      font-size: 12px;
      padding-top:10px;
      padding-bottom:2px;
      padding-left:2px;
      padding-right:1px;
    }
    table td.firstcol { padding-left: 5px; }
    table td.lascol { padding-right: 5px; }
    table th.firstcol { padding-left: 5px; }
    table th.lascol { padding-right: 5px; }
    table td.group {
      padding-left: 8px;
      padding-top:8px;
      font-size: 14px;
      padding-bottom:8px;
      background: #F5F5F1; 
      font-weight: bold; }
  </style>
</head>
<body style="margin:0; font-family:Tahoma, Geneva, sans-serif">
  <main>
    <table>

      <!-- HEAD -->
      <thead>
        <tr> {{--width:675px--}}
          @foreach($s->FieldsParent as $field)
            <th class="{{$col == 0 ? ' firstcol ' : ''}}
                {{$col == count($s->FieldsParent)-1 ? ' lastcol ' : ''}}" 
                style="{{isset($s->Columns[$col]) ? 'width:'.$s->Columns[$col].'px' : ''}}"
                colspan='{{isset($field->ColSpan) ? $field->ColSpan : 1}}'>
                {{strtoupper($field->Name)}} 
            </th>
            @php $col = $col+1; $colTotalCount = $colTotalCount+1; @endphp
          @endforeach
        </tr>
        @if($s->ReportType == 'Detail')
          <tr> {{--width:675px--}}
            <th></th>
            @foreach($s->FieldsDetail as $field)
              <th class="{{$col == 0 ? ' firstcol ' : ''}}
                  {{$col == count($s->FieldsDetail)-1 ? ' lastcol ' : ''}}" 
                  style="{{isset($s->Columns[$col]) ? 'width:'.$s->Columns[$col].'px' : ''}}"
                  colspan='{{isset($field->ColSpan) ? $field->ColSpan : 1}}'>
                  {{strtoupper($field->Name)}}
              </th>
              @php $col = $col+1; @endphp
            @endforeach
          </tr>
        @endif
      </thead>
      <!-- HEAD -->
      
      <!-- BODY START TO LOOP -->
      <tbody>        
        @foreach($data as $row)

          <!-- GROUP 3 -->
          @if(isset($s->Group3))
            @if($row->{$s->Group3->Alias} != $s->Group3->Value)
              @php 
                echo reportGeneratorTotal($s->Group1);
                echo reportGeneratorTotal($s->Group2);
                echo reportGeneratorTotal($s->Group3); 
              @endphp
              
              <tr><td colspan='{{$colTotalCount}}' class='group'><strong>{{ strtoupper($row->{$s->Group3->Alias}) }}</strong></td></tr>
              @php 
                $s->Group3->Value=$row->{$s->Group3->Alias}; 
                $s->Group1->Value=null;
                foreach ($s->Group1->Sum as $f => $v) $s->Group1->Sum[$f] = 0;
                foreach ($s->Group2->Sum as $f => $v) $s->Group2->Sum[$f] = 0;
                foreach ($s->Group3->Sum as $f => $v) $s->Group3->Sum[$f] = 0;
              @endphp
            @endif
          @endif
          <!-- GROUP 3 -->

          <!-- GROUP 2 -->
          @if(isset($s->Group2))
            @if($row->{$s->Group2->Alias} != $s->Group2->Value)
              @php 
                echo reportGeneratorTotal($s->Group1);
                echo reportGeneratorTotal($s->Group2); 
              @endphp
              
              <tr><td colspan='{{$colTotalCount}}' class='group'><strong>{{ strtoupper($row->{$s->Group2->Alias}) }}</strong></td></tr>
              @php 
                $s->Group2->Value=$row->{$s->Group2->Alias}; 
                $s->Group1->Value=null;
                foreach ($s->Group1->Sum as $f => $v) $s->Group1->Sum[$f] = 0;
                foreach ($s->Group2->Sum as $f => $v) $s->Group2->Sum[$f] = 0;
              @endphp
            @endif
          @endif
          <!-- GROUP 2 -->

          <!-- GROUP 1 -->
          @if(isset($s->Group1))
            @if($row->{$s->Group1->Alias} != $s->Group1->Value)
              @php echo reportGeneratorTotal($s->Group1); @endphp

              <tr><td colspan='{{$colTotalCount}}' class='group'><strong>{{ strtoupper($row->{$s->Group1->Alias}) }}</strong></td></tr>
              @php 
                $s->Group1->Value=$row->{$s->Group1->Alias}; 
                foreach ($s->Group1->Sum as $f => $v) $s->Group1->Sum[$f] = 0;
              @endphp
            @endif
          @endif
          <!-- GROUP 1 -->
          
          <!-- PARENT --> 
          @php
            $parentShow = false;
            if ($s->ReportType == 'Detail') {
              $parentShow = $s->Parent != $row->p_Code;
            } else {
              $parentShow = true;
            }
          @endphp
          @if($parentShow)
                <tr>
                @php $col = 0; @endphp
                @foreach($s->FieldsParent as $field)
                    <td align="{{ is_numeric($row->{$field->Alias}) ? 'right' : 'left' }}"
                        class="{{$col == 0 ? ' firstcol ' : ''}}
                        {{$col == count($s->FieldsParent)-1 ? ' lastcol ' : ''}}" 
                        >
                        {{ is_numeric($row->{$field->Alias}) ? number_format($row->{$field->Alias} ,2) : $row->{$field->Alias} }}
                    </td>
                    @php $col = $col+1; @endphp
                @endforeach
                @php 
                  if (isset($s->Group1)) foreach ($s->Group1->Sum as $f => $v) $s->Group1->Sum[$f] = $s->Group1->Sum[$f] + $row->{$f};
                  if (isset($s->Group2)) foreach ($s->Group2->Sum as $f => $v) $s->Group2->Sum[$f] = $s->Group2->Sum[$f] + $row->{$f};
                  if (isset($s->Group3)) foreach ($s->Group3->Sum as $f => $v) $s->Group3->Sum[$f] = $s->Group3->Sum[$f] + $row->{$f};
                  if ($s->ReportType == 'Detail') $s->Parent = $row->p_Code;
                @endphp
                </tr>
          @endif
          <!-- PARENT -->

          <!-- DETAIL -->
          @if($s->ReportType == 'Detail')
                <tr>
                <td></td>
                @php $col = 0; @endphp
                @foreach($s->FieldsDetail as $field)
                      <td align="{{ is_numeric($row->{$field->Alias}) ? 'right' : 'left' }}"
                          colspan="{{isset($field->ColSpan) ? $field->ColSpan : 1}}"
                          class="{{$col == 0 ? ' firstcol ' : ''}}
                          {{$col == count($s->FieldsDetail)-1 ? ' lastcol ' : ''}}" 
                          >
                          {{ is_numeric($row->{$field->Alias}) ? number_format($row->{$field->Alias} ,2) : $row->{$field->Alias} }}
                      </td>
              @endforeach
              @php $col = $col+1; @endphp
              </tr>
          @endif 
          <!-- DETAIL -->
          

        @endforeach
        @php
          if (isset($s->Group1)) echo reportGeneratorTotal($s->Group1);
          if (isset($s->Group2)) echo reportGeneratorTotal($s->Group2);
          if (isset($s->Group3)) echo reportGeneratorTotal($s->Group3); 
        @endphp
      </tbody>
      <!-- BODY END OF LOOP -->

    </table>
    <div style="padding: 13px 20px 13px 20px;">
      <div style="font-size: 14px; color: #858585;"></div>
    </div>
  </main>
</body>
</html>