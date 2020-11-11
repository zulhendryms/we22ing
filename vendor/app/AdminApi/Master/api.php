<?php

Route::prefix('v1/itemcontent')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/company/unset_to', 'ItemContentCompanyController@unsetCompanyTo');
    Route::post('/company/set_to', 'ItemContentCompanyController@setCompanyTo');
    Route::get('/company/list', 'ItemContentCompanyController@listItemForCompany');
    Route::get('/company/item', 'ItemContentCompanyController@listCompanyForItem');
    Route::get('/company/detail', 'ItemContentCompanyController@listItemDetailForCompany');
    Route::get('/detail/getprice', 'ItemContentCompanyController@getPriceDetailForCompany');
    Route::post('/detail/updateprice', 'ItemContentCompanyController@createDetailPrice');
    Route::delete('/detail/price/{Oid}', 'ItemContentCompanyController@deleteDetailPrice');
    Route::get('/company/custom', 'ItemContentCompanyController@getItemContentCustom');
    Route::post('/company/duplicate', 'ItemContentCompanyController@duplicateItemContent');
    Route::get('/companytype/listprice', 'ItemContentCompanyController@listPriceCompanyItemType');
    Route::post('/companytype/setprice', 'ItemContentCompanyController@setPriceCompanyItemType');
    Route::get('/price/get', 'ItemContentCompanyController@listPriceCompanyItemContent');
    Route::post('/price/set', 'ItemContentCompanyController@setPriceCompanyItemContent');

    //CRUD
    Route::get('/presearch', 'ItemContentCRUDController@presearch')->name('AdminApi\Item::presearch');
    Route::get('/config', 'ItemContentCRUDController@config')->name('AdminApi\Item::config');
    Route::get('/list', 'ItemContentCRUDController@list')->name('AdminApi\Item::list');
    Route::get('/', 'ItemContentCRUDController@index')->name('AdminApi\Item::index');
    Route::match(['put', 'post'], '/savedetail', 'ItemContentCRUDController@saveDetail')->name('AdminApi\Item::saveDetail');
    Route::delete('/deletedetail/{Oid}', 'ItemContentCRUDController@deleteDetail')->name('AdminApi\Item::deleteDetail');

    Route::get('/reorderitemcontent', 'ItemContentController@reorderItemContent')->name('AdminApi\Item::reorderItemContent');; //reorder
    Route::post('/reorderitemcontentupdate', 'ItemContentController@reorderItemContentUpdate')->name('AdminApi\Item::reorderItemContentUpdate');; //reorder

    Route::post('/duplicate', 'ItemContentController@duplicateItemContent')->name('AdminApi\Item::duplicateItemContent');;
    Route::get('/detailfeaturelist', 'ItemContentController@detailfeaturelist')->name('AdminApi\Item::detailfeaturelist');
    Route::get('/featurelist', 'ItemContentController@featurelist')->name('AdminApi\Item::featurelist');
    Route::post('/import', 'ItemContentController@import')->name('AdminApi\Item::import');
    Route::get('/importsample', 'ItemContentController@importSample')->name('AdminApi\Item::importSample');
    Route::match(['put', 'post'], '/featuresave/{Oid?}', 'ItemContentController@featuresave')->name('AdminApi\Item::featuresave');
    Route::delete('/featuredelete/{data}', 'ItemContentController@featuredestroy')->name('AdminApi\Item::featuredestroy');
    Route::get('/{Oid}/attraction', 'ItemContentController@getItemAttraction')->name('Admin\Item::getItemAttraction');
    Route::match(['post'], '/attraction', 'ItemContentController@createItemAttraction')->name('AdminApi\Item::createItemAttraction');
    Route::match(['put'], '/attraction/{Oid}', 'ItemContentController@saveItemAttraction')->name('AdminApi\Item::saveItemAttraction');
    Route::delete('/attraction/{Oid}', 'ItemContentController@deleteAttraction')->name('AdminApi\Item::deleteAttraction');
    Route::match(['put', 'post'], '/attractioneticket/{Oid}', 'ItemContentController@saveItemForEticketUpload')->name('AdminApi\Item::saveItemForEticketUpload');

    Route::get('/{Oid}/restaurant', 'ItemContentController@getItemRestaurant')->name('Admin\Item::getItemRestaurant');
    Route::match(['post'], '/restaurant', 'ItemContentController@createItemRestaurant')->name('AdminApi\Item::createItemRestaurant');
    Route::match(['put'], '/restaurant/{Oid}', 'ItemContentController@saveItemRestaurant')->name('AdminApi\Item::saveItemRestaurant');
    Route::delete('/restaurant/{Oid}', 'ItemContentController@deleteRestaurant')->name('AdminApi\Item::deleteRestaurant');

    Route::get('/{Oid}/transport', 'ItemContentController@getItemTransport')->name('Admin\Item::getItemTransport');
    Route::match(['put', 'post'], '/transport/{Oid?}', 'ItemContentController@saveItemTransport')->name('AdminApi\Item::saveItemTransport');
    Route::delete('/transport/{Oid}', 'ItemContentController@deleteItemTransport')->name('AdminApi\Item::deleteItemTransport');

    Route::get('/{Oid}/hotel', 'ItemContentController@getItemHotel')->name('Admin\Item::getItemHotel');
    Route::match(['post'], '/hotel', 'ItemContentController@createItemHotel')->name('AdminApi\Item::createItemHotel');
    Route::match(['put'], '/hotel/{Oid}', 'ItemContentController@saveItemHotel')->name('AdminApi\Item::saveItemHotel');
    Route::delete('/hotel/{Oid}', 'ItemContentController@deleteItemHotel')->name('AdminApi\Item::deleteItemHotel');
    
    Route::get('/listitemprocess', 'ItemContentController@listitemprocess')->name('AdminApi\Item::listitemprocess');
    Route::post('/saveprocess', 'ItemContentController@saveitemprocess')->name('AdminApi\Item::saveitemprocess');

    Route::get('/listitemecommerce', 'ItemContentController@listitemecommerce')->name('AdminApi\Item::listitemecommerce');
    Route::post('/saveecommerce', 'ItemContentController@saveitemecommerce')->name('AdminApi\Item::saveitemecommerce');

    Route::get('/listitemcountry', 'ItemCountryController@index')->name('AdminApi\ItemCountryController::index');
    Route::post('/savecountry', 'ItemCountryController@save')->name('AdminApi\ItemCountryController::save');

    Route::get('/date', 'ItemContentController@listitemdate')->name('AdminApi\Item::listitemdate');
    Route::post('/date', 'ItemContentController@saveitemdate')->name('AdminApi\Item::saveitemdate');
    Route::post('/copyschedule/{Oid}', 'ItemContentController@copyschedule')->name('AdminApi\Item::copyschedule');

    Route::get('/listitemoutbound', 'ItemContentController@listitemoutbound')->name('AdminApi\Item::listitemoutbound');
    Route::match(['post'], '/outbound', 'ItemContentController@createItemOutbound')->name('AdminApi\Item::createItemOutbound');
    Route::match(['put'], '/outbound/{Oid}', 'ItemContentController@saveitemoutbound')->name('AdminApi\Item::saveitemoutbound');
    Route::delete('/outbound/{Oid}', 'ItemContentController@deleteItemOutbound')->name('AdminApi\Item::deleteItemOutbound');

    Route::get('/salesamount', 'ItemContentController@viewSalesAmount')->name('AdminApi\Item::viewSalesAmount');
    Route::get('/purchaseamount', 'ItemContentController@viewPurchaseAmount')->name('AdminApi\Item::viewPurchaseAmount');
    Route::get('/{data}', 'ItemContentCRUDController@show')->name('AdminApi\Item::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemContentCRUDController@save')->name('AdminApi\Item::save');
    Route::delete('/{data}', 'ItemContentController@destroy')->name('AdminApi\Item::destroy');
    Route::get('/{Oid}/eticket', 'ItemContentController@viewEticket')->name('Admin\Item::viewEticket');
    Route::post('/{Oid}/eticket', 'ItemContentController@upload')->name('Admin\Item::store');
    Route::delete('/eticket/{Oid}', 'ItemContentController@deleteEticket')->name('AdminApi\Item::deleteEticket');
    Route::get('/{Oid}/pricemethod', 'ItemContentController@getPriceMethod')->name('Admin\Item::getPriceMethod');
    Route::post('/{Oid}/pricemethod', 'ItemContentController@savePriceMethod')->name('AdminApi\Item::savePriceMethod');
});

