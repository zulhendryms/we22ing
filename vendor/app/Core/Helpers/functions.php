<?php
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\Status;
use Carbon\Carbon;

if (!function_exists("disabledFieldsForEdit")) {
    function disabledFieldsForEdit()
    {
        return ['CompanyName','StockWithdrawed','Oid','id','Type','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy', 'prev', 'index','Index','exists','timestamps','TotalAmount','SubtotalAmount','ItemObj','ItemName','BusinessPartnerObj','BusinessPartnerName','TravelHotelRoomTypeName', 'TravelFlightNumberName','NationalityName','CurrencyRateDateName'];
    }
}

if (!function_exists("ifnull")) {
    function ifnull($value, $valueIfNull = null)
    {        
        $isset = false;
        try {  $isset = isset($value); } 
        catch (\Exception $ex) {  $err = true; }
        if ($isset) return $value; else return $valueIfNull;
    }
}


if (!function_exists("removeDuplicateArray")) {
    function removeDuplicateArray($array, $blacklist = []) {
        $result = [];
        foreach($array as $u) {
            if (!in_array($u, $result) && !in_array($u, $blacklist)) $result[] = $u;
        }
        // if (count($result) == 1 && $returnString) $result = $result[0];
        return $result;
    }
}
    
if (!function_exists("actionCheckCompany")) {
function actionCheckCompany($module, $actions, $logger = false)
{
    $company = Illuminate\Support\Facades\Auth::user()->CompanyObj;
    if ($company->CustomFieldSetting) {
        $data = json_decode($company->CustomFieldSetting);
        if ($logger) dd($actions);
        foreach($actions as $a) {
            $a = (object) $a;
            foreach($data as $c) {
                if (!isset($c->type)) continue;
                if (!$c->table == $module) continue;
                if ($c->type !== 'action') continue;
                if (isset($a->name)) {
                    if ($c->code !== $a->name) continue;
                    if (isset($c->hide)) $a->hide = $c->hide;
                    if (isset($c->title)) $a->overrideLabel = $c->title;
                }
            }
        }
    }
    return $actions;
}
}

if (!function_exists("requestToObject")) {
    function requestToObject($request)
    {
        // $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        return json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
    }
}

if (!function_exists("err_return")) {
    function err_return($err)
    {
        throw $err;
    }
}

if (!function_exists("errjson")) {
    function errjson($e)
    {
        if ($e instanceof Exception) {  
            // dd($e);
            if (method_exists($e, 'getFile')) {
                $err = $e->getMessage()." - #".$e->getCode()."; /".basename(dirname($e->getFile())).'/'.basename($e->getFile())." (Line No. ".$e->getLine().")";
            } elseif (!method_exists($e, 'getMessage')) {
                $err = $e->getMessage();
            } elseif (gettype($e)=='object') {
                $err = $e->getMessage();
            } else {
                return $e;
            }
            return $err;
        } else { 
            return $err;
        }
        
    }
}

if (!function_exists("errmsg")) {
    function errmsg($err)
    {
        if (strpos($err->getMessage(),"1062 Duplicate entry")> 0) return 'Duplicate entry is found';
        elseif (strpos($err->getMessage(),"1451 Cannot delete")> 0) return 'Data is in used, it is invalid to be deleted';
        else return $err->getMessage();
    }
}

if (!function_exists("ui_col")) {
    function object_to_array($data)
    {
        // $data = json_decode($data);
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = object_to_array($value);
            }
            // return json_decode(json_encode($result));
            return $result;
        }
        return $data;
        // return json_decode(json_encode($data));
    }
}

if (!function_exists("ui_col")) {
    function ui_col($big = null, $med = null, $small = null)
    {
        if ($big) $big = round(12/$big, 0, PHP_ROUND_HALF_UP);
        if ($med) $med = round(12/$med, 0, PHP_ROUND_HALF_DOWN);
        if ($small) $small = round(12/$small, 0, PHP_ROUND_HALF_DOWN);
        if ($big == null || $big == 0) $big = 3;
        if ($med == null) $med = round($big*2, 0, PHP_ROUND_HALF_DOWN);
        if ($med == null || $med== 0) $med = 1;
        if ($med > 12) $med = 12;
        if ($small == null) $small = round($med*2, 0, PHP_ROUND_HALF_DOWN);
        if ($small == null || $small== 0) $small = 1;
        if ($small > 12) $small = 12;
        return "col-sm-".$small." col-md-".$med." col-lg-".$big;
    }
}

if (!function_exists("ui_status")) {
    function ui_status($status)
    {
        if ($status == null) return "";
        if ($status->Code == 'entry') return ""; else return "disabled";
    }
}

if (!function_exists("ui_statusgrid")) {
    function ui_statusgrid($status)
    {
        if ($status == null) return "true";
        if ($status->Code == 'entry') return "true"; else return "false";
    }
}

if (!function_exists("ui_required")) {
    function ui_required()
    {
        return "required data-validation-required-message='This is required";
    }
}

if (!function_exists("ui_requiredmax")) {
    function ui_requiredmax($max)
    {
        return "required data-validation-required-message='This is required & Max ".$max." character' maxlength='".$max."'";
    }
}

if (!function_exists("ui_value")) {
    function ui_value($v = null, $default = null)
    {
        if ($v == null) {
            if ($default == "0") return "value=0"; 
            if ($default) return "value=".$default; else return ""; 
        } else {
            return "value=".$v;
        }
    }
}

if (!function_exists("ui_now")) {
    function ui_now($v = null)
    {
        if ($v == null) return "value=".date('Y-m-d'); else return "value=".$v;
    }
}

if (!function_exists("ui_selected")) {
    function ui_selected($oid = null, $row, $default = null)
    {
        if ($oid == null) {
            if ($default == null) return "";
            if ($default == $row->Oid) return "selected"; else return "";
        } else {
            if ($oid == $row->Oid) return "selected"; else return "";
        }
    }
}

if (!function_exists("ui_selectedstatus")) {
    function ui_selectedstatus($data = null, $row)
    {
        if ($data == null) {
            if ($row->Code == 'entry') return "selected"; else return "";
        } else {
            if ($data->Status == $row->Oid) return "selected"; else return "";
        }
    }
}

if (!function_exists("include_route_file")) {
    function include_route_files($app, $modules, $name = 'web')
    {
        foreach ($modules as $module) {
            require base_path("app/{$app}")."/{$module}/{$name}.php";
        }
    }
}

