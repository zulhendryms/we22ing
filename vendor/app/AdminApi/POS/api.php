<?php

Route::prefix('v1/report')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/salespos','ReportSalesPosController@report')->name('AdminApi\Report::report');
    Route::get('/cashtransaction','ReportPOSCashTransactionController@report')->name('AdminApi\Report::report');
});

Route::prefix('v1/reportviewer')->middleware(['cors'])->group(function () {
    Route::get('/{reportName}','ReportSalesPosController@view')->name('AdminApi\Report::view');
});

Route::prefix('v1/pointofsale')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'PointOfSaleController@config')->name('AdminApi\POS::config');
    Route::get('/list', 'PointOfSaleController@list')->name('AdminApi\POS::list');
    Route::get('/autocomplete', 'PointOfSaleController@autocomplete')->name('AdminApi\POS::autocomplete');
    Route::get('/company', 'POSCompanyController@index')->name('AdminApi\POS::index');
    Route::get('/company/{data}', 'POSCompanyController@show')->name('AdminApi\POS::show');
    Route::post('/repost', 'PointOfSaleController@repostPerDate')->name('AdminApi\POS::repostPerDate');
    Route::get('/searchsession', 'PointOfSaleController@searchSession')->name('AdminApi\POS::searchSession');
    Route::post('/changesession', 'PointOfSaleController@changeSession')->name('AdminApi\POS::changeSession');
    Route::get('/return/config', 'PointOfSaleController@configReturn')->name('AdminApi\POS::configReturn');
    Route::get('/return/search', 'PointOfSaleController@posreturnsearch')->name('AdminApi\POS::posreturnsearch');
    Route::post('/return/cancel', 'PointOfSaleController@posreturn')->name('AdminApi\POS::posreturn');
    Route::get('/', 'PointOfSaleController@index')->name('AdminApi\POS::index');
    Route::get('/detailtransaction', 'PointOfSaleController@listDetailTransaction')->name('AdminApi\POS::listDetailTransaction');
    Route::get('/detailpos', 'PointOfSaleController@listDetailPOS')->name('AdminApi\POS::listDetailPOS');
    Route::get('/{data}', 'PointOfSaleController@show')->name('AdminApi\POS::show');
    Route::post('/detail', 'PointOfSaleController@saveDetail')->name('AdminApi\POS::save');
    
    Route::match(['put', 'post'], '/{Oid?}', 'PointOfSaleController@save')->name('AdminApi\POS::save');
    Route::delete('/{data}', 'PointOfSaleController@destroy')->name('AdminApi\POS::destroy');
    Route::post('/{data}/entry', 'PointOfSaleController@entry');
    Route::post('/{data}/paid', 'PointOfSaleController@paid');
    Route::post('/{data}/completed', 'PointOfSaleController@completed');
    Route::post('/{data}/cancelled', 'PointOfSaleController@cancelled');
    Route::post('/{Oid}/eticket', 'PointOfSaleController@upload')->name('Admin\POS::store');
    Route::post('/{Oid}/eticket/delete', 'PointOfSaleController@deleteEticket')->name('Admin\POS::deleteEticket');

    Route::post('/{id}/pay', 'PaymentController@pay')->name('AdminApi\POS::pay');
});

Route::prefix('v1/pos')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'POSController@config')->name('AdminApi\POS::config');
    Route::get('/list', 'POSController@list')->name('AdminApi\POS::list');
    Route::get('/company', 'POSCompanyController@index')->name('AdminApi\POS::index');
    Route::get('/company/{data}', 'POSCompanyController@show')->name('AdminApi\POS::show');
    Route::post('/repost', 'POSController@repostPerDate')->name('AdminApi\POS::repostPerDate');
    Route::get('/searchsession', 'POSController@searchSession')->name('AdminApi\POS::searchSession');
    Route::post('/changesession', 'POSController@changeSession')->name('AdminApi\POS::changeSession');
    Route::get('/return/config', 'POSController@configReturn')->name('AdminApi\POS::configReturn');
    Route::get('/return/search', 'POSController@posreturnsearch')->name('AdminApi\POS::posreturnsearch');
    Route::post('/return/cancel', 'POSController@posreturn')->name('AdminApi\POS::posreturn');
    Route::get('/', 'POSController@index')->name('AdminApi\POS::index');
    Route::get('/detailtransaction', 'POSController@listDetailTransaction')->name('AdminApi\POS::listDetailTransaction');
    Route::get('/detailpos', 'POSController@listDetailPOS')->name('AdminApi\POS::listDetailPOS');
    Route::get('/{data}', 'POSController@show')->name('AdminApi\POS::show');
    Route::post('/detail', 'POSController@saveDetail')->name('AdminApi\POS::save');
    
    Route::match(['put', 'post'], '/{Oid?}', 'POSController@save')->name('AdminApi\POS::save');
    Route::delete('/{data}', 'POSController@destroy')->name('AdminApi\POS::destroy');
    Route::post('/{data}/entry', 'POSController@entry');
    Route::post('/{data}/paid', 'POSController@paid');
    Route::post('/{data}/completed', 'POSController@completed');
    Route::post('/{data}/cancelled', 'POSController@cancelled');
    Route::post('/{Oid}/eticket', 'POSController@upload')->name('Admin\POS::store');
    Route::post('/{Oid}/eticket/delete', 'POSController@deleteEticket')->name('Admin\POS::deleteEticket');

    Route::post('/{id}/pay', 'PaymentController@pay')->name('AdminApi\POS::pay');
});

