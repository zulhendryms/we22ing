<?php

// Route::prefix('v1/attraction')->middleware(['cors', 'auth:api'])->group(function () {
//     Route::get('/config', 'AttractionController@config')->name('AdminApi\Attraction::config');  
//     Route::get('/list', 'AttractionController@list')->name('AdminApi\Attraction::list');    
//     Route::get('/', 'AttractionController@index')->name('AdminApi\Attraction::index');
//     Route::get('/{data}', 'AttractionController@show')->name('AdminApi\Attraction::show');
//     Route::match(['put', 'post'], '/{Oid?}', 'AttractionController@save')->name('AdminApi\Attraction::save');
// });