if (!function_exists("company")) {
    /**
     * Get company object
     * 
     * @return \App\Core\Master\Entities\Company
     */
    function company()
    {
        $user = Illuminate\Support\Facades\Auth::user();
        if ($user) $company = $user->Company; else $company = config('app.company_id');
        return \App\Core\Master\Entities\Company::find($company);
    }
}

if (!function_exists("company_timezone")) {
    /**
     * Get company timezone
     * 
     * @return integer
     */
    function company_timezone()
    {
        $company = \Illuminate\Support\Facades\DB::table('company')
        ->select('Timezone')
        ->where('Oid', config('app.company_id'))->first();

        return $company->Timezone ?? 7;
    }
}

if (!function_exists("company_email")) {
    /**
     * Get company email
     * 
     * @return string
     */
    function company_email()
    {
        $company = \Illuminate\Support\Facades\DB::table('company')
        ->select('Email')
        ->where('Oid', config('app.company_id'))->first();

        return $company->Email;
    }
}

if (!function_exists("company_emailcc")) {
    /**
     * Get company email
     * 
     * @return string
     */
    function company_emailcc()
    {
        $company = \Illuminate\Support\Facades\DB::table('company')
        ->select('EmailCC')
        ->where('Oid', config('app.company_id'))->first();
        return explode(',', $company->EmailCC);
    }
}

if (!function_exists("company_logo")) {
    /**
     * Get company logo
     * 
     * @return string
     */
    function company_logo($num = '')
    {
        $company = \Illuminate\Support\Facades\DB::table('company')
        ->select('Image'.$num)
        ->where('Oid', config('app.company_id'))->first();

        return $company->{'Image'.$num};
    }
}

if (!function_exists("company_code")) {
    /**
     * Get company code
     * 
     * @return string
     */
    function company_code()
    {
        $company = \Illuminate\Support\Facades\DB::table('company')
        ->select('Code')
        ->where('Oid', config('app.company_id'))->first();

        return $company->Code;
    }
}

if (!function_exists("company_name")) {
    /**
     * Get company name
     * 
     * @return string
     */
    function company_name()
    {
        $company = \Illuminate\Support\Facades\DB::table('company')
        ->select('Name')
        ->where('Oid', config('app.company_id'))->first();

        return $company->Name;
    }
}

if (!function_exists("has_feature")) {
    /**
     * Check if company has feature
     * @param string $code
     * @return boolean
     */
    function has_feature($code)
    {
        return company()->hasFeature($code);
    }
}

if (!function_exists("has_plugin")) {
    /**
     * Check if company has feature
     * @param string $code
     * @return boolean
     */
    function has_plugin($code)
    {
        return company()->hasPlugin($code);
    }
}

if (!function_exists("app_url")) {
    /**
     * Get app url
     * 
     * @return string
     */
    function app_url($app = 'url', $url = '') {
        if (empty($app)) $app = 'url';
        if ($app != 'url') {
            $app .= '_url';
        }
        if (!empty($url) && !starts_with($url, '/')) $url = '/'.$url;
        return config('app.'.$app).$url;    
    }
}

if (!function_exists("app_asset")) {
    /**
     * Get app asset url
     * 
     * @return string
     */
    function app_asset($app, $url = '') {
        if (!starts_with($url, '/')) $url = '/'.$url;
        if (strpos($url, 'assets/'.$app) === false) $url = '/assets/'.$app.$url;
        return app_url('', $url);
    }
}

if (!function_exists("app_storage")) {
    /**
     * Get app storage url
     * 
     * @return string
     */
    function app_storage($app, $url = '') {
        if (!starts_with($url, '/')) $url = '/'.$url;
        return app_url('', '/storage/'.$app.$url);    
    }
}

if (!function_exists("company_storage")) {
    /**
     * Get app storage url
     * 
     * @return string
     */
    function company_storage($url = '') {
        if (!starts_with($url, '/')) $url = '/'.$url;
        return app_url('', '/storage/'.company_code().$url);    
    }
}


if (!function_exists("is_home")) {
    /**
     * Check if is home
     * 
     * @return boolean
     */
    function is_home()
    {
        return request()->route()->getName() == config('core.routes.home');
    }
}

if (!function_exists("is_mobile")) {
    /**
     * Check if is home
     * 
     * @return boolean
     */
    function is_mobile()
    {
        $agent = new Jenssegers\Agent\Agent;
        return $agent->isMobile();
    }
}

if (!function_exists("is_webview")) {
    /**
     * Check if is home
     * 
     * @return boolean
     */
    function is_webview()
    {
        return session(config('constants.mobile')) ?? request()->query(config('constants.mobile')) ?? false;
    }
}


if (!function_exists("is_android")) {
    /**
     * Check if is android
     * 
     * @return boolean
     */
    function is_android()
    {
        $agent = new Jenssegers\Agent\Agent;
        return $agent->isAndroidOS();
    }
}

if (!function_exists("is_ios")) {
    /**
     * Check if is android
     * 
     * @return boolean
     */
    function is_ios()
    {
        $agent = new Jenssegers\Agent\Agent;
        return $agent->isiOS();
    }
}

if (!function_exists("encrypt_salted")) {
    /**
     * Encrypt with salt
     * @param string $data
     * @return string
     */
    function encrypt_salted($data)
    {
        return encrypt(config('app.salt_1').'.'.$data.'.'.config('app.salt_2'));
    }
}

if (!function_exists("decrypt_salted")) {
    /**
     * Decrypt with salt
     * @param string $data
     * @return string
     */
    function decrypt_salted($data)
    {
        if (empty($data)) return $data;
        if (strlen($data) < 50) return $data;
        $data = str_replace(config('app.salt_1').'.', '', str_replace('.'.config('app.salt_2'), '', decrypt($data)));
        return str_replace('.'.config('app.salt_1'), '', str_replace(config('app.salt_2').'.', '', $data));
    }
}

