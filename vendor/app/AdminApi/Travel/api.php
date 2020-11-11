<?php

Route::prefix('v1/traveltransaction')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/testprocess', 'TravelTransactionController@testProcess');
    Route::post('/createinvoice', 'TravelTransactionController@createInvoice');
    Route::post('/eticketmanualprocess', 'TravelTransactionController@eticketmanualtype');
    Route::post('/eticketmanualallocate', 'TravelTransactionController@eticketmanualallocate');
    Route::post('/status/entry', 'TravelTransactionController@statusInhouseEntry');
    Route::post('/status/posted', 'TravelTransactionController@statusInhousePosted');
    Route::post('/action/changetourpackage', 'TravelTransactionController@actionChangeItinerary');
    Route::get('/eticket/list/{Oid}', 'TravelTransactionController@eticketAttractionList');

    Route::get('/presearch', 'TravelTransactionController@presearch');
    Route::get('/config', 'TravelTransactionController@config');
    Route::get('/list', 'TravelTransactionController@list');

    Route::match(['put', 'post'],'/detail/save', 'TravelTransactionController@saveRowDetail');
    Route::match(['put', 'post'],'/attraction/save', 'TravelTransactionController@saveAttraction');
    Route::delete('/detail/delete', 'TravelTransactionController@deleteRowDetail');

    Route::get('/', 'TravelTransactionController@index');
    Route::get('/detailtransaction', 'TravelTransactionController@listDetailTransaction');
    Route::match(['put', 'post'],'/detail', 'TravelTransactionController@saveDetail');
    Route::post('/passenger', 'TravelTransactionController@savePassenger');
    Route::post('/flight', 'TravelTransactionController@savePassenger');
    Route::get('/{data}', 'TravelTransactionController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'TravelTransactionController@save');
    Route::delete('/{data}', 'TravelTransactionController@destroy');
});

Route::prefix('v1/travelitemhotelprice')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'TravelItemHotelPriceController@config');
    Route::get('/list', 'TravelItemHotelPriceController@list');
    Route::get('/presearch', 'TravelItemHotelPriceController@presearch');
    Route::get('/', 'TravelItemHotelPriceController@index');
    Route::get('/{data}', 'TravelItemHotelPriceController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'TravelItemHotelPriceController@save');
    Route::delete('/{data}', 'TravelItemHotelPriceController@destroy');
    Route::post('/country/{module}/{Oid}', 'TravelItemHotelPriceController@addCountry');
});

Route::prefix('v1/travelcoach')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'TravelCoachController@config');
    Route::get('/list', 'TravelCoachController@list');
    Route::get('/presearch', 'TravelCoachController@presearch');
    Route::get('/', 'TravelCoachController@index');
    Route::get('/{data}', 'TravelCoachController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'TravelCoachController@save');
    Route::delete('/{data}', 'TravelCoachController@destroy');
});

Route::prefix('v1/vtbhotel')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'VTBHotelController@config')->name('AdminApi\vtbHotel::config');
    Route::get('/list', 'VTBHotelController@list')->name('AdminApi\vtbHotel::list');
    Route::get('/', 'VTBHotelController@index')->name('AdminApi\vtbHotel::index');
    Route::get('/{data}', 'VTBHotelController@show')->name('AdminApi\vtbHotel::show');
    Route::match(['put', 'post'], '/{Oid?}', 'VTBHotelController@save')->name('AdminApi\vtbHotel::save');
    Route::delete('/{data}', 'VTBHotelController@destroy')->name('AdminApi\vtbHotel::destroy');
});

Route::prefix('v1/travelitempricebusinesspartner')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/dashboard', 'TravelItemPriceBusinessPartnerController@dashboard')->name('AdminApi\TravelBusinessPartner::config');
    Route::get('/presearch', 'TravelItemPriceBusinessPartnerController@presearch')->name('AdminApi\TravelBusinessPartner::config');
    Route::get('/config', 'TravelItemPriceBusinessPartnerController@config')->name('AdminApi\TravelBusinessPartner::config');
    Route::get('/list', 'TravelItemPriceBusinessPartnerController@list')->name('AdminApi\TravelBusinessPartner::list');
    Route::get('/', 'TravelItemPriceBusinessPartnerController@index')->name('AdminApi\TravelBusinessPartner::index');
    Route::get('/{data}', 'TravelItemPriceBusinessPartnerController@show')->name('AdminApi\TravelBusinessPartner::show');
    Route::match(['put', 'post'], '/{Oid?}', 'TravelItemPriceBusinessPartnerController@save')->name('AdminApi\TravelBusinessPartner::save');
    Route::delete('/{data}', 'TravelItemPriceBusinessPartnerController@destroy')->name('AdminApi\TravelBusinessPartner::destroy');
});

Route::prefix('v1')->middleware('cors','auth:api')->group(function () {
    Route::get('allotment', 'AllotmentController@index')->name('Travel\Admin::allotment');
    Route::get('allotment_transactions', 'AllotmentController@getTransactions')->name('Travel\Admin::allotment.transactions');
    Route::post('allotment_transactions', 'AllotmentController@assignAllotment')->name('Travel\Admin::allotment.transactions.assign');
    Route::get('allotment/items', 'AllotmentController@items')->name('Travel\Admin::allotment.items');
    Route::post('allotment', 'AllotmentController@store')->name('Travel\Admin::allotment.store');
    Route::post('allotment/take', 'AllotmentController@take')->name('Travel\Admin::allotment.take');
    Route::get('allotment_detail/{id}', 'AllotmentController@show')->name('Travel\Admin::allotment.show');
    Route::put('allotment/{id}', 'AllotmentController@update')->name('Travel\Admin::allotment.update');
    Route::delete('allotment/{id}', 'AllotmentController@destroy')->name('Travel\Admin::allotment.destroy');
    Route::delete('allotment_transactions/{id}', 'AllotmentController@destroyAllotmentTransaction')->name('Travel\Admin::allotment.transactions.destroy');
    Route::get('allotment_transactions_detail/{id}', 'AllotmentController@viewTransaction')->name('Travel\Admin::allotment.viewTransaction');
});

