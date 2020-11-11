<?php

Route::prefix('v1/ferrypricing')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'FerryPricingController@list')->name('AdminApi\FerryPricing::list');
    Route::get('/config', 'FerryPricingController@config')->name('AdminApi\FerryPricing::config');
    Route::get('/', 'FerryPricingController@index')->name('AdminApi\FerryPricing::index');
    Route::get('/search', 'FerryPricingController@updateSearch')->name('AdminApi\FerryPricing::updatesearch');
    Route::post('/update', 'FerryPricingController@updateProcess')->name('AdminApi\FerryPricing::updateprocess');
    Route::get('/{data}', 'FerryPricingController@show')->name('AdminApi\FerryPricing::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'FerryPricingController@save')->name('AdminApi\FerryPricing::save');
    Route::put('/edit/{Oid}', 'FerryPricingController@edit')->name('AdminApi\FerryPricing::edit');
    Route::delete('/{data}', 'FerryPricingController@destroy')->name('AdminApi\FerryPricing::destroy');    
});

Route::prefix('v1/ferryschedule')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'FerryScheduleController@list')->name('AdminApi\FerrySchedule::list');
    Route::get('/config', 'FerryScheduleController@config')->name('AdminApi\FerrySchedule::config');
    Route::get('/', 'FerryScheduleController@index')->name('AdminApi\FerrySchedule::index');
    Route::get('/search', 'FerryScheduleController@generateSearch')->name('AdminApi\FerrySchedule::generatesearch');
    Route::get('/searchqty', 'FerryScheduleController@searchQty')->name('AdminApi\FerrySchedule::searchQty');
    Route::post('/generate', 'FerryScheduleController@generateProcess')->name('AdminApi\FerrySchedule::generateprocess');
    Route::post('/updateqty', 'FerryScheduleController@updateQty')->name('AdminApi\FerrySchedule::updateQty');
    Route::get('/{data}', 'FerryScheduleController@show')->name('AdminApi\FerrySchedule::show');
    Route::match(['put', 'post'], '/{Oid?}', 'FerryScheduleController@save')->name('AdminApi\FerrySchedule::save');
    Route::delete('/{data}', 'FerryScheduleController@destroy')->name('AdminApi\FerrySchedule::destroy');
});

Route::prefix('v1/ferryroute')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'FerryRouteController@list')->name('AdminApi\FerryRoute::list');
    Route::get('/config', 'FerryRouteController@config')->name('AdminApi\FerryRoute::config');
    Route::get('/', 'FerryRouteController@index')->name('AdminApi\FerryRoute::index');
    Route::get('/presearch', 'FerryRouteController@presearch')->name('AdminApi\FerryRoute::presearch');
    Route::get('/{data}', 'FerryRouteController@show')->name('AdminApi\FerryRoute::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'FerryRouteController@save')->name('AdminApi\FerryRoute::save');
    Route::put('/edit/{Oid}', 'FerryRouteController@edit')->name('AdminApi\FerryRoute::edit');
    Route::delete('/{data}', 'FerryRouteController@destroy')->name('AdminApi\FerryRoute::destroy');    
});