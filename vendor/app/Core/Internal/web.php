<?php

Route::get('/timezone', 'SetTimezoneController@index')->name('Core\Internal::timezone');
// Route::get('/device', 'CreateDeviceController@index')->name('Core\Internal::device');
Route::post('/onesignal', 'SetOneSignalTokenController@index')->name('Core\Internal::onesignal');