Route::prefix('v1/travelpackage')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'TravelPackageController@config')->name('AdminApi\TravelPackageController::config');
    Route::get('/list', 'TravelPackageController@list')->name('AdminApi\TravelFlightNumber::list');
    Route::get('/', 'TravelPackageController@index')->name('AdminApi\TravelPackage::index');
    Route::get('/{data}', 'TravelPackageController@show')->name('AdminApi\TravelPackage::show');
    Route::match(['put', 'post'], '/{Oid?}', 'TravelPackageController@save')->name('AdminApi\TravelPackage::save');
    Route::delete('/{data}', 'TravelPackageController@destroy')->name('AdminApi\TravelPackage::destroy');
});

Route::prefix('v1/travelprofitloss')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'TravelProfitLossController@config')->name('AdminApi\TravelProfitLoss::config');
    Route::get('/list', 'TravelProfitLossController@list')->name('AdminApi\TravelProfitLoss::list');
    Route::get('/presearch', 'TravelProfitLossController@presearch');
    Route::get('/', 'TravelProfitLossController@index')->name('AdminApi\TravelProfitLoss::index');
    // Route::get('/{data}', 'TravelProfitLossController@show')->name('AdminApi\TravelProfitLossController::show');
    // Route::match(['put', 'post'], '/{Oid?}', 'TravelProfitLossController@save')->name('AdminApi\TravelProfitLossController::save');
    // Route::delete('/{data}', 'TravelProfitLossController@destroy')->name('AdminApi\TravelProfitLossController::destroy');
});


Route::prefix('v1/travelapi')->middleware(['cors'])->group(function () {
    Route::get('/item', 'TravelAPIController@getItem')->name('AdminApi\TravelAPIController::getItem');
    Route::get('/item/autocomplete', 'TravelAPIController@autocomplete')->name('AdminApi\TravelAPIController::autocomplete');
    Route::get('/itemcontent/autocomplete', 'TravelAPIController@getItemContentAutocomplete')->name('AdminApi\TravelAPIController::getItemContentAutocomplete');
    Route::post('/upload/eticket', 'TravelAPIController@uploadEticket')->name('AdminApi\TravelAPIController::uploadEticket');
    Route::post('/upload/ftp/{id}', 'TravelAPIController@uploadEticketFTP')->name('AdminApi\TravelAPIController::uploadEticketFTP');
    Route::post('/generate/qty/{id}', 'TravelAPIController@generateByQty')->name('AdminApi\TravelAPIController::generateByQty');
    Route::post('/generate/merchant/{id}', 'TravelAPIController@generateByMerchant')->name('AdminApi\TravelAPIController::generateByMerchant');
    Route::post('/send/user/{id}', 'TravelAPIController@sendEticketToUser')->name('AdminApi\TravelAPIController::sendEticketToUser');
    Route::post('/send/vendor/{id}', 'TravelAPIController@sendEticketToVendor')->name('AdminApi\TravelAPIController::sendEticketToVendor');
    Route::post('/set/paid/{id}', 'TravelAPIController@setToPaid')->name('AdminApi\TravelAPIController::setToPaid');
    Route::post('/set/complete/{id}', 'TravelAPIController@setToComplete')->name('AdminApi\TravelAPIController::setToComplete');
    Route::post('/send/eticket/{id}', 'TravelAPIController@sendEticket')->name('AdminApi\TravelAPIController::sendEticket');
    Route::post('/resend/eticket/{id}', 'TravelAPIController@resendEticket')->name('AdminApi\TravelAPIController::resendEticket');
    Route::post('/link/stock/{id}', 'TravelAPIController@linkFromStock')->name('AdminApi\TravelAPIController::linkFromStock');
    Route::post('/payment/prereport/{id}', 'TravelAPIController@paymentPreReport')->name('AdminApi\TravelAPIController::paymentPreReport');
    Route::delete('/delete/eticket/{id}', 'TravelAPIController@deleteEticket')->name('AdminApi\TravelAPIController::deleteEticket');
});

Route::prefix('v1/traveltransactioncompany')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'TravelTransactionCompanyController@list')->name('AdminApi\TravelTransactionCompany::list');
    Route::get('/config', 'TravelTransactionCompanyController@config')->name('AdminApi\TravelTransactionCompany::config');
    Route::get('/{Oid}', 'TravelTransactionCompanyController@detailList')->name('AdminApi\TravelTransactionCompany::detailList');
});

Route::prefix('v1/sendemail')->middleware(['cors'])->group(function () {
    Route::post('/', 'TravelAPIController@sendToVendorPaymentPreReport');
});

// TODO: remove this later (Dummy)
Route::prefix('v1/importexcel')->middleware(['cors'])->group(function () {
    Route::post('/', 'ImportExcelController@store')->name('AdminApi\ImportExcel::store');
});
Route::prefix('v1/importpayment')->middleware(['cors'])->group(function () {
    Route::post('/', 'ImportPaymentController@store')->name('AdminApi\ImportPayment::store');
});