Route::prefix('v1/possession')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'POSSessionController@config')->name('AdminApi\POSSession::config');
    Route::get('/list', 'POSSessionController@list')->name('AdminApi\POSSession::list');
    Route::get('/autocomplete', 'POSSessionController@autocomplete')->name('AdminApi\POSSession::autocomplete');
    Route::post('/changedate', 'POSSessionController@changeDate')->name('AdminApi\POSSession::changeDate');
    Route::get('/{data}', 'POSSessionController@show')->name('AdminApi\POSSession::show');
    Route::get('/', 'POSSessionController@index')->name('AdminApi\POSSession::index');
    Route::match(['put', 'post'], '/{Oid?}', 'POSSessionController@save')->name('AdminApi\POSSession::save');
    Route::delete('/{data}', 'POSSessionController@destroy')->name('AdminApi\POSSession::destroy');
    Route::post('/{data}/end', 'POSSessionController@end')->name('AdminApi\POSSession::end');
});

Route::prefix('v1/walletbalance')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/summary/list', 'WalletBalanceController@summarylist');
    Route::get('/summary/config', 'WalletBalanceController@summaryconfig');
    Route::get('/presearch', 'WalletBalanceController@presearch');
    Route::get('/config', 'WalletBalanceController@config');
    Route::get('/list', 'WalletBalanceController@list');
    Route::get('/', 'WalletBalanceController@index');
    Route::get('/{data}', 'WalletBalanceController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'WalletBalanceController@save');
    Route::delete('/{data}', 'WalletBalanceController@destroy');
    Route::post('/{data}/unpost', 'WalletBalanceController@unpost');
    Route::post('/{data}/post', 'WalletBalanceController@post');
    Route::post('/{data}/cancelled', 'WalletBalanceController@cancelled');
});

Route::prefix('v1/stocketicket')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/amendment', 'POSETicketUploadController@amendment');
    Route::get('/1/presearch', 'POSETicketUploadController@listPresearch');
    Route::get('/1/config', 'POSETicketUploadController@detailConfig');
    Route::get('/1/list', 'POSETicketUploadController@listList');  
    Route::get('/2/presearch', 'POSETicketUploadController@listPresearch');
    Route::get('/2/config', 'POSETicketUploadController@listConfig');
    Route::get('/2/list', 'POSETicketUploadController@detailList');  
});
Route::prefix('v1/stocketicket/summary')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'POSETicketUploadController@summaryPresearch');
    Route::get('/config', 'POSETicketUploadController@summaryConfig');
    Route::get('/list', 'POSETicketUploadController@summaryList');  
});
Route::prefix('v1/stocketicket/list')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'POSETicketUploadController@listPresearch');
    Route::get('/config', 'POSETicketUploadController@listConfig');
    Route::get('/list', 'POSETicketUploadController@listList');  
});
Route::prefix('v1/traveltransaction/stock')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'POSETicketUploadController@stockpopupPresearch');
    Route::get('/config', 'POSETicketUploadController@stockpopupConfig');
    Route::get('/list', 'POSETicketUploadController@stockpopupList');  
});

Route::prefix('v1/stocketicket/detail')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'POSETicketUploadController@detailPresearch');
    Route::get('/config', 'POSETicketUploadController@detailConfig');
    Route::get('/list', 'POSETicketUploadController@detailList');  
});

Route::prefix('v1/poseticketupload')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'POSETicketUploadController@listPresearch')->name('AdminApi\POSETicketUpload::presearch');
    Route::get('/config', 'POSETicketUploadController@listConfig')->name('AdminApi\POSETicketUpload::config');
    Route::get('/list', 'POSETicketUploadController@listList')->name('AdminApi\POSETicketUpload::list');  
    
    Route::get('/detailconfig', 'POSETicketUploadController@detailConfig')->name('AdminApi\POSETicketUpload::detailconfig');
    Route::get('/detaillist', 'POSETicketUploadController@detailList')->name('AdminApi\POSETicketUpload::detaillist');  

    Route::post('/unlink', 'POSETicketUploadController@unlink');
    Route::post('/remove/{Oid}', 'POSETicketUploadController@removeEticket')->name('AdminApi\POSETicketUpload::remove'); ;
    Route::get('/', 'POSETicketUploadController@index')->name('AdminApi\POSETicketUpload::index');    
    Route::post('/insert','POSETicketUploadController@insert')->name('AdminApi\POSETicketUpload::insert');
    Route::post('/export','POSETicketUploadController@export')->name('AdminApi\POSETicketUpload::export');
    Route::delete('/eticket/{Oid}','POSETicketUploadController@deleteEticket')->name('AdminApi\POSETicketUpload::deleteEticket');
    Route::get('/{data}', 'POSETicketUploadController@show')->name('AdminApi\POSETicketUpload::show');
    Route::match(['put', 'post'], '/{Oid?}', 'POSETicketUploadController@save')->name('AdminApi\POSETicketUpload::save');
    Route::delete('/{data}', 'POSETicketUploadController@destroy')->name('AdminApi\POSETicketUpload::destroy');
    Route::post('/{Oid}/eticket', 'POSETicketUploadController@upload')->name('Admin\POSETicketUpload::store');
    Route::post('/{data}/unpost', 'POSETicketUploadController@unpost');
    Route::post('/{data}/post', 'POSETicketUploadController@post');
});