Route::prefix('v1/item/old')->middleware(['cors', 'auth:api'])->group(function () {    
    Route::get('/config', 'ItemController@config')->name('AdminApi\item::config');
    Route::get('/list', 'ItemController@list')->name('AdminApi\item::list');
    Route::get('/', 'ItemController@index')->name('AdminApi\item::index');
    Route::get('/{Oid}', 'ItemController@show')->name('AdminApi\Item::show');  
    Route::match(['put', 'post'], '/{Oid?}', 'ItemController@save')->name('AdminApi\item::save');
    Route::delete('/{data}', 'ItemController@destroy')->name('AdminApi\item::destroy');
});

Route::prefix('v1/companyitemcontent')->middleware(['cors', 'auth:api'])->group(function () {    
    Route::get('/presearch', 'CompanyItemContentController@presearch');
    Route::get('/config', 'CompanyItemContentController@config');
    Route::get('/list', 'CompanyItemContentController@list');
    Route::get('/', 'CompanyItemContentController@index');
    Route::get('/{data}', 'CompanyItemContentController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'CompanyItemContentController@save');
    Route::delete('/{data}', 'CompanyItemContentController@destroy');

    Route::get('/unsetcompany', 'CompanyItemContentController@unsetCompanyTo');
    Route::get('/setcompany', 'CompanyItemContentController@setCompanyTo');
});

Route::prefix('v1/itembusinesspartner')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'ItemBusinessPartnerController@config')->name('AdminApi\itembusinesspartner::config');
    Route::get('/list', 'ItemBusinessPartnerController@list')->name('AdminApi\itembusinesspartner::list');
    Route::get('/', 'ItemBusinessPartnerController@index')->name('AdminApi\itembusinesspartner::index');
    Route::get('/{data}', 'ItemBusinessPartnerController@show')->name('AdminApi\itembusinesspartner::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemBusinessPartnerController@save')->name('AdminApi\itembusinesspartner::save');
    Route::delete('/{data}', 'ItemBusinessPartnerController@destroy')->name('AdminApi\itembusinesspartner::destroy');
});

