<?php

Route::prefix('v1/development')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('globaldata', 'DevelopmentController@globalData');
    Route::post('generateautonumber', 'DevelopmentController@generateAutoNumber')->name('AdminApi\Login::generateAutoNumber');
    Route::post('generateautonumber2', 'DevelopmentController@generateAutoNumber2')->name('AdminApi\Login::generateAutoNumber');
    // Route::post('class', 'DevelopmentController@listClass')->name('AdminApi\Login::listClass');
});

Route::prefix('v1/cronjob')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('config', 'CronJobController@config');
    Route::match(['put', 'post'], 'save/{Oid?}', 'CronJobController@save');
    Route::get('list', 'CronJobController@list');
    Route::get('show/{data}', 'CronJobController@show');
});

Route::prefix('v1/businesspartnerrole')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'BusinessPartnerRoleController@index')->name('AdminApi\BusinessPartnerRole::index');
    Route::get('/{data}', 'BusinessPartnerRoleController@show')->name('AdminApi\BusinessPartnerRole::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'BusinessPartnerRoleController@save')->name('AdminApi\BusinessPartnerRole::save');
    // Route::delete('/{data}', 'BusinessPartnerRoleController@destroy')->name('AdminApi\BusinessPartnerRole::destroy');
});

Route::prefix('v1/companytype')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'CompanyTypeController@index')->name('AdminApi\CompanyType::index');
    Route::get('/{data}', 'CompanyTypeController@show')->name('AdminApi\CompanyType::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'CompanyTypeController@save')->name('AdminApi\CompanyType::save');
    // Route::delete('/{data}', 'CompanyTypeController@destroy')->name('AdminApi\CompanyType::destroy');
});

Route::prefix('v1/log')->middleware(['cors'])->group(function () {
    Route::post('/create', 'LogController@create')->name('AdminApi\LogController::create');
});

Route::prefix('v1/global')->middleware(['cors'])->group(function () {
    Route::get('/language', 'GlobalController@language')->name('AdminApi\Global::language');
});

Route::prefix('v1/country')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'CountryController@config')->name('AdminApi\Country::config');
    Route::get('/list', 'CountryController@list')->name('AdminApi\Country::list');
    Route::get('/', 'CountryController@index')->name('AdminApi\Country::index');
    Route::get('/{data}', 'CountryController@show')->name('AdminApi\Country::show');
    Route::match(['put', 'post'], '/{Oid?}', 'CountryController@save')->name('AdminApi\Country::save');
    Route::delete('/{data}', 'CountryController@destroy')->name('AdminApi\Country::destroy');
});

Route::prefix('v1/itemmethod')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'ItemMethodController@index')->name('AdminApi\ItemMethod::index');
    Route::get('/{data}', 'ItemMethodController@show')->name('AdminApi\ItemMethod::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'ItemMethodController@save')->name('AdminApi\ItemMethod::save');
    // Route::delete('/{data}', 'ItemMethodController@destroy')->name('AdminApi\ItemMethod::destroy');
});

Route::prefix('v1/journaltype')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'JournalTypeController@index')->name('AdminApi\JournalType::index');
    Route::get('/{data}', 'JournalTypeController@show')->name('AdminApi\JournalType::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'JournalTypeController@save')->name('AdminApi\JournalType::save');
    // Route::delete('/{data}', 'JournalTypeController@destroy')->name('AdminApi\JournalType::destroy');
});

Route::prefix('v1/pointofsaletype')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'PointOfSaleTypeController@index')->name('AdminApi\PointOfSaleType::index');
    Route::get('/{data}', 'PointOfSaleTypeController@show')->name('AdminApi\PointOfSaleType::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'PointOfSaleTypeController@save')->name('AdminApi\PointOfSaleType::save');
    // Route::delete('/{data}', 'PointOfSaleTypeController@destroy')->name('AdminApi\PointOfSaleType::destroy');
});

Route::prefix('v1/status')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'StatusController@index')->name('AdminApi\Status::index');
    Route::get('/{data}', 'StatusController@show')->name('AdminApi\Status::show');
    Route::match(['put', 'post'], '/{Oid?}', 'StatusController@save')->name('AdminApi\Status::save');
    Route::delete('/{data}', 'StatusController@destroy')->name('AdminApi\Status::destroy');
});

Route::prefix('v1/pricemethod')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'PriceMethodController@index')->name('AdminApi\PriceMethod::index');
    Route::get('/{data}', 'PriceMethodController@show')->name('AdminApi\PriceMethod::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'PriceMethodController@save')->name('AdminApi\PriceMethod::save');
    // Route::delete('/{data}', 'PriceMethodController@destroy')->name('AdminApi\PriceMethod::destroy');
});

Route::prefix('v1/modules')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'ModulesController@index')->name('AdminApi\Status::index');
});