<?php

Route::prefix('v1/prereport')->middleware(['cors', 'auth:api'])->group(function () {
    Route::match(['get','post'], '/purchaseorder', 'PreReportPurchaseOrderController@report');
    Route::match(['get','post'], '/cashbank', 'PreReportCashBankController@report');
    Route::match(['get','post'], '/purchaseinvoice', 'PreReportPurchaseInvoiceController@report');

    Route::get('/invoice','PreReportInvoiceController@report')->name('AdminApi\Report::prereport');
    Route::get('/salesinvoice','PreReportSalesInvoiceController@report')->name('AdminApi\Report::prereport');
    Route::get('/purchaserequest','PreReportPurchaseRequestController@report')->name('AdminApi\Report::prereport');
    Route::get('/purchasedelivery/{Oid}','PreReportPurchaseDeliveryController@report')->name('AdminApi\Report::prereport');
    Route::get('/cashbanksubmission','PrereportCashBankSubmissionController@report')->name('AdminApi\Report::prereportcashbank_submission');
    Route::get('/purchaserequestversion/{Oid}','PreReportPurchaseRequestVersionController@report')->name('AdminApi\Report::prereport');
    Route::get('/pointofsale/{Oid}','PreReportPointOfSaleController@report')->name('AdminApi\Report::prereport');
    Route::get('/traveltransaction','PreReportTravelTransactionController@report')->name('AdminApi\Report::prereport');
    Route::get('/eticket/{Oid}','PreReportETicketController@report')->name('AdminApi\Report::prereport');
    Route::get('/transactionfuel','PrereportTransactionFuelController@report')->name('AdminApi\Report::prereportTransactionFuel');
    // Route::get('/purchaseorder','PreReportPurchaseOrderController@report')->name('AdminApi\Report::prereport');
    // Route::get('/purchaseinvoice','PreReportPurchaseInvoiceController@report')->name('AdminApi\Report::prereport');
    // Route::get('/cashbank','PreReportCashBankController@report')->name('AdminApi\Report::prereport');

});