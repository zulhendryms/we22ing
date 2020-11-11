<?php

Route::prefix('v1/development')->middleware(['cors'])->group(function () {
    Route::match(['get', 'post'], 'class', 'ServerCRUDController@listClass')->name('AdminApi\Login::listClass');
    Route::match(['get', 'post'], 'test/removenotification', 'TestingController@testRemoveNotification');
    Route::match(['get', 'post'], 'test/onesignal', 'TestingNotificationController@testOneSignal');
    Route::match(['get', 'post'], 'test/socket', 'TestingNotificationController@testSocketIO');
});

Route::prefix('v1/autocomplete')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/account', 'ComboAutoCompleteController@account');
    Route::get('/itemgroup', 'ComboAutoCompleteController@itemgroup');
    Route::get('/user', 'ComboAutoCompleteController@user');
    Route::get('/businesspartner', 'ComboAutoCompleteController@businesspartner');
    Route::get('/itemcontent', 'ComboAutoCompleteController@itemcontent');
    Route::get('/item', 'ComboAutoCompleteController@item');
    Route::get('/country', 'ComboAutoCompleteController@country');
    Route::get('/travelflightnumber', 'ComboAutoCompleteController@travelflightnumber');
    Route::get('/travelhotelroomtype', 'ComboAutoCompleteController@travelhotelroomtype');
    Route::get('/truckingaddress', 'ComboAutoCompleteController@truckingaddress');
    Route::get('/productionpriceprocess', 'ComboAutoCompleteController@productionpriceprocess');
});

Route::prefix('v1/combosource')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/item', 'ComboSourceController@item');
    Route::get('/paymentmethod', 'ComboSourceController@paymentmethod');
    Route::get('/field', 'ComboSourceController@field');
    Route::get('/company', 'ComboSourceController@company');
    Route::get('/department', 'ComboSourceController@department');
    Route::get('/businesspartnergroup', 'ComboSourceController@businesspartnergroup');
    Route::get('/itemgroup', 'ComboSourceController@itemgroup');
    Route::get('/traveltemplatenote', 'ComboSourceController@traveltemplatenote');
});

Route::prefix('v1/master')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/{module}/config', 'CRUDGlobalController@config');
    Route::get('/{module}/list', 'CRUDGlobalController@list');
    Route::get('/{module}/dashboard', 'CRUDGlobalController@dashboard');
    Route::get('/{module}/presearch', 'CRUDGlobalController@presearch');
    Route::get('/{module}', 'CRUDGlobalController@index');
    Route::get('/{module}/getCombo', 'CRUDGlobalController@index');
    Route::get('/{module}/{Oid}', 'CRUDGlobalController@show');
    Route::match(['put', 'post'], '/{module}/{Oid?}', 'CRUDGlobalController@save');
    Route::delete('/{module}/{Oid}', 'CRUDGlobalController@destroy');
});


Route::prefix('v1/data')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/{module}/config', 'CRUDGlobalController@config');
    Route::get('/{module}/list', 'CRUDGlobalController@list');
    Route::get('/{module}/dashboard', 'CRUDGlobalController@dashboard');
    Route::get('/{module}/presearch', 'CRUDGlobalController@presearch');
    Route::get('/{module}', 'CRUDGlobalController@index');
    Route::get('/{module}/getCombo', 'CRUDGlobalController@index');
    Route::get('/{module}/{Oid}', 'CRUDGlobalController@show');
    Route::match(['put', 'post'], '/{module}/{Oid?}', 'CRUDGlobalController@save');
    Route::delete('/{module}/{Oid}', 'CRUDGlobalController@destroy');
    Route::post('/sendtochat', 'CRUDDevelopmentController@sendToChat');
});

Route::prefix('v1/report')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/generate', 'ReportGeneratorController@report')->name('AdminApi\PublicReportController::report');
    Route::get('/field/{table}', 'ReportGeneratorController@reportFields')->name('AdminApi\PublicReportController::reportFields');
});

Route::prefix('v1/development')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/vuemaster', 'ServerCRUDController@generateVueMaster');
    Route::get('/vuetransaction', 'ServerCRUDController@generateVueTransaction');
    Route::get('/vueview', 'ServerCRUDController@generateVueView');
    Route::post('/convert', 'ServerCRUDController@convertJSon');
});