if (!function_exists("image")) {
    /**
     * @param string $image
     * @param string $size
     * @return string
     */
    function image($image, $size = null)
    {        
        if ($image == null)
            return company()->DefaultProductImage;
        else 
            $path = $image;

        if (empty($size)) {
            if (is_mobile())
                $size = 'sm';
            else
                $size = 'md';
        }
        if (strpos($path, 'http') === 0) {
            if (empty($size)) return $path;
            // $path = substr($path, strpos($path, '/public/') + strlen('/public/'));
            $path = substr($path, strpos($path, 'storage'));
        }
        if (empty($size)) return asset($path);
        $filename = basename($path);
        $path = str_replace(' ', '%20', $path);
        $path = str_replace($filename, $size.'_'.$filename, $path);
        if (is_file($path)) return asset($path);
        $image = str_replace(' ', '%20', $image);
        return asset($image);
    }
}


if (!function_exists("small_image")) {
    /**
     * @param string $image
     * @return string
     */
    function small_image($image)
    {
        return image($image, 'sm');
    }
}

if (!function_exists("medium_image")) {
    /**
     * @param string $image
     * @return string
     */
    function medium_image($image)
    {
        return image($image, 'md');
    }
}

if (!function_exists("getChartColor")) {
    function getChartColor($i)
    {
        if ($i == 0) return "#3e95cd";
        if ($i == 1) return "#8e5ea2";
        if ($i == 2) return "#3cba9f";
        if ($i == 3) return "#e8c3b9";
        if ($i == 4) return "#c45850";
        else return "#3e95cd";
    }
}

if (!function_exists("convert_number_to_words")) {
    function convert_number_to_words($number) {
   
        $hyphen      = '-';
        $conjunction = '  ';
        $separator   = ' ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary  = array(
            0                   => 'Zero',
            1                   => 'One',
            2                   => 'Two',
            3                   => 'Three',
            4                   => 'Four',
            5                   => 'Five',
            6                   => 'Six',
            7                   => 'Seven',
            8                   => 'Eight',
            9                   => 'Nine',
            10                  => 'Ten',
            11                  => 'Eleven',
            12                  => 'Twelve',
            13                  => 'Thirteen',
            14                  => 'Fourteen',
            15                  => 'Fifteen',
            16                  => 'Sixteen',
            17                  => 'Seventeen',
            18                  => 'Eighteen',
            19                  => 'Nineteen',
            20                  => 'Twenty',
            30                  => 'Thirty',
            40                  => 'Fourty',
            50                  => 'Fifty',
            60                  => 'Sixty',
            70                  => 'Seventy',
            80                  => 'Eighty',
            90                  => 'Ninety',
            100                 => 'Hundred',
            1000                => 'Thousand',
            1000000             => 'Million',
            1000000000          => 'Billion',
            1000000000000       => 'Trillion',
            1000000000000000    => 'Quadrillion',
            1000000000000000000 => 'Quintillion'
        );
       
        if (!is_numeric($number)) {
            return false;
        }
       
        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }
     
        if ($number < 0) {
            return $negative . convert_number_to_words(abs($number));
        }
       
        $string = $fraction = null;
       
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }
       
        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= convert_number_to_words($remainder);
                }
                break;
        }
       
        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }
       
        return $string;
    }
}

if (!function_exists("queryInsertFromFields")) {
    function queryInsertFromFields($data, $disabled = [])
    {
        $arr = [];
        foreach ($data->getAttributes() as $field => $key) {
            if (in_array($field, $disabled)) continue;
            if ($data->{$field} == null) continue;
            $arr = array_merge($arr, [
                $field => "'".$data->{$field}."'",
            ]);
        }
        return $arr;
    }
}


if (!function_exists("queryInsertFromFields2")) {
    function queryInsertFromFields2($data, $disabled = [])
    {
        $arr = [];
        foreach ($data as $field => $key) {
            if (in_array($field, $disabled)) continue;
            if ($data->{$field} == null) continue;
            $arr = array_merge($arr, [
                $field => $field,
            ]);
        }
        return $arr;
    }
}

if (!function_exists("pluckComma")) {
    function pluckComma($data, $field = "Oid")
    {
        $str = "";
        for ($i = 0; $i <= count($data)-1; $i++) {
            $str = $str.($str ? ', ': '')."'".$data[$i]->{$field}."'";
        }
        return $str;
    }
}

if (!function_exists("mergeObjectField")) {
    function mergeObjectField($data1, $data2)
    {
        foreach ($data2 as $field => $value) $data1->{$field} = $value;
        return $data1;
    }
}

if (!function_exists("convertObjectToArray")) {
    function convertObjectToArray($data)
    {
        $arr = [];
        foreach ($data as $field => $value) $arr = array_merge($arr, [ $field => "'".$value."'" ]);
        return $arr;
    }
}

if (!function_exists("dataReorder")) {
    function dataReorder($data, $sort = false)
    {
        $data = collect($data)->sortBy($sort);
        $arr = [];
        foreach ($data as $row) {
            if (is_array($row)) $arr[] = $row[0];
            else $arr[] = $row; //$row[0];
        }
        return $arr;
    }
}

if (!function_exists("moveToNewObject")) {
    function moveToNewObject($data)
    {
        $result = new \stdClass();
        foreach ($data as $field => $value) $result->{$field} = $value;
        return $result;
    }
}

if (!function_exists("qstr")) {
    function qstr($val)
    {
        if ($val == null) return "null";
        if ($val == '') return "null";        
        else return "'".$val."'";
    }
}

if (!function_exists("qstrbol")) {
    function qstrbol($val)
    {
        if ($val == null) return "0";
        if ($val == '') return "0";        
        else return $val;
    }
}

if (!function_exists("qstrno")) {
    function qstrno($val)
    {
        if ($val == null) return "0";
        if ($val == '') return "0";   
        if (!is_numeric($val)) return "0";     
        else return $val;
    }
}

if (!function_exists("ddd")) {
    function ddd($val)
    {
        echo "<pre>";
        print_r($val);
        echo "</pre>";
        die();
    }
}

if (!function_exists("err")) {
    function err($val)
    {
        return response()->json($val, Response::HTTP_UNAUTHORIZED);
    }
}

if (!function_exists("convertParamToWhereIn")) {
    function convertParamToWhereIn($val)
    {
        // $tmp = convertParamToWhereIn($request->query('Code'));
        // if ($tmp) $data = $data->whereIn('Code', $tmp);

        // cara ke 2
        // if ($request->has('Code')) $data = $data->whereIn('Code', $request->query('Code'));

        // cara ke 3
        // $param = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        // if (isset($param->Code)) $data = $data->whereIn('Code', $param->Code);

        $result = null;
        if($val != null){
            foreach ($val as $key => $value) {
                $result = ($result ? $result."," : "")."'".$value."'";
            }
        }
        return $result;
    }
}

