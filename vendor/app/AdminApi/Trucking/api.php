<?php

Route::prefix('v1/truckingtransactionfuel')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'TruckingTransactionFuelController@presearch')->name('AdminApi\truckingtransactionfuel::presearch');
    Route::get('/list', 'TruckingTransactionFuelController@list')->name('AdminApi\truckingtransactionfuel::list');
    Route::get('/config', 'TruckingTransactionFuelController@config')->name('AdminApi\truckingtransactionfuel::config');
    Route::get('/', 'TruckingTransactionFuelController@index')->name('AdminApi\truckingtransactionfuel::index');
    Route::get('/{data}', 'TruckingTransactionFuelController@show')->name('AdminApi\truckingtransactionfuel::show');
    Route::match(['put', 'post'], '/{Oid?}', 'TruckingTransactionFuelController@save')->name('AdminApi\truckingtransactionfuel::save');
    Route::delete('/{data}', 'TruckingTransactionFuelController@destroy')->name('AdminApi\truckingtransactionfuel::destroy');
    Route::post('/{data}/post', 'TruckingTransactionFuelController@statusPost');
    Route::post('/{data}/unpost', 'TruckingTransactionFuelController@statusUnpost');
    Route::post('/{data}/entry', 'TruckingTransactionFuelController@statusEntry');
    Route::post('/{data}/cancel', 'TruckingTransactionFuelController@statusCancel');
});

Route::prefix('v1/truckingtrackinglog')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'TruckingTrackingLogController@list')->name('AdminApi\TruckingTrackingLog::list');
    Route::post('/import', 'TruckingTrackingLogController@import')->name('AdminApi\TruckingTrackingLog::import');
    Route::get('/config', 'TruckingTrackingLogController@config')->name('AdminApi\TruckingTrackingLog::config');
    Route::get('/', 'TruckingTrackingLogController@index')->name('AdminApi\TruckingTrackingLog::index');
    Route::get('/{data}', 'TruckingTrackingLogController@show')->name('AdminApi\TruckingTrackingLog::show');
    Route::match(['put', 'post'], '/{Oid?}', 'TruckingTrackingLogController@save')->name('AdminApi\TruckingTrackingLog::save');
    Route::delete('/{data}', 'TruckingTrackingLogController@destroy')->name('AdminApi\TruckingTrackingLog::destroy');
});

Route::prefix('v1/truckingworkorder')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/create', 'TruckingWorkOrderController@create');
    Route::get('/listcontroller', 'TruckingWorkOrderController@listController');
    Route::post('/driver', 'TruckingWorkOrderController@driverAssign');
    Route::post('/reassign', 'TruckingWorkOrderController@driverReassign');
    Route::post('/start', 'TruckingWorkOrderController@statusStarted');
    Route::post('/reject', 'TruckingWorkOrderController@statusReject');
    Route::post('/end', 'TruckingWorkOrderController@statusEnded');
    Route::post('/verify', 'TruckingWorkOrderController@statusVerify');
    Route::post('/completed', 'TruckingWorkOrderController@statusCompleted');
    Route::get('/list', 'TruckingWorkOrderController@list');
    Route::get('/config', 'TruckingWorkOrderController@config');
    Route::get('/listlastposition', 'TruckingWorkOrderController@listlastposition');
    Route::post('/recreateorder', 'TruckingWorkOrderController@recreateOrder');
    Route::get('/', 'TruckingWorkOrderController@index');
    Route::get('/{data}', 'TruckingWorkOrderController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'TruckingWorkOrderController@save');
    Route::delete('/{data}', 'TruckingWorkOrderController@destroy');
});

