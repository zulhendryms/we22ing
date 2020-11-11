<?php

Route::prefix('v1/report')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/account','ReportAccountController@report')->name('AdminApi\Report::report');
    Route::get('/arap','ReportARAPController@report')->name('AdminApi\Report::report');
    Route::get('/balancesheet','ReportBalanceSheetController@report')->name('AdminApi\Report::report');
    Route::get('/businesspartner','ReportBusinessPartnerController@report')->name('AdminApi\Report::report');
    Route::get('/cashbank','ReportCashBankController@report')->name('AdminApi\Report::report');
    Route::get('/item','ReportItemController@report')->name('AdminApi\Report::report');
    Route::get('/ledgerbook','ReportLedgerBookController@report')->name('AdminApi\Report::report');
    Route::get('/profitloss','ReportProfitLossController@report')->name('AdminApi\Report::report');
    Route::get('/accountlist','ReportAccountController@report')->name('AdminApi\Report::report');
    Route::get('/customer','ReportCustomerController@report')->name('AdminApi\Report::report');
    Route::get('/supplier','ReportSupplierController@report')->name('AdminApi\Report::report');
    Route::get('/stockvalue','ReportStockValueController@report')->name('AdminApi\Report::report');
    Route::get('/stock','ReportStockController@report')->name('AdminApi\Report::report');
    Route::get('/stock/config','ReportStockController@config')->name('AdminApi\Report::report');
    Route::get('/stockadjustment','ReportStockAdjustmentController@report')->name('AdminApi\Report::report');
    Route::get('/faktur/{Oid}','ReportFakturController@report')->name('AdminApi\Report::report');
    Route::get('/fakturpayment/{Oid}','ReportPaymentPrePaymentController@report')->name('AdminApi\Report::report');
    Route::get('/summaryperbill/{Session}','ReportSummaryPerBillController@report')->name('AdminApi\Report::report');
    Route::get('/summaryperitem/{Session}','ReportSummaryPerItemController@report')->name('AdminApi\Report::report');
    Route::get('/summaryperpayment/{Session}','ReportSummaryPerPaymentController@report')->name('AdminApi\Report::report');
    Route::get('/posinvoice/{Oid}','ReportPOSInvoiceController@report')->name('AdminApi\Report::report');
    Route::get('/dataproblem','ReportDataProblemController@report')->name('AdminApi\Report::report');
    Route::get('/possession','ReportPOSSessionController@report')->name('AdminApi\Report::report');
    Route::get('/stocketicket','ReportStockEticketController@report')->name('AdminApi\Report::report');
    Route::get('/profitlosstravel','ReportProfitLossTravelController@report')->name('AdminApi\Report::report');
    Route::get('/activitylist','ReportActivityListController@report')->name('AdminApi\Report::report');
    Route::get('/traveltransaction','ReportTravelTransactionController@report')->name('AdminApi\Report::report');
    Route::get('/travelstock','ReportTravelStockController@report')->name('AdminApi\Report::report');
    Route::get('/travelloa','ReportTravelLOAController@report')->name('AdminApi\Report::report');
    Route::get('/traveloutbound','ReportTravelOutboundController@report')->name('AdminApi\Report::report');
    Route::get('/transactionfuel','ReportTruckingTransactionFuelController@report')->name('AdminApi\Report::report');
});

Route::prefix('v1/reportviewer')->middleware(['cors'])->group(function () {
    Route::get('/{reportName}','ReportAccountController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportARAPController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportBalanceSheetController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportBusinessPartnerController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportCashBankController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportItemController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportLedgerBookController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportProfitLossController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportAccountController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportStockAdjustmentController@view')->name('AdminApi\Report::view'); //zz
    Route::get('/{reportName}','ReportPaymentPrePaymentController@view')->name('AdminApi\Report::view'); //zz
    Route::get('/{reportName}','ReportCustomerController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportSupplierController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportStockValueController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportStockController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportFakturController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportSummaryPerBillController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportSummaryPerItemController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportSummaryPerPaymentController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportPOSInvoiceController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportDataProblemController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportPOSSessionController@view')->name('AdminApi\Report::view');
    Route::get('/{reportName}','ReportStockEticketController@view')->name('AdminApi\Report::view');
});
