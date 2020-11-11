<?php

namespace App\Core\Internal\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller 
{
    
    public function clearLog(Request $request) 
    {
        DB::table('auditdataitempersistent')->delete();
        DB::table('auditedobjectweakreference')->delete();
        DB::table('xpweakreference')->delete();
        return "";
    }

    public function removeFeatureForm(Request $request)
    {
        $request->validate([
            'Form' => 'required'
        ]);
        $form = FeatureForm::find($request->input('Form'));
        DB::table('sysfeatureformfeatureforms_sysfeaturefeatures')->where('FeatureForms', $form->Oid)->delete();
        DB::table('typepermissionobject')->whereIn('Oid',
            DB::table('permissionpolicytypepermissionsobject')->select('Oid')
            ->where('TargetType', $form->Name)->get()
        )->delete();
        DB::table('permissionpolicytypepermissionsobject')->where('TargetType', $form->Name)->delete();
        DB::table('sysfeatureform')->where('Oid', $form->Oid)->delete();
        return "";
    }
}