Route::prefix('v1/item/priceitem')->middleware(['cors', 'auth:api'])->group(function () {
    Route::delete('/{data}', 'ItemController@destroyPriceMarkupItem')->name('AdminApi\Item::destroyPriceMarkupItem');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemController@savePriceMarkupItem')->name('AdminApi\Item::savePriceMarkupItem');
    Route::get('', 'ItemController@listPriceMarkupItem')->name('AdminApi\Item::listPriceMarkupItem');
    Route::get('/{Oid}', 'ItemController@detailListPriceMarkupItem')->name('AdminApi\Item::detailListPriceMarkupItem');    
});

// Route::prefix('v1/item/hotelprice')->middleware(['cors', 'auth:api'])->group(function () {
//     Route::get('', 'ItemController@listHotelPrice')->name('AdminApi\Item::listHotelPrice');
//     Route::get('/{Oid}', 'ItemController@detailListHotelPrice')->name('AdminApi\Item::detailListHotelPrice');
//     Route::match(['put', 'post'], '/{Oid?}', 'ItemController@saveHotelPrice')->name('AdminApi\Item::saveHotelPrice');
//     Route::delete('/{data}', 'ItemController@destroyHotelPrice')->name('AdminApi\Item::destroyHotelPrice');
// });

// Route::prefix('v1/item/attraction')->middleware(['cors', 'auth:api'])->group(function () {
//     Route::get('/{Oid}', 'ItemController@getItemAttraction')->name('Admin\Item::getItemAttraction'); //Oid.attraction ->attraction.Oid
//     Route::match(['put', 'post'], '/attraction/{Oid?}', 'ItemController@saveItemAttraction')->name('AdminApi\Item::saveItemAttraction');
//     Route::match(['put', 'post'], '/eticket/{Oid}', 'ItemController@saveItemForEticketUpload')->name('AdminApi\Item::saveItemForEticketUpload');
//     Route::delete('/attraction/{Oid}', 'ItemController@deleteAttraction')->name('AdminApi\Item::deleteAttraction');
// });

