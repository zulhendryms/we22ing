<?php

Route::prefix('v1/apitudehotel')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'HotelController@list')->name('AdminApi\Hotel::index');
    Route::get('/config', 'HotelController@config')->name('AdminApi\Hotel::index');
    Route::get('/', 'HotelController@index')->name('AdminApi\Hotel::index');
    Route::get('/listitemecommerce', 'HotelController@listitemecommerce')->name('AdminApi\Hotel::listitemecommerce');
    Route::post('/saveecommerce', 'HotelController@saveitemecommerce')->name('AdminApi\Hotel::saveitemecommerce');
    Route::get('/{data}', 'HotelController@show')->name('AdminApi\Hotel::show');
    Route::match(['put', 'post'], '/{Oid?}', 'HotelController@save')->name('AdminApi\Hotel::save');
    Route::post('/{data}/delete', 'HotelController@changeIsActive')->name('AdminApi\Hotel::changeIsActive');
});