if (!function_exists("reportVarCreate")) {
    function reportVarCreate($val = [])
    {
        for ($i = 0; $i < count($val); $i++) $result[$val[$i]] = 0;
        return $result;
        // return [
        //     'Code' => '',
        //     'Amt' => $result
        // ];
    }
}

if (!function_exists("reportVarReset")) {
    function reportVarReset($val = [])
    {
        foreach ($val as $key => $value) $val[$key] = 0;
        return $val;
    }
}

if (!function_exists("reportVarAddValue")) {
    function reportVarAddValue($val = [], $data)
    {
        foreach ($data as $field => $amount) {
            foreach ($val as $key => $value) {
                if ($field == $key) { 
                    $val[$key] = $val[$key] + $amount;
                }
            }
        }
        return $val;
    }
}

if (!function_exists("reportGeneratorTotal")) {
    function reportGeneratorTotal($group)
    {        
        $html = '';
        if (isset($group)) {
            if ($group->Value && $group->ColSpan > 1) {
                $colSpan = $group->ColSpan - 1;
                $html = "<tr>
                    <td colspan='{$colSpan}' class='total' align='right'><strong>Total For : {$group->Value}</strong></td>";
                    foreach ($group->Sum as $f => $v) {
                        $html=$html."<td class='total' align='right'><strong>".number_format($group->Sum[$f] ,2)."</strong></td>";
                    }
                $html=$html."</tr>";
            }
        }
        return $html;
    }
}

if (!function_exists("reportTotal")) {
    function reportTotal($data, $colspan = 1, $group = null)
    {
        $title = $group == null ? "GRAND TOTAL" : "Total for ".$group;
        $html = "";
        foreach ($data as $key => $value) {            
          $html = $html."<td class='total' align='right'><strong>".number_format($value,2,',','.')."</strong></td>";
        }
        return "<tr>
          <td colspan='{$colspan}' class='total' align='right'><strong>{$title}</strong></td>
          ".$html."
        </tr>";
    }
}

if (!function_exists("reportStyle1")) {
    function reportStyle1()
    {
        logger('reportStyle1');
        return "<script type='text/php'></script>
        <style>
          @page { margin: 110px 25px; }
          p{
            line-height: normal;
            padding: 0px;
            margin: 0px;
          }
      
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
            font-size: 11px;
            padding-top:10px;
            padding-bottom:2px;
            padding-left:2px;
            padding-right:5px;
          }
          table td.firstcol { padding-left: 5px; }
          table td.lascol { padding-right: 5px; }
          table th.firstcol { padding-left: 5px; }
          table td.lascol { padding-right: 5px; }
          table td.group {
            padding-left: 10px;
            padding-top:10px;
            font-size: 14px;
            padding-bottom:10px;
            background: #F5F5F1; 
            font-weight: bold; }     
          table td.group2 {
            padding-left: 10px;
            padding-top:15px;
            border-top: 2px solid #c7c7c7; }     
        </style>";
    }
}

if (!function_exists("reportHeader")) { //reportTableHeader
    function reportHeader($fields = [],$headfoot = true)
    {
        logger('reportHeader');
        $html = "";
        $i = 0;
        foreach($fields as $row) {
            $align = "";
            $width = isset($row['w']) ? " style=width:'".$row['w']."px' " : "";
            $colspan = isset($row['m']) ? " colspan='".$row['m']."' " : "";
            $row['t'] = isset($row['t']) ? strtolower($row['t']) : "text";
            $row['n'] = isset($row['n']) ? $row['n'] : $row['c'];
            switch ($row['t']) {
                case "int":
                    $align = "align='right'";
                    break;
                case "double":
                    $align = "align='right'";
                    break;
            }
            $html .= "<th "
                .($i==0 ? "class='firstcol' " : " ")
                .($i==count($fields) ? "class='lastcol' " : " ")
                .$width
                .$colspan
                .$align.">"
                .strtoupper($row['n'])
                ."</th>";
            $i += 1;
        }
        if ($headfoot == true) return "<thead><tr>".$html."</tr></thead>";
        else return "<tr>".$html."</tr>";
    }
}

if (!function_exists("reportHeader2")) {
    function reportHeader2($fields = [],$fields2 = [],$headfoot = true)
    {
        logger('reportHeader');
        $html = "";
        $i = 0;
        foreach($fields as $row) {
            $align = "";
            $width = isset($row['w']) ? " style=width:'".$row['w']."px;' " : "";
            $colspan = isset($row['m']) ? " colspan='".$row['m']."' " : "";
            $row['t'] = isset($row['t']) ? strtolower($row['t']) : "text";
            $row['n'] = isset($row['n']) ? $row['n'] : $row['c'];
            switch ($row['t']) {
                case "int":
                    $align = "align='right'";
                    break;
                case "double":
                    $align = "align='right'";
                    break;
            }
            $html .= "<th "
                .($i==0 ? "class='firstcol' " : " ")
                .($i==count($fields) ? "class='lastcol' " : " ")
                .$width
                .$colspan
                .$align.">"
                .strtoupper($row['n'])
                ."</th>";
            $i += 1;
        }
        $html2 = "";
        $i = 0;
        foreach($fields2 as $row) {
            $align = "";
            $width = isset($row['w']) ? " style=width:'".$row['w']."px' " : "";
            $colspan = isset($row['m']) ? " colspan='".$row['m']."' " : "";
            $row['t'] = isset($row['t']) ? strtolower($row['t']) : "text";
            $row['n'] = isset($row['n']) ? $row['n'] : $row['c'];
            switch ($row['t']) {
                case "int":
                    $align = "align='right'";
                    break;
                case "double":
                    $align = "align='right'";
                    break;
            }
            $html2 .= "<th "
                .($i==0 ? "class='firstcol' " : " ")
                .($i==count($fields2) ? "class='lastcol' " : " ")
                .$width
                .$colspan
                .$align.">"
                .strtoupper($row['n'])
                ."</th>";
            $i += 1;
        }
        if ($headfoot == true) return "<thead><tr>".$html."</tr><tr>".$html2."</tr></thead>";
        else return "<tr>".$html."</tr><tr>".$html2."</tr>";
    }
}