Route::prefix('v1/item')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'ItemCRUDController@field')->name('AdminApi\item::field');
    Route::get('/presearch', 'ItemCRUDController@presearch')->name('AdminApi\item::presearch');
    Route::get('/barcodelist', 'ItemController@barcodelist')->name('AdminApi\Item::barcodelist');
    Route::get('/featurelist', 'ItemController@featurelist')->name('AdminApi\Item::featurelist');
    Route::get('/quick/config', 'ItemCRUDController@quickConfig')->name('AdminApi\item::config');
    Route::get('/quick/list', 'ItemCRUDController@quickList')->name('AdminApi\item::list');
    
    Route::get('/listitemcountry', 'ItemCountryController@index')->name('AdminApi\ItemCountryController::index');
    Route::post('/savecountry', 'ItemCountryController@save')->name('AdminApi\ItemCountryController::save');
    
    Route::get('/date', 'ItemController@listitemdate')->name('AdminApi\Item::listitemdate');
    Route::post('/date', 'ItemController@saveitemdate')->name('AdminApi\Item::saveitemdate');

    //HOKINDO
    Route::match(['put', 'post'], '/production/{Oid?}', 'ItemController@save')->name('AdminApi\item::save');
    Route::delete('/production/{data}', 'ItemController@destroy')->name('AdminApi\item::destroy');
    
    Route::post('/sync', 'ItemController@sendItem')->name('AdminApi\Item::sendItem');
    Route::post('/send', 'ItemController@receiveItem')->name('AdminApi\Item::receiveItem');

    Route::match(['put', 'post'], '/featuresave/{Oid?}', 'ItemController@featuresave')->name('AdminApi\Item::featuresave');
    Route::get('/detailfeaturelist', 'ItemController@detailfeaturelist')->name('AdminApi\Item::detailfeaturelist');
    Route::get('/checkstock', 'ItemController@checkStockItem')->name('AdminApi\Item::checkStockItem');
    Route::post('/import', 'ItemController@import')->name('AdminApi\Item::import');
    Route::get('/importsample', 'ItemController@importSample')->name('AdminApi\Item::importSample');
    Route::delete('/featuredelete/{data}', 'ItemController@featuredestroy')->name('AdminApi\Item::featuredestroy');
    
    Route::get('/config', 'ItemCRUDController@config')->name('AdminApi\item::config');
    Route::get('/list', 'ItemCRUDController@list')->name('AdminApi\item::list');
    Route::get('/', 'ItemCRUDController@index')->name('AdminApi\item::index');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemCRUDController@save')->name('AdminApi\item::save');
    Route::delete('/{data}', 'ItemCRUDController@destroy')->name('AdminApi\item::destroy');

    // Route::get('/config', 'ItemController@config')->name('AdminApi\Item::config');
    // Route::get('/list', 'ItemController@list')->name('AdminApi\Item::list');
    // Route::get('/', 'ItemController@index')->name('AdminApi\Item::index');
    // Route::match(['put', 'post'], '/{Oid?}', 'ItemController@save')->name('AdminApi\Item::save');
    // Route::delete('/{data}', 'ItemController@destroy')->name('AdminApi\Item::destroy');    
    
    Route::post('/generateBarcode', 'ItemController@allGenerateBarcode')->name('AdminApi\Item::allGenerateBarcode');




    
    // Route::get('/{Oid}/transport', 'ItemController@getItemTransport')->name('Admin\Item::getItemTransport');
    // Route::match(['put', 'post'], '/transport/{Oid?}', 'ItemController@saveItemTransport')->name('AdminApi\Item::saveItemTransport');
    // Route::delete('/transport/{Oid}', 'ItemController@deleteItemTransport')->name('AdminApi\Item::deleteItemTransport');

    Route::get('/{Oid}/hotel', 'ItemController@getItemHotel')->name('Admin\Item::getItemHotel');
    Route::match(['put', 'post'], '/hotel/{Oid?}', 'ItemController@saveItemHotel')->name('AdminApi\Item::saveItemHotel');
    Route::delete('/hotel/{Oid}', 'ItemController@deleteItemHotel')->name('AdminApi\Item::deleteItemHotel');
    
    Route::get('/listitemprocess', 'ItemController@listitemprocess')->name('AdminApi\Item::listitemprocess');
    Route::post('/saveprocess', 'ItemController@saveitemprocess')->name('AdminApi\Item::saveitemprocess');

    Route::get('/listitemecommerce', 'ItemController@listitemecommerce')->name('AdminApi\Item::listitemecommerce');
    Route::post('/saveecommerce', 'ItemController@saveitemecommerce')->name('AdminApi\Item::saveitemecommerce');


    Route::post('/copyschedule/{Oid}', 'ItemController@copyschedule')->name('AdminApi\Item::copyschedule');

    Route::get('/listitemoutbound', 'ItemController@listitemoutbound')->name('AdminApi\Item::listitemoutbound');
    Route::match(['put', 'post'], '/saveitemoutbound/{Oid?}', 'ItemController@saveitemoutbound')->name('AdminApi\Item::saveitemoutbound');
    Route::delete('/outbound/{Oid}', 'ItemController@deleteitemoutbound')->name('AdminApi\Item::deleteitemoutbound');

    Route::get('/salesamount', 'ItemController@viewSalesAmount')->name('AdminApi\Item::viewSalesAmount');
    Route::get('/purchaseamount', 'ItemController@viewPurchaseAmount')->name('AdminApi\Item::viewPurchaseAmount');
    Route::get('/{Oid}/eticket', 'ItemController@viewEticket')->name('Admin\Item::viewEticket');
    Route::post('/{Oid}/eticket', 'ItemController@upload')->name('Admin\Item::store');
    Route::delete('/eticket/{Oid}', 'ItemController@deleteEticket')->name('AdminApi\Item::deleteEticket');
    Route::get('/{Oid}/pricemethod', 'ItemController@getPriceMethod')->name('Admin\Item::getPriceMethod');
    Route::post('/{Oid}/pricemethod', 'ItemController@savePriceMethod')->name('AdminApi\Item::savePriceMethod');
    Route::get('/{data}', 'ItemCRUDController@show')->name('AdminApi\item::show');
    //HOKINDO
    Route::get('/production/{data}', 'ItemController@show')->name('AdminApi\Item::show');
});