Route::prefix('v1/development/table')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'DevelopmentTableController@field');
    Route::get('/list', 'DevelopmentTableController@list');
    Route::get('/autocomplete', 'DevelopmentTableController@autocomplete');
    Route::get('/presearch', 'DevelopmentTableController@presearch');
    Route::get('/config', 'DevelopmentTableController@config');
    Route::get('/', 'DevelopmentTableController@index');
    Route::get('/{data}', 'DevelopmentTableController@show');
    Route::delete('/{data}', 'DevelopmentTableController@destroy');
});

Route::prefix('v1/development/field')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'DevelopmentFieldController@field');
    Route::get('/list', 'DevelopmentFieldController@list');
    Route::get('/presearch', 'DevelopmentFieldController@presearch');
    Route::get('/config', 'DevelopmentFieldController@config');
    Route::get('/', 'DevelopmentFieldController@index');
    Route::get('/{data}', 'DevelopmentFieldController@show');
    Route::delete('/{data}', 'DevelopmentFieldController@destroy');
});

Route::prefix('v1/development/dashboard')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'DevelopmentDashBoardController@list');
    Route::get('/presearch', 'DevelopmentDashBoardController@presearch');
    Route::get('/config', 'DevelopmentDashBoardController@config');
    Route::get('/', 'DevelopmentDashBoardController@index');
    Route::get('/{data}', 'DevelopmentDashBoardController@show');
    Route::delete('/{data}', 'DevelopmentDashBoardController@destroy');
});

Route::prefix('v1/development/menu')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'DevelopmentMenuController@field');
    Route::get('/list', 'DevelopmentMenuController@list');
    Route::get('/presearch', 'DevelopmentMenuController@presearch');
    Route::get('/config', 'DevelopmentMenuController@config');
    Route::get('/', 'DevelopmentMenuController@index');
    Route::get('/{data}', 'DevelopmentMenuController@show');
    Route::delete('/{data}', 'DevelopmentMenuController@destroy');
});

Route::prefix('v1/development/apitoken')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/log/{Oid}', 'DevelopmentAPITokenController@listLog');
    Route::get('/list', 'DevelopmentAPITokenController@list');
    Route::get('/presearch', 'DevelopmentAPITokenController@presearch');
    Route::get('/config', 'DevelopmentAPITokenController@config');
    Route::get('/', 'DevelopmentAPITokenController@index');
    Route::get('/{data}', 'DevelopmentAPITokenController@show');
    Route::delete('/{data}', 'DevelopmentAPITokenController@destroy');
});

Route::prefix('v1/development/apilist')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'DevelopmentAPIListController@field');
    Route::get('/list', 'DevelopmentAPIListController@list');
    Route::get('/presearch', 'DevelopmentAPIListController@presearch');
    Route::get('/config', 'DevelopmentAPIListController@config');
    Route::get('/', 'DevelopmentAPIListController@index');
    Route::get('/{data}', 'DevelopmentAPIListController@show');
    Route::delete('/{data}', 'DevelopmentAPIListController@destroy');
});

Route::prefix('v1/development/reportparent')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'DevelopmentReportParentController@field');
    Route::get('/list', 'DevelopmentReportParentController@list');
    Route::get('/presearch', 'DevelopmentReportParentController@presearch');
    Route::get('/config', 'DevelopmentReportParentController@config');
    Route::get('/', 'DevelopmentReportParentController@index');
    Route::get('/{data}', 'DevelopmentReportParentController@show');
    Route::delete('/{data}', 'DevelopmentReportParentController@destroy');
});

Route::prefix('v1/development/reportdetail')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'DevelopmentReportDetailController@field');
    Route::get('/list', 'DevelopmentReportDetailController@list');
    Route::get('/presearch', 'DevelopmentReportDetailController@presearch');
    Route::get('/config', 'DevelopmentReportDetailController@config');
    Route::get('/', 'DevelopmentReportDetailController@index');
    Route::get('/{data}', 'DevelopmentReportDetailController@show');
    Route::delete('/{data}', 'DevelopmentReportDetailController@destroy');
});