if (!function_exists("reportTableFields")) {
    function reportTableFields($data, $fields = [])
    {
        $html = "";
        $i = 0;
        foreach($fields as $row) {
            $align = "";
            $colspan = isset($row['m']) ? " colspan='".$row['m']."' " : "";
            $row['t'] = isset($row['t']) ? strtolower($row['t']) : "text";
            if ($row['c'] == '') {
                $val = '';
            } elseif ($row['t'] == 'double') {
                $val = number_format($data->{$row['c']},2,',','.');
                $align = "align='right'";
            } elseif ($row['t'] == 'int') {
                $val = $data->{$row['c']};
                $align = "align='right'";
            } elseif ($row['t'] == 'date' || $row['c'] == 'Date') {                
                $align = "align='left'";
                $row['f'] = isset($row['f']) ? strtolower($row['f']) : "medium";                
                switch ($row['f']) {
                    case "short":
                        $val = date('j/n', strtotime($data->{$row['c']}));
                        break;
                    case "medium":
                        $val = date('d M Y', strtotime($data->{$row['c']}));
                        break;
                    case "long":
                        $val = date('d F Y', strtotime($data->{$row['c']}));
                        break;
                    case "datetime":
                        $val = date('d M Y H:i:s', strtotime($data->{$row['c']}));
                        break;
                    default:                        
                        $val = date('D, d M Y', strtotime($data->{$row['c']}));
                        break;
                }
            } else {
                $val = $data->{$row['c']};
            }
            $html .= "<td "
                .($i==0 ? "class='firstcol' " : " ")
                .($i==count($fields) ? "class='lastcol' " : " ")
                .$colspan
                .$align.">"
                .$val
                ."</td>";
            $i += 1;
        }
        logger($html);
        return "<tr>".$html."</tr>";
    }
}

if (!function_exists("reportTableFieldsGroup")) {
    function reportTableFieldsGroup($data, $fields = [])
    {
        $html = "";
        $i = 0;
        foreach($fields as $row) {
            $align = "";
            $colspan = isset($row['m']) ? " colspan='".$row['m']."' " : "";
            $row['t'] = isset($row['t']) ? strtolower($row['t']) : "text";
            if ($row['c'] == '') {
                $val = '';
            } elseif ($row['t'] == 'double') {
                $val = number_format($data->{$row['c']},2,',','.');
                $align = "align='right'";
            } elseif ($row['t'] == 'int') {
                $val = $data->{$row['c']};
                $align = "align='right'";
            } elseif ($row['t'] == 'date' || $row['c'] == 'Date') {                
                $align = "align='left'";
                $row['f'] = isset($row['f']) ? strtolower($row['f']) : "medium";                
                switch ($row['f']) {
                    case "short":
                        $val = date('j/n', strtotime($data->{$row['c']}));
                        break;
                    case "medium":
                        $val = date('d M Y', strtotime($data->{$row['c']}));
                        break;
                    case "long":
                        $val = date('d F Y', strtotime($data->{$row['c']}));
                        break;
                    case "datetime":
                        $val = date('d M Y H:i:s', strtotime($data->{$row['c']}));
                        break;
                    default:                        
                        $val = date('D, d M Y', strtotime($data->{$row['c']}));
                        break;
                }
            } else {
                $val = $data->{$row['c']};
            }
            $html .= "<td "
                ."class = 'group2 "
                .($i==0 ? "firstcol " : " ")
                .($i==count($fields) ? "lastcol " : " ")
                ."' "
                .$colspan
                .$align.">"
                .$val
                ."</td>";
            $i += 1;
        }
        logger($html);
        return "<tr class>".$html."</tr>";
    }
}

if (!function_exists("comboStatus")) {
    function comboStatus($module)
    {
        return DB::table('sysstatus')
            ->select(
                'Oid',
                DB::raw("CONCAT(".$module.", ' - ', Code) AS Name")
                )->whereRaw($module.' IS NOT NULL')
                ->orderBy('Sort')->limit(100)->get();
    }
}

if (!function_exists("comboSelect")) {
    function comboSelect($tableName)
    {
        if ($tableName == 'user') return DB::table('user')
            ->select('Oid', DB::raw("UserName AS Name") )->whereRaw('GCRecord IS NULL')->where('IsActive',true)->orderBy('UserName')->limit(100)->get();
        elseif ($tableName == 'role') return DB::table('role')
            ->select('Oid', DB::raw("Name AS Name"))->orderBy('Name')->limit(100)->get();
        elseif ($tableName == 'company') return DB::table('company')
            ->select('Oid', DB::raw("Code AS Name"))->orderBy('Name')->limit(100)->get();
        elseif ($tableName == 'mstcurrency') return DB::table('mstcurrency')
            ->select('Oid', DB::raw("Code AS Name"))->orderBy('Name')->limit(100)->get();
        elseif (in_array($tableName, ['pospointofsale'])) return DB::table('pospointofsale')
            ->select('Oid', DB::raw("Code AS Name"))->orderBy('Name')->limit(100)->get();
        else return DB::table($tableName)
            ->select('Oid', DB::raw("CONCAT(Name, ' - ', Code) AS Name") )->whereRaw('GCRecord IS NULL')->orderBy('Name')->limit(100)->get();
    }
}

if (!function_exists("companyMultiModuleCriteria")) {
    function companyMultiModuleCriteria($found, $company, $companyWhere, $tableName = null)
    {
        $logger = true;
        $criteria = "";
        if ($company->Oid == $company->CompanySource) $type = 'Source';
        elseif ($company->Oid == $company->CompanyParent) $type = 'Parent';
        else $type = 'Detail';        
            
        if ($found == '') {
            $filter = "Company = ".$company->Code;
            $criteria = $companyWhere.".Oid = '".$company->Oid."'";
        } elseif ($type == 'Detail') { // Group - GROUP COMBO
            $filter = "Company = ".$company->Code;
            $criteria = $companyWhere.".Oid = '".$company->Oid."'";
        } elseif ($type == 'Source') { // Source - GROUP COMBO
            $filter = "CompanySource =".$company->CompanySourceObj->Code;
            $criteria = $companyWhere.".CompanySource = '".$company->CompanySource."'";
        } elseif ($type == 'Parent') { // Group - GROUP COMBO
            $filter = "CompanyParent ='".$company->CompanyParentObj->Code;
            $criteria = $companyWhere.".CompanyParent = '".$company->Oid."'";            
        } else {
            $filter = "Company = ".$company->Code;
            $criteria = $companyWhere.".Oid = '".$company->Oid."'";
        }
        if ($logger) logger('Helper '.$type." ".$tableName." ".($found ?: 'NOT FOUND')." ".$filter);
        return $criteria;
    }
}