Route::prefix('v1/businesspartner')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'BusinessPartnerController@config')->name('AdminApi\BusinessPartner::config');
    Route::get('/list', 'BusinessPartnerController@list')->name('AdminApi\BusinessPartner::list');
    Route::get('/', 'BusinessPartnerController@index')->name('AdminApi\BusinessPartner::index');
    Route::get('/inquiry', 'BusinessPartnerController@inquiry')->name('AdminApi\BusinessPartner::inquiry');
    Route::get('/contact', 'BusinessPartnerController@listContact')->name('AdminApi\BusinessPartner::listContact');
    Route::get('/contact/{Oid}', 'BusinessPartnerController@listContactDetail')->name('AdminApi\BusinessPartner::listContactDetail');
    Route::get('/transportdriver', 'BusinessPartnerController@listTransportDriver')->name('AdminApi\BusinessPartner::listTransportDriver');
    Route::get('/transportdriver/{Oid}', 'BusinessPartnerController@listTransportDriverDetail')->name('AdminApi\BusinessPartner::listTransportDriverDetail');
    Route::get('/port', 'BusinessPartnerController@listPort')->name('AdminApi\BusinessPartner::listPort');
    Route::get('/port/{Oid}', 'BusinessPartnerController@listPortDetail')->name('AdminApi\BusinessPartner::listPortDetail');
    Route::get('/inquiryjournal', 'BusinessPartnerController@inquiryjournal')->name('AdminApi\BusinessPartner::inquiryjournal');
    Route::post('/importcustomer', 'BusinessPartnerController@importCustomer')->name('AdminApi\BusinessPartner::importcustomer');

    Route::post('/import', 'BusinessPartnerController@import')->name('AdminApi\BusinessPartner::import');

    Route::get('/importsamplecustomer', 'BusinessPartnerController@importSampleCustomer')->name('AdminApi\BusinessPartner::importsamplecustomer');
    Route::post('/importsupplier', 'BusinessPartnerController@importSupplier')->name('AdminApi\BusinessPartner::importsupplier');
    Route::get('/importsamplesupplier', 'BusinessPartnerController@importSampleSupplier')->name('AdminApi\BusinessPartner::importsamplesupplier');
    Route::delete('/contact/{Oid}', 'BusinessPartnerController@deleteContact')->name('AdminApi\BusinessPartner::deleteContact');
    Route::delete('/transportdriver/{Oid}', 'BusinessPartnerController@deleteTransportDriver')->name('AdminApi\BusinessPartner::deleteTransportDriver');
    Route::delete('/port/{Oid}', 'BusinessPartnerController@deletePort')->name('AdminApi\BusinessPartner::deletePort');
    Route::match(['put', 'post'], '/savetoken/{Oid?}', 'BusinessPartnerController@savetoken')->name('AdminApi\BusinessPartner::savetoken');
    Route::match(['put', 'post'], '/contact/{Oid?}', 'BusinessPartnerController@saveContact')->name('AdminApi\BusinessPartner::saveContact');
    Route::match(['put', 'post'], '/transportdriver/{Oid?}', 'BusinessPartnerController@saveTransportDriver')->name('AdminApi\BusinessPartner::saveTransportDriver');
    Route::match(['put', 'post'], '/port/{Oid?}', 'BusinessPartnerController@savePort')->name('AdminApi\BusinessPartner::savePort');
    Route::get('/{data}', 'BusinessPartnerController@show')->name('AdminApi\BusinessPartner::show');
    Route::match(['put', 'post'], '/{Oid?}', 'BusinessPartnerController@save')->name('AdminApi\BusinessPartner::save');
    Route::delete('/{data}', 'BusinessPartnerController@destroy')->name('AdminApi\BusinessPartner::destroy');    
});

