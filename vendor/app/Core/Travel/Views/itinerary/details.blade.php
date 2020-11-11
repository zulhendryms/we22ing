<table class="bordered">
    <tr class="strong text-center">
        <td style="width: 10%">DATE</td>
        <td>ITINERARY</td>
        <td style="width: 25%">RESTAURANT</td>
    </tr>
    <?php
        $dateFrom = Carbon::parse($travelTransaction->DateFrom);
        $dateUntil = Carbon::parse($travelTransaction->DateUntil);
        while ($dateFrom->lte($dateUntil)) {
            $data1 = $activities->filter(function ($value) use ($dateFrom) {
                return Carbon::parse($value->DateFrom)->format('Y-m-d') == $dateFrom->format('Y-m-d');
            });
            $data2 = $restaurants->filter(function ($value) use ($dateFrom) {
                return Carbon::parse($value->DateFrom)->format('Y-m-d') == $dateFrom->format('Y-m-d');
            });

            $count = $data1->count() > $data2->count() ? $data1->count() : $data2->count();
            if ($count == 0) {
                $dateFrom->addDay(); 
                continue;
            }

            $date = $dateFrom->format("M\' d (D)");
            $desc1 = '';
            $desc2 = '';
    ?>
        <tr class="border-bottom-none">
    <?php
            for ($i = 0; $i < $count; $i++) {
                if ($data1->count() != 0) {
                    $keys = $data1->keys();
                    if (isset($keys[$i])) {
                        $k = $keys[$i];
                        if (isset($data1[$k])) {
                            if ($desc1 != '') $desc1 .= '</br>';
                            $desc1 .= Carbon::parse($data1[$k]->DateFrom)->format('H:i').' - '.Carbon::parse($data1[$k]->DateUntil)->format('H:i'). " : ";
                            if (!empty($data1[$k]->Name)) {
                                $desc1 .= $data1[$k]->Name;
                            } else if (!is_null($data1[$k]->Item)) {
                                $desc1 .= $data1[$k]->ItemObj->Name;
                            }
                        }
                    }
                }

                if ($data2->count() != 0) {
                    $keys = $data2->keys();
                    if (isset($keys[$i])) {
                        $k = $keys[$i];
                        if (isset($data2[$k])) {
                            if ($desc2 != '') $desc2 .= '</br>';
                            $desc2 .= Carbon::parse($data2[$k]->DateFrom)->format('H:i').' - '.Carbon::parse($data2[$k]->DateUntil)->format('H:i'). " : ";
                            if (!empty($data2[$k]->Name)) {
                                $desc2 .= $data2[$k]->Name;
                            } else if (!is_null($data2[$k]->Item)) {
                                $desc2 .= $data2[$k]->ItemObj->Name;
                            }
                        }
                    }
                }
            }
    ?>
    
            <td style="width: 15%">{{$date}}</td>
            <td>{!! $desc1 !!}</td>
            <td>{!! $desc2 !!}</td>
        </tr>
    <?php
            $dateFrom->addDay(); 
        }
    ?>
</table>