if (!function_exists("companyMultiModuleSearch")) {
    function companyMultiModuleSearch($table, $combo = true)
    {
        $company = Auth::user()->CompanyObj;
        $found = '';
        if (substr($table, 0, 3) != 'sys') {
            $tmp = isJson($company->ModuleGlobal) ? json_decode($company->ModuleGlobal) : $company->ModuleGlobal;
            if ($found == '' && $tmp) $found = companyMultiModuleFound($tmp, $table, 'Global');

            $tmp = isJson($company->ModuleGroup) ? json_decode($company->ModuleGroup) : $company->ModuleGroup;
            if ($found == '' && $tmp) $found = companyMultiModuleFound($tmp, $table, 'Group');

            if ($combo) {
                $tmp = isJson($company->ModuleGlobalCombo) ? json_decode($company->ModuleGlobalCombo) : $company->ModuleGlobalCombo;
                if ($found == '' && $tmp) $found = companyMultiModuleFound($tmp, $table, 'GlobalCombo');
    
                $tmp = isJson($company->ModuleGroupCombo) ? json_decode($company->ModuleGroupCombo) : $company->ModuleGroupCombo;
                if ($found == '' && $tmp) $found = companyMultiModuleFound($tmp, $table, 'GroupCombo');
            }
            
            return companyMultiModuleCriteria($found, $company, 'Company');
        }
        return null;
    }
}

if (!function_exists("companyMultiModuleFound")) {
    function companyMultiModuleFound($tmp, $tableName, $found, $logger = false)
    {
        if (gettype($tmp) == 'string') if ($tmp == 'all') return $found;
        foreach($tmp as $row) {            
            if ($row == 'all') return $found;
            if ($row == $tableName) return $found;
        }
    }
}

if (!function_exists("reportQueryCompany")) {
    function reportQueryCompany($tableName)
    {
        $company = Auth::user()->CompanyObj;
        $found = '';
        $tmp = json_decode($company->ModuleGlobal);        
        if ($found == '' && $tmp) $found = companyMultiModuleFound($tmp, $tableName, 'Global');
        $tmp = json_decode($company->ModuleGroup);
        if ($found == '' && $tmp) $found = companyMultiModuleFound($tmp, $tableName, 'Group');
        $criteria = companyMultiModuleCriteria($found, $company, 'co');
        if ($criteria) return ' AND '.$criteria;
    }
}

if (!function_exists("returnDataField")) {
    function returnDataField($sort)
    {
        if ($sort == 'Name') $sort = 'data.Name';
        elseif ($sort == 'Code') $sort = 'data.Code';
        elseif (strpos($sort,'Name') < 1) $sort = 'data.'.$sort;
        return $sort;
    }
}
if (!function_exists("serverSideConfigField")) {
    function serverSideConfigField($field)
    {
        switch($field) {
            case 'Oid':
                // return ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid', 'f'=>'Oid'];
                return ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid'];
                break;
            case 'Code':
                return ['w'=> 180, 'r'=>1, 'h'=>0,  't'=>'text', 'n'=>'Code', 'd'=>'<<Auto>>',];
                break;
            case 'Date':
                return ['w'=> 250, 'r'=>1, 'h'=>0,  't'=>'date',  'n'=>'Date',];
                break;
            case 'Name':
                return ['w'=> 250, 'r'=>1, 'h'=>0, 't'=>'text', 'n'=>'Name'];
                break;
            case 'Currency':
                return ['w'=> 90, 'r'=>1, 't'=>'combo', 'n'=>'Currency', 'f'=>'c.Code',];
                break;
            case 'Item':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'autocomplete',  'n'=>'Item', 'f'=>'i.Name',];
                break;
            case 'Account':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'combo',  'n'=>'Account', 'f'=>'a.Name',];
                break;
            case 'BusinessPartner':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'combo',  'n'=>'BusinessPartner', 'f'=>'bp.Name',];
                break;
            case 'Status':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'combo',  'n'=>'Status', 'f'=>'s.Name', 'dis'=>true, 'd'=>'09128d8c-a364-4dc7-bd3b-a2d15d8fefc5'];
                break;
            case 'Warehouse':
                return ['w'=> 70,  'r'=>1, 'h'=>0,  't'=>'combo', 'n'=>'Warehouse', 'f'=>'w.Name',];
                break;
            case 'User':
                return ['w'=> 70,  'r'=>1, 'h'=>0,  't'=>'combo', 'n'=>'User', 'f'=>'u.Name',];
                break;
            case 'IsActive':
                return ['w'=> 120,  'r'=>1, 'h'=>0,  't'=>'bool', 'n'=>'IsActive',];
                break;
        }
    }
}