Route::prefix('v1/businesspartnergroup')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'CompanyController@config')->name('AdminApi\BusinessPartnerGroup::config');
    Route::get('/list', 'BusinessPartnerGroupController@list')->name('AdminApi\BusinessPartnerGroup::list');
    Route::get('/', 'BusinessPartnerGroupController@index')->name('AdminApi\BusinessPartnerGroup::index');
    Route::get('/{data}', 'BusinessPartnerGroupController@show')->name('AdminApi\BusinessPartnerGroup::show');
    Route::match(['put', 'post'], '/{Oid?}', 'BusinessPartnerGroupController@save')->name('AdminApi\BusinessPartnerGroup::save');
    Route::delete('/{data}', 'BusinessPartnerGroupController@destroy')->name('AdminApi\BusinessPartnerGroup::destroy');
});

Route::prefix('v1/company')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'CompanyController@index')->name('AdminApi\Company::index');
    Route::get('/config', 'CompanyController@config')->name('AdminApi\Company::config');
    Route::get('/presearch', 'CompanyController@presearch')->name('AdminApi\Company::presearch');
    Route::get('/list', 'CompanyController@list')->name('AdminApi\Company::list');
    Route::get('/all', 'CompanyController@masterlist')->name('AdminApi\Company::masterlist');
    Route::get('/testlist/{$type}', 'CompanyController@list')->name('AdminApi\Company::masterlist');
    Route::put('/edit', 'CompanyController@edit')->name('AdminApi\Company::edit');
    Route::get('/{data}', 'CompanyController@show')->name('AdminApi\Company::show');
    Route::match(['put', 'post'], '/{Oid?}', 'CompanyController@save')->name('AdminApi\Company::save');
    Route::delete('/{data}', 'CompanyController@destroy')->name('AdminApi\CompanyController::destroy');
    Route::post('/change', 'CompanyController@change');
    
    
    Route::get('/pricemethod/apitude', 'CompanyController@getPriceMethodApitude')->name('Admin\Company::getPriceMethodApitude');
    Route::put('/pricemethod/apitude', 'CompanyController@savePriceMethodApitude')->name('AdminApi\Company::savePriceMethodApitude');
    Route::get('/pricemethod/globaltix', 'CompanyController@getPriceMethodGlobalTix')->name('Admin\Company::getPriceMethodGlobalTix');
    Route::put('/pricemethod/globaltix', 'CompanyController@savePriceMethodGlobalTix')->name('AdminApi\Company::savePriceMethodGlobalTix');
    // Route::match(['put', 'post'], '/{Oid?}', 'CompanyController@save')->name('AdminApi\Company::save');
    // Route::delete('/{data}', 'CompanyController@destroy')->name('AdminApi\Company::destroy');
});

Route::prefix('v1/currency')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/rate', 'CurrencyController@rate')->name('AdminApi\Currency::rate');
    Route::post('/rate/insert', 'CurrencyController@rateInsert')->name('AdminApi\Currency::rateInsert');
    Route::get('/convert/c', 'CurrencyController@convert')->name('AdminApi\Currency::convert');
});

