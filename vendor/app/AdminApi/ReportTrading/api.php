<?php
Route::prefix('v1/report')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/purchaseinvoice', 'ReportPurchaseInvoiceController@report')->name('AdminApi\Report::report purchase invoice');
    Route::get('/purchaseorder', 'ReportPurchaseOrderController@report')->name('AdminApi\Report::report purchase order');
    Route::get('/purchasedelivery', 'ReportPurchaseDeliveryController@report')->name('AdminApi\Report::report purchase delivery');
    Route::get('/purchaserequest', 'ReportPurchaseRequestController@report')->name('AdminApi\Report::report purchase request');
    Route::get('/salesinvoice', 'ReportSalesInvoiceController@report')->name('AdminApi\Report::report sales invoice');
    Route::get('/outstanding', 'ReportOutstandingPurchaseInvoiceController@report')->name('AdminApi\Report::report Hutang Piutang');
    Route::get('/fakturpurchaseinvoice/{Oid}', 'ReportPurchaseInvoicePreInvoiceController@report')->name('AdminApi\Report::report faktur PI');
    Route::get('/faktursalesinvoice/{Oid}', 'ReportSalesInvoicePreInvoiceController@report')->name('AdminApi\Report::report faktur SI');
    Route::get('/fakturpurchaseorder/{Oid}', 'ReportPurchaseOrderPreOrderController@report')->name('AdminApi\Report::report faktur PO');
});

Route::prefix('v1/reportviewer')->middleware(['cors'])->group(function () {
    Route::get('/{reportName}', 'ReportPurchaseInvoiceController@view')->name('AdminApi\Report::view');
});