if (!function_exists("isJson")) {
    function isJson($string) {
        return ((is_string($string) &&
                (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }
}

if (!function_exists("serverSideSave")) {
    function serverSideSave($data,$request,$disabled = [])
    {
        $company = Auth::user()->CompanyObj ?: company();
        if ($disabled !=[]) array_merge($disabled, disabledFieldsForEdit());
        // if (!$excludeAutoNumber) if (isset($request->Code)) if ($request->Code == '<<Auto>>') $request->Code = now()->format('mdHis').str_random(2);
        foreach ($request as $field => $key) {
            if (in_array($field, $disabled)) continue;
            $data->{$field} = $request->{$field};
        }
        
        foreach ($data as $field => $key) {
            if ($field == 'Company'          && !isset($data->{$field})) $data->{$field} = $company->Oid;
            if ($field == 'Code'             && !isset($data->{$field})) $data->{$field} = now()->format('mdHis').str_random(2);
            if ($field == 'Date'             && !isset($data->{$field})) $data->{$field} = now()->addHours(company_timezone())->toDateTimeString();
            if ($field == 'ItemUnit'         && !isset($data->{$field})) $data->{$field} = $company->ItemUnit;
            if ($field == 'BusinessPartner'  && !isset($data->{$field})) $data->{$field} = $company->CustomerCash;
            if ($field == 'Status'           && !isset($data->{$field})) $data->{$field} = Status::entry()->first()->Oid;
            if ($field == 'Warehouse'        && !isset($data->{$field})) $data->{$field} = $company->POSDefaultWarehouse;
            if ($field == 'Currency'         && !isset($data->{$field})) $data->{$field} = $company->Currency;
            if ($field == 'Rate'             && !isset($data->{$field})) $data->{$field} = 1;
            if ($field == 'RateAmount'       && !isset($data->{$field})) $data->{$field} = 1;
        }        
        return $data;
    }
}

if (!function_exists("serverSideDefaultValue")) {
    function serverSideDefaultValue($data, $default = [])
    {
        $company = company();
        foreach ($default as $field) {
            if ($field == 'Date' && !isset($data->{$field})) $data->{$field} = now()->addHours(company_timezone())->toDateTimeString();
            if ($field == 'ItemUnit' && !isset($data->{$field})) $data->{$field} = $company->ItemUnit;
            if ($field == 'BusinessPartner' && !isset($data->{$field})) $data->{$field} = $company->CustomerCash;
            if ($field == 'Status' && !isset($data->{$field})) $data->{$field} = Status::entry()->first()->Oid;
            if ($field == 'Warehouse' && !isset($data->{$field})) $data->{$field} = $company->POSDefaultWarehouse;
            if ($field == 'Currency' && !isset($data->{$field})) $data->{$field} = $company->Currency;
            if ($field == 'Rate' && !isset($data->{$field})) $data->{$field} = 1;
            if ($field == 'RateAmount' && !isset($data->{$field})) $data->{$field} = 1;
        }
        if (!$data->Company) $data->Company = company()->Oid;
        return $data;
    }
}

if (!function_exists("defaultAction")) {
    function defaultAction($action)
    {
        return array_merge($action, [
            [
                "name" => "Edit",
                "icon" => "SettingsIcon",
                "type" => "edit",
                "action" => ""

            ],
            [
                "name" => "Delete",
                "icon" => "SettingsIcon",
                "type" => "delete",
                "action" => ""
            ],
        ]);
    }
}

if (!function_exists("serverSideDeleteDetail")) {
    function serverSideDeleteDetail($data,$request)
    {
        if ($data->count() != 0) {
            foreach ($data as $rowdb) {
                $found = false;               
                foreach ($request as $rowapi) {
                    if (isset($rowapi->Oid)) if ($rowdb->Oid == $rowapi->Oid) $found = true;
                }
                if (!$found) {
                    $detail = $data->where('Oid',$rowdb->Oid)->first();
                    $detail->delete();
                }
            }
        }
    }
}

if (!function_exists("serverSideSaveDetail")) {
    function serverSideSaveDetail($data,$request,$disabled = [])
    {
        if ($data->count() != 0) {
            foreach ($data as $rowdb) {
                $found = false;               
                foreach ($request as $rowapi) {
                    if (isset($rowapi->Oid)) if ($rowdb->Oid == $rowapi->Oid) $found = true;
                }
                if (!$found) {
                    $detail = $data->where('Oid',$rowdb->Oid)->first();
                    $detail->delete();
                }
            }
        }
        $details = [];
        if ($disabled !=[]) array_merge($disabled, disabledFieldsForEdit());
        foreach ($request as $row) {
            if (isset($row->Oid)) $detail = $data->where('Oid',$row->Oid)->first();
            else $detail = new $data;

            foreach ($row as $field => $key) {
                if (in_array($field, $disabled)) continue;
                $detail->{$field} = $row->{$field};
            }
            $detail->save();
        }
        return $details;
    }
}


if (!function_exists("addPaymentTermDueDate")) {
    function addPaymentTermDueDate($date, $paymentTerm)
    {
        $paymentTerm = \App\Core\Master\Entities\PaymentTerm::where('Oid',$paymentTerm)->first();
        if ($paymentTerm) return Carbon::parse($date)->addDays($paymentTerm->Interval)->toDateString();
        else return Carbon::parse($date)->addDays(30)->toDateString();
    }
}

if (!function_exists("getDefault")) {
    function getDefault($company)
    {        
        $user = Illuminate\Support\Facades\Auth::user();
        $company = $company ? \App\Core\Master\Entities\Company::find($company) : company();
        $result = new stdClass();
        if ($user) {
            $result->user = $user;
            $result->lang = $user->Lang;
            $result->cur = $user->CurrencyObj;
            $result->company = $company;
            $result->businesspartner = $user->BusinessPartnerObj ?: $company->CustomerCash;
        } else {
            $result->user = null;
            $result->lang = $company->Lang;
            $result->cur = $company->CurrencyObj;
            $result->company = $company;
            $result->businesspartner = $company->CustomerCash;
        }
        return $result;      
    }
}


if (!function_exists("getPriceMethodItemContent")) {    
    function getPriceMethodItemContent($default, $itemContent) {
        $dataPrices = [];
        $query = "SELECT c.Oid, c.Level, c.CompanySupplier CompanyFrom, c.Company CompanyTo, bp.Oid BusinessPartnerCustomer, 
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAddMethod ELSE c.SalesAddMethod END AS Method,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAddAmount1 ELSE c.SalesAddAmount1 END AS Amount_1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAddAmount2 ELSE c.SalesAddAmount2 END AS Amount_2,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd1Method ELSE c.SalesAdd1Method END AS Method1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd1Amount1 ELSE c.SalesAdd1Amount1 END AS Amount1_1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd1Amount2 ELSE c.SalesAdd1Amount2 END AS Amount1_2,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd2Method ELSE c.SalesAdd2Method END AS Method2,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd2Amount1 ELSE c.SalesAdd2Amount1 END AS Amount2_1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd2Amount2 ELSE c.SalesAdd2Amount2 END AS Amount2_2,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd3Method ELSE c.SalesAdd3Method END AS Method3,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd3Amount1 ELSE c.SalesAdd3Amount1 END AS Amount3_1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd3Amount2 ELSE c.SalesAdd3Amount2 END AS Amount3_2,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd4Method ELSE c.SalesAdd4Method END AS Method4,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd4Amount1 ELSE c.SalesAdd4Amount1 END AS Amount4_1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd4Amount2 ELSE c.SalesAdd4Amount2 END AS Amount4_2,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd5Method ELSE c.SalesAdd5Method END AS Method5,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd5Amount1 ELSE c.SalesAdd5Amount1 END AS Amount5_1,
                CASE WHEN c.IsUsingPriceMethod THEN cit.SalesAdd5Amount2 ELSE c.SalesAdd5Amount2 END AS Amount5_2,
                bp.SalesPriceLevel PriceLevelForPrevious
            FROM companyitemcontent c 
            LEFT OUTER JOIN companyitemtype cit ON c.ItemType = cit.ItemType AND c.Company = cit.Company 
            LEFT OUTER JOIN mstbusinesspartner bp ON bp.CompanyAccount = c.Company AND bp.Company = c.CompanySupplier";
        $companyItemContent = DB::select($query." WHERE c.GCRecord IS NULL AND c.ItemContent = '{$itemContent->Oid}' AND c.Item IS NULL AND c.Company = '{$default->company->Oid}'");
        if ($companyItemContent) {
            $companyItemContent = $companyItemContent[0];
            $companyLevel = $companyItemContent->Level;
            $parent = $companyItemContent->Oid;
            $priceLevelPrevious = $default->user ? $default->user->getSalesPriceLevel() : '';
            $data = DB::select($query." WHERE c.ItemContent = '{$itemContent->Oid}' AND c.Level <= {$companyLevel}");
            for($i = $companyLevel; $i >= 1; $i--) {
                foreach($data as $row) {
                    if ($row->Level == $i && $row->Oid == $parent) {
                        $row->PriceLevel = $priceLevelPrevious;
                        $priceLevelPrevious = $row->PriceLevelForPrevious;
                        // unset($row->PriceLevelForPrevious);
                        $dataPrices[] = $row;
                        $parent = $row->Oid;
                    }
                }
            }
        }
        return $dataPrices;        
    }
}
   
if (!function_exists("getPriceMethodItemType")) {    
    function getPriceMethodItemType($default, $itemType) {
        $dataPrices = [];
        $query = "SELECT cit.Oid, cit.Level, cit.CompanySupplier CompanyFrom, cit.Company CompanyTo, bp.Oid BusinessPartnerCustomer, 
                cit.SalesAddMethod  Method,
                cit.SalesAddAmount1  Amount_1,
                cit.SalesAddAmount2  Amount_2,
                cit.SalesAdd1Method Method1,
                cit.SalesAdd1Amount1 Amount1_1,
                cit.SalesAdd1Amount2 Amount1_2,
                cit.SalesAdd2Method Method2,
                cit.SalesAdd2Amount1 Amount2_1,
                cit.SalesAdd2Amount2 Amount2_2,
                cit.SalesAdd3Method Method3,
                cit.SalesAdd3Amount1 Amount3_1,
                cit.SalesAdd3Amount2 Amount3_2,
                cit.SalesAdd4Method Method4,
                cit.SalesAdd4Amount1 Amount4_1,
                cit.SalesAdd4Amount2 Amount4_2,
                cit.SalesAdd5Method Method5,
                cit.SalesAdd5Amount1 Amount5_1,
                cit.SalesAdd5Amount2 Amount5_2,
                bp.SalesPriceLevel PriceLevelForPrevious
                FROM companyitemtype cit 
                LEFT OUTER JOIN sysitemtype ity ON ity.Oid = cit.ItemType
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.CompanyAccount = cit.Company AND bp.Company = cit.CompanySupplier";
        $companyItemType = DB::select($query." WHERE cit.GCRecord IS NULL AND ity.Code = '{$itemType}' AND cit.Company = '{$default->company->Oid}'");
        
        if ($companyItemType) {
            $companyItemType = $companyItemType[0];
            $companyLevel = $companyItemType->Level;
            $parent = $companyItemType->Oid;
            $priceLevelPrevious = $default->user ? $default->user->getSalesPriceLevel() : '';

            $data = DB::select($query." WHERE ity.Code = '{$itemType}' AND cit.Level <= {$companyLevel}");
            for($i = $companyLevel; $i >= 1; $i--) {
                foreach($data as $row) {
                    if ($row->Level == $i && $row->Oid == $parent) {
                        $row->PriceLevel = $priceLevelPrevious;
                        $priceLevelPrevious = $row->PriceLevelForPrevious;
                        // unset($row->PriceLevelForPrevious);
                        $dataPrices[] = $row;
                        $parent = $row->Oid;
                    }
                }
            }
        }
        return $dataPrices;        
    }
}

if (!function_exists("getPriceMethodItem")) {    
    function getPriceMethodItem($default, $itemContent) {
        $query = "SELECT c.Oid, c.Item, c.SalesAmount, c.SalesAmount1, c.SalesAmount2, c.SalesAmount3, c.SalesAmount4, c.SalesAmount5
            FROM companyitemcontent c 
            LEFT OUTER JOIN companyitemtype cit ON c.ItemType = cit.ItemType AND c.Company = cit.Company 
            LEFT OUTER JOIN mstbusinesspartner bp ON bp.CompanyAccount = c.Company AND bp.Company = c.CompanySupplier";
        $data = DB::select($query." WHERE c.ItemContent = '{$itemContent->Oid}' AND c.Item IS NOT NULL AND c.Company = '{$default->company->Oid}'");
        return $data;        
    }
}

if (!function_exists("calcPriceMethod")) {    
    function calcPriceMethod($method, $amt, $isAmountTotal = true) {
        if ($amt < 1) return 0;
        switch ($method->Method) {
            case "Amount": return ($isAmountTotal ? $amt : 0) + $method->Amount_1;
            case "Percentage": return ($isAmountTotal ? $amt : 0) + (($amt * $method->Amount_1)/100);
            case "PercentageAmount": return ($isAmountTotal ? $amt : 0) + ((($amt * $method->Amount_1)/100) + $method->Amount_2);
            case "AmountPercentage": return ($isAmountTotal ? $amt : 0) + $method->Amount_1 + ((($amt + $method->Amount_1) * $method->Amount_2)/100);
            default: return 0;
        }
        return 0;
    }
}

if (!function_exists("getObj")) {    
    function getObj($data, $field = null) {
        if (!$data) return [
            'Oid' => null,
            'Name' => null,
        ];
        if (isset($field)) $field = $data->{$field};
        else $field = $data->Name.' - '.$data->Code ?: $data->Code;
        return [
            'Oid' => $data->Oid ?: null,
            'Name' => $field,
        ];
    }
}