Route::prefix('v1/featureinfoitem')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'FeatureInfoItemController@config')->name('AdminApi\FeatureInfoItem::config');
    Route::get('/list', 'FeatureInfoItemController@list')->name('AdminApi\FeatureInfoItem::list');
    Route::get('/', 'FeatureInfoItemController@index')->name('AdminApi\FeatureInfoItem::index');
    Route::get('/{data}', 'FeatureInfoItemController@show')->name('AdminApi\FeatureInfoItem::show');
    Route::match(['put', 'post'], '/{Oid?}', 'FeatureInfoItemController@save')->name('AdminApi\FeatureInfoItem::save');
    Route::delete('/{data}', 'FeatureInfoItemController@destroy')->name('AdminApi\FeatureInfoItem::destroy');
});

Route::prefix('v1/possessionamount')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'POSSessionAmountController@config')->name('AdminApi\POSSessionAmount::config');
    Route::get('/list', 'POSSessionAmountController@list')->name('AdminApi\POSSessionAmount::list');
    Route::get('/', 'POSSessionAmountController@index')->name('AdminApi\POSSessionAmount::index');
    Route::get('/{data}', 'POSSessionAmountController@show')->name('AdminApi\POSSessionAmount::show');
    Route::match(['put', 'post'], '/{Oid?}', 'POSSessionAmountController@save')->name('AdminApi\POSSessionAmount::save');
    Route::delete('/{data}', 'POSSessionAmountController@destroy')->name('AdminApi\POSSessionAmount::destroy');
});

Route::prefix('v1/listeticket')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'EticketController@config')->name('AdminApi\EticketController::config');
    Route::get('/list', 'EticketController@list')->name('AdminApi\EticketController::list');
    Route::get('/', 'EticketController@index')->name('AdminApi\EticketController::index');
    Route::get('/{data}', 'EticketController@show')->name('AdminApi\EticketController::show');
    Route::put('/{Oid}', 'EticketController@redeem')->name('AdminApi\EticketController::destroy');
});

Route::prefix('v1/poseticketreturn')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'POSETicketReturnController@config')->name('AdminApi\POSETicketReturnController::config');
    Route::get('/list', 'POSETicketReturnController@list')->name('AdminApi\POSETicketReturnController::list');

    Route::post('/eticket/add', 'POSETicketReturnController@eticketAdd')->name('AdminApi\POSETicketReturn::');
    Route::get('/eticket/search', 'POSETicketReturnController@eticketSearch')->name('AdminApi\POSETicketReturn::');

    Route::get('/', 'POSETicketReturnController@index')->name('AdminApi\POSETicketReturnController::index');
    Route::get('/{data}', 'POSETicketReturnController@show')->name('AdminApi\POSETicketReturnController::show');
    Route::match(['put', 'post'], '/{Oid?}', 'POSETicketReturnController@save')->name('AdminApi\POSETicketReturnController::save');
    Route::delete('/{data}', 'POSETicketReturnController@destroy')->name('AdminApi\POSETicketReturnController::destroy');
    
    Route::post('/{data}/post', 'POSETicketReturnController@statusPost');
});

Route::prefix('v1/poseticketwithdraw')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'POSETicketWithdrawController@config')->name('AdminApi\POSETicketwithdraw::config');
    Route::get('/list', 'POSETicketWithdrawController@list')->name('AdminApi\POSETicketwithdraw::list');
    Route::get('/', 'POSETicketWithdrawController@index')->name('AdminApi\POSETicketwithdraw::index');
    Route::get('/{data}', 'POSETicketWithdrawController@show')->name('AdminApi\POSETicketwithdraw::show');
    Route::match(['put', 'post'], '/{Oid?}', 'POSETicketWithdrawController@save')->name('AdminApi\POSETicketwithdraw::save');
    Route::delete('/{data}', 'POSETicketWithdrawController@destroy')->name('AdminApi\POSETicketwithdraw::destroy');
});
