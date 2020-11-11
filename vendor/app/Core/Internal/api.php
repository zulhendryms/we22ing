<?php

// Route::post('/auto_number', 'AutoNumberController@index');
Route::put('/locale_contents', 'LocaleContentController@store');
Route::middleware('auth:api')->group(function () {

    Route::post('/maintenance/clear_log', 'MaintenanceController@clearLog');
    Route::post('/maintenance/remove_feature_form', 'MaintenanceController@removeFeatureForm');

    Route::post('/encrypt', 'EncryptController@encrypt');
    Route::post('/decrypt', 'EncryptController@decrypt');

    Route::post('/encrypt_salted', 'EncryptController@encryptSalted');
    Route::post('/decrypt_salted', 'EncryptController@decryptSalted');

    Route::post('/upload', 'UploadFileController@upload')->name('Core\Internal::upload');

    Route::post('/notification', 'SendNotificationController@store');
});