<?php

Route::prefix('v1/autonumbersetup')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'AutoNumberSetupController@list')->name('AdminApi\AutonumberSetup::list');
    Route::get('/config', 'AutoNumberSetupController@config')->name('AdminApi\AutonumberSetup::config');
    Route::get('/', 'AutoNumberSetupController@index')->name('AdminApi\AutonumberSetup::index');
    Route::get('/{data}', 'AutoNumberSetupController@show')->name('AdminApi\AutonumberSetup::show');
    Route::match(['put', 'post'], '/{Oid?}', 'AutoNumberSetupController@save')->name('AdminApi\AutonumberSetup::save');
    Route::delete('/{data}', 'AutoNumberSetupController@destroy')->name('AdminApi\AutonumberSetup::destroy');
});

Route::prefix('v1/emailinbox')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/download', 'EmailInboxController@downloadEmailMessage')->name('AdminApi\EmailInbox::downloadEmailMessage');
});

?>