Route::prefix('v1/currencyratedate')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'CurrencyRateDateController@config')->name('AdminApi\CurrencyRateDate::config');
    Route::get('/list', 'CurrencyRateDateController@list')->name('AdminApi\CurrencyRateDate::list');
    // Route::get('/detailconfig', 'CurrencyRateDateController@detailconfig')->name('AdminApi\CurrencyRateDate::detailconfig');
    Route::match(['put', 'post'], '/{Oid?}', 'CurrencyRateDateController@save')->name('AdminApi\CurrencyRateDate::save');
    Route::get('/', 'CurrencyRateDateController@index')->name('AdminApi\CurrencyRateDate::index');
    Route::get('/saveall', 'CurrencyRateDateController@saveall')->name('AdminApi\CurrencyRateDate::saveall');
    Route::get('/rateDate', 'CurrencyRateDateController@currencyRateDate')->name('AdminApi\CurrencyRateDate::currencyRateDate');
    Route::get('/{data}', 'CurrencyRateDateController@show')->name('AdminApi\CurrencyRateDate::show');
    Route::put('/{Oid}', 'CurrencyRateDateController@update')->name('AdminApi\CurrencyRateDate::update');
    Route::post('/reupdate/{Oid}', 'CurrencyRateDateController@reupdate')->name('AdminApi\CurrencyRateDate::reupdate');
    Route::delete('/{data}', 'CurrencyRateDateController@destroy')->name('AdminApi\CurrencyRateDate::destroy');
    Route::post('/create','CurrencyRateDateController@functionInsert')->name('AdminApi\CurrencyRateDate::functionInsert');
});

Route::prefix('v1/employee')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/default', 'EmployeeController@getDefault')->name('AdminApi\Employee::getDefault');
    Route::get('/config', 'EmployeeController@config')->name('AdminApi\Employee::config');
    Route::get('/list', 'EmployeeController@list')->name('AdminApi\Employee::list');
    Route::get('/detailconfig', 'EmployeeController@detailconfig')->name('AdminApi\Employee::detailconfig');
    Route::get('/', 'EmployeeController@index')->name('AdminApi\Employee::index');
    Route::post('/sync', 'EmployeeController@sendEmployee')->name('AdminApi\Employee::sendEmployee');
    Route::post('/send', 'EmployeeController@receiveEmployee')->name('AdminApi\Employee::receiveEmployee');
    Route::match(['put', 'post'], '/savetoken/{Oid?}', 'EmployeeController@savetoken')->name('AdminApi\Employee::savetoken');
    Route::get('/{data}', 'EmployeeController@show')->name('AdminApi\Employee::show');
    Route::match(['put', 'post'], '/{Oid?}', 'EmployeeController@save')->name('AdminApi\Employee::save');
    Route::delete('/{data}', 'EmployeeController@destroy')->name('AdminApi\Employee::destroy');
});

Route::prefix('v1/itemgroup')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'ItemGroupController@config')->name('AdminApi\ItemGroup::config');
    Route::get('/list', 'ItemGroupController@list')->name('AdminApi\ItemGroup::list');
    Route::get('/', 'ItemGroupController@index')->name('AdminApi\ItemGroup::index');
    Route::get('/{data}', 'ItemGroupController@show')->name('AdminApi\ItemGroup::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemGroupController@save')->name('AdminApi\ItemGroup::save');
    Route::delete('/{data}', 'ItemGroupController@destroy')->name('AdminApi\ItemGroup::destroy');
    Route::get('/{Oid}/pricemethod', 'ItemGroupController@getPriceMethod')->name('Admin\ItemGroup::getPriceMethod');
    Route::post('/{Oid}/pricemethod', 'ItemGroupController@savePriceMethod')->name('AdminApi\ItemGroup::savePriceMethod');
});

