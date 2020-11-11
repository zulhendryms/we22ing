<?php

namespace App\Core\Internal\Services;

use App\Core\Internal\Entities\AuditedObjectWeakReference;
use App\Core\Internal\Entities\AutoNumberSetup;
use App\Core\Master\Entities\EmployeePosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AutoNumberService 
{
    public function generate($object, $tableQuery, $fieldName = 'Code')
    {   
        // type = 0 IsDefault
        // type = 1 IsPrefixSuffix
        // type = 2 IsDirect

        $logger = $fieldName == 'RequestCode';
        // if($logger) dd($tableQuery.' '.$fieldName);
        $setup = AutoNumberSetup::where('TableQuery',$tableQuery)->where('FieldName',$fieldName)->first();
        // if($logger) dd($setup);
        if (!$setup) return now()->format('mdHis').str_random(2);
        $class = config('autonumber.'.$tableQuery);
        // if($logger) dd($class);
        if (is_null($class)) return now()->format('mdHis').str_random(2);
        throw_if(is_null($class), new ModelNotFoundException("Model for $tableQuery not found"));
        // $check = $class::whereIn($fieldName,['<<AutoGenerate>>','<<Auto>>'])->where('Oid','!=',$object->Oid)->get();
        // foreach($check as $row) {
        //     $row->{$fieldName} = now()->format('dHismy').str_random(2);
        //     $row->save();
        // }
        if (!in_array($object->{$fieldName},['<<AutoGenerate>>','<<Auto>>'])) return $object->{$fieldName};
        $prefix = isset($setup->Prefix) && $setup->Type != 0 ? $this->parseExpression($object, $setup->Prefix) : '';
        $suffix = isset($setup->Suffix) && $setup->Type != 0 ? $this->parseExpression($object, $setup->Suffix) : '';

        $qFrom = " FROM ".$tableQuery." WHERE Company = '{$object->Company}' ";
        $qField = " CONVERT(SUBSTRING(".$setup->FieldName.", ".(strlen($prefix)+1).",".$setup->Digit."), SIGNED INTEGER) ";
        $qWhereNumber = " AND (concat('',".$qField." * 1) = ".$qField.") = 1 ";
        $qOrder = "ORDER BY ".$qField." DESC LIMIT 1";

        $number = 1;
        // if($logger) dd('def:'.$setup->IsDefault.'; pref:'.$setup->IsPrefixSuffix.'; direct:'.$setup->IsDirect.' ;prefix:'.$prefix.'; suffix:'.$suffix);
        if ($setup->IsDefault) {
            $query = "SELECT " . $qField . " AS Number " . $qFrom . $qWhereNumber . $qOrder;
            $number = DB::select($query);
            if (count($number) == 0) {
                $number = 0;
            } else {
                $number = $number[0]->Number;
            };
        } else if ($setup->IsPrefixSuffix) {
            if (!empty($prefix)) $qFrom .= " AND {$setup->FieldName} LIKE '{$prefix}%'";
            if (!empty($suffix)) $qFrom .= " AND {$setup->FieldName} LIKE '%{$suffix}'";
            $query = "SELECT " . $qField . " AS Number " . $qFrom . $qWhereNumber . $qOrder;
            $number = DB::select($query);
            if (count($number) == 0) {
                $number = 0;
            } else {
                $number = $number[0]->Number;
            }
        } else if ($setup->IsDirect) {
            if (!empty($prefix)) $qFrom .= " AND {$setup->FieldName} LIKE '{$prefix}%'";
            if (!empty($suffix)) $qFrom .= " AND {$setup->FieldName} LIKE '%{$suffix}'";
            $query = "SELECT " . $qField . " AS Number " . $qFrom . $qWhereNumber . $qOrder;
            $number = DB::select($query);
            if (count($number) == 0) {
                $number = 0;
            } else {
                $number = $number[0]->Number;
            }
            if (empty($number)) {
                $number = 1;
            }
            $number = chr(64 + $number);
        }

        if (!$setup->IsDirect) {
            $number = $number + 1;
            $number = str_pad($number, $setup->Digit, '0', STR_PAD_LEFT);
        }
        $value = $prefix.$number.$suffix;
        // if($logger) dd($value);
        return $value;
    }

    protected function parseExpression($object, $exp)
    {
        $query = "";
        $index = 0;
        if (empty($exp)) return "";
        while ($index < strlen($exp)) {
            $value = "";
            $startIndex = strpos($exp, '[', $index);
            if ($startIndex === false) {
                $value = "'".substr($exp, $index)."'";
                $index += strlen($value);
            } else {
                $endIndex = strpos($exp, ']', $startIndex);
                if ($index != $startIndex) {
                    $value = "'".substr($exp, $index, $startIndex - $index)."'";
                    $index = $startIndex;
                } else {
                    $propertyName = substr($exp, $startIndex + 1, $endIndex - ($index == 0 ? 1 : $index + 1));
                    if (strpos($propertyName, '(') !== false && strpos($propertyName, ')') !== false) {
                        if (strpos($propertyName, '@')) {
                            $si = strpos($propertyName, '@') + 1;
                            $ei = strpos($propertyName, ',', $si) - $si;
                            $prop = substr($propertyName, strpos($propertyName, '@') + 1, $ei);
                            $value = $this->getValue($object, $prop);
                            if (strtotime($value)) {
                                $value = Carbon::parse($value)->toDateString();
                            }
                            $value = str_replace("@".$prop, "'".$value."'", $propertyName);
                        } else {
                            $value = $propertyName;
                        }
                    } else {
                        $value = $this->getValue($object, $propertyName);
                        if (strtotime($value)) {
                            $value = Carbon::parse($value)->toDateString();
                        }
                        $value = "'".$value."'";
                    }
                    $index = $endIndex + 1;
                }
            }
            if (!empty($query)) $query.=",";
            $query .= $value;
        }
        $query = "CONCAT(".$query.") AS Value";
        $result = "";
        $row = DB::select("SELECT ".$query);
        return strtoupper($row[0]->Value);
    }

    protected function getValue($obj, $path)
    {
        $value = clone $obj;
        $propertyNames = explode('.', $path);
        foreach ($propertyNames as $name) {
            $value = $value->{$name};
        }
        return $value;
    }
}
// if (!empty($prefix)) $qFrom .= " AND SUBSTRING(".$setup->FieldName.",1,".(strlen($prefix)).")= '".$prefix."'";
// if (!empty($suffix)) $qFrom .= " AND SUBSTRING(".$setup->FieldName.",".(strlen($prefix)+$setup->Digit+1).")= '".$suffix."'";