<?php

Route::group([
    'middleware' => 'auth:api'
], function() {
    Route::post('/traveltransaction/{id}/package', 'CreateFromPackageController@store');
    Route::post('/traveltransaction/{id}/calculate', 'TravelTransactionController@calculate');
    Route::post('/traveltransaction/{id}/complete', 'TravelTransactionController@complete');
    Route::post('/traveltransactiondetail/{id}/updateqty', 'TravelTransactionDetailController@updateQty');
    Route::post('/traveltransactiondetail/{id}/updatedate', 'TravelTransactionDetailController@updateDate');
    Route::post('/traveltransactiondetail/{id}/updateallotment', 'TravelTransactionDetailController@updateAllotment');
    Route::post('/traveltransactiondetail/{id}/deleteallotment', 'TravelTransactionDetailController@deleteAllotment');
    Route::post('/traveltransactiondetail/{id}/calculate', 'TravelTransactionDetailController@calculate');
    Route::post('/traveltransactiondetail/{id}/generate', 'TravelETicketController@generateForDetail');
    Route::post('/traveltransactiondetail/{id}/generateqty', 'TravelETicketController@generateByQty');
    Route::post('/traveltransactiondetail/{id}/generatemerchant', 'TravelETicketController@generateByMerchant');
    Route::post('/traveltransactiondetail/{id}/complete', 'TravelTransactionDetailController@setComplete');
    Route::post('/traveltransactiondetail/{id}/cancel', 'TravelTransactionDetailController@setCancel');
    Route::post('/traveltransactiondetail/{id}/email', 'TravelTransactionDetailController@sendEmail');
    Route::post('/traveltransactiondetail/{id}/emailuser', 'TravelTransactionDetailController@sendToUser');
    Route::post('/traveltransactiondetail/{id}/entry', 'TravelTransactionDetailController@setEntry');
    Route::post('/traveltransactiondetail/{id}/emailvendor', 'TravelTransactionDetailController@sendToVendor');
    Route::post('/travelpurchaseinvoice/{id}/post', 'TravelPurchaseInvoiceController@post');
    Route::post('/travelpurchaseinvoice/{id}/unpost', 'TravelPurchaseInvoiceController@unpost');
    Route::post('/traveltransactioncommission/{id}/post', 'TravelTransactionCommissionController@post');
    Route::post('/traveltransactioncommission/{id}/unpost', 'TravelTransactionCommissionController@unpost');
    Route::post('/traveltransactionarinvoice/{id}/post', 'TravelTransactionARInvoiceController@post');
    Route::post('/traveltransactionarinvoice/{id}/unpost', 'TravelTransactionARInvoiceController@unpost');
});