Route::prefix('v1/itemtype')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ItemTypeController@list')->name('AdminApi\ItemType::list');
    Route::get('/config', 'ItemTypeController@config')->name('AdminApi\ItemType::config');
    Route::get('/', 'ItemTypeController@index')->name('AdminApi\ItemType::index');

    Route::get('/listitemtypecountry', 'ItemTypeCountryController@index')->name('AdminApi\ItemTypeCountryController::index');
    Route::post('/saveitemtypecountry', 'ItemTypeCountryController@save')->name('AdminApi\ItemTypeCountryController::save');
    Route::delete('/deleteitemtypecountry/{Oid}', 'ItemTypeCountryController@destroy')->name('AdminApi\ItemTypeCountryController::destroy');

    Route::get('/priceglobal', 'ItemTypeController@listPriceGlobalMarkup')->name('AdminApi\ItemType::listPriceGlobalMarkup');
    Route::get('/priceglobal/{Oid}', 'ItemTypeController@detailListPriceGlobalMarkup')->name('AdminApi\ItemType::detailListPriceGlobalMarkup');
    Route::match(['put', 'post'], '/priceglobal/{Oid?}', 'ItemTypeController@savePriceGlobalMarkup')->name('AdminApi\ItemType::savePriceGlobalMarkup');
    Route::delete('/priceglobal/{data}', 'ItemTypeController@destroyPriceGlobalMarkup')->name('AdminApi\ItemType::destroyPriceGlobalMarkup');

    Route::get('/{data}', 'ItemTypeController@show')->name('AdminApi\ItemType::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemTypeController@save')->name('AdminApi\ItemType::save');
    Route::delete('/{data}', 'ItemTypeController@destroy')->name('AdminApi\ItemType::destroy');
    Route::get('/{Oid}/pricemethod', 'ItemTypeController@getPriceMethod')->name('Admin\ItemType::getPriceMethod');
    Route::post('/{Oid}/pricemethod', 'ItemTypeController@savePriceMethod')->name('AdminApi\ItemType::savePriceMethod');

});

Route::prefix('v1/image')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'ImageController@config')->name('AdminApi\Image::config');
    Route::get('/list', 'ImageController@list')->name('AdminApi\Image::list');
    Route::get('/', 'ImageController@index')->name('AdminApi\Image::index');
    Route::get('/{data}', 'ImageController@show')->name('AdminApi\Image::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ImageController@save')->name('AdminApi\Image::save');
    Route::delete('/{data}', 'ImageController@destroy')->name('AdminApi\Image::destroy');
});

Route::prefix('v1/project')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'ProjectController@config')->name('AdminApi\Project::config');
    Route::get('/list', 'ProjectController@list')->name('AdminApi\Project::list');
    Route::match(['put', 'post'], '/save/{Oid?}', 'ProjectController@saveProjectSimple')->name('AdminApi\Project::saveProjectSimple');
    Route::get('/', 'ProjectController@index')->name('AdminApi\Project::index');
    Route::post('/export', 'ProjectController@export')->name('AdminApi\Project::export');
    Route::post('/sync', 'ProjectController@sendProject')->name('AdminApi\Project::sendProject');
    Route::post('/send', 'ProjectController@receiveProject')->name('AdminApi\Project::receiveProject');
    Route::get('/{data}', 'ProjectController@show')->name('AdminApi\Project::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProjectController@save')->name('AdminApi\Project::save');
    Route::delete('/{data}', 'ProjectController@destroy')->name('AdminApi\Project::destroy');
});

Route::prefix('v1/itemallotment')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ItemAllotmentController@list')->name('AdminApi\itemallotment::list');
    Route::get('/config', 'ItemAllotmentController@config')->name('AdminApi\itemallotment::config');
    Route::get('/', 'ItemAllotmentController@index')->name('AdminApi\itemallotment::index');
    Route::get('/{data}', 'ItemAllotmentController@show')->name('AdminApi\itemallotment::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ItemAllotmentController@save')->name('AdminApi\itemallotment::save');
    Route::delete('/{data}', 'ItemAllotmentController@destroy')->name('AdminApi\itemallotment::destroy');
});

Route::prefix('v1/itemallotmentdashboard')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ItemAllotmentDashboardController@list')->name('AdminApi\itemallotment::list');
    Route::get('/config', 'ItemAllotmentDashboardController@config')->name('AdminApi\itemallotment::config');
    Route::get('/presearch', 'ItemAllotmentDashboardController@presearch')->name('AdminApi\FerryRoute::presearch');
    Route::post('/create/{Oid}', 'ItemAllotmentDashboardController@create')->name('AdminApi\itemallotment::save');
});

Route::prefix('v1/agentredeem')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'AgentRedeemController@list')->name('AdminApi\agentredeem::list');
    Route::get('/config', 'AgentRedeemController@config')->name('AdminApi\agentredeem::config');
    Route::get('/presearch', 'AgentRedeemController@presearch')->name('AdminApi\agentredeem::presearch');
    Route::match(['put', 'post'],'/redeem/{Oid}', 'AgentRedeemController@redeem')->name('AdminApi\agentredeem::save');
});