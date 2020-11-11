<?php

Route::prefix('v1/reportproduction')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('production','ReportProductionController@report')->name('AdminApi\ReportProduction::report');
    Route::get('productionorder','ReportProductionOrderController@report')->name('AdminApi\ReportProduction::report');
    Route::get('quotation','ReportQuotationController@report')->name('AdminApi\ReportProduction::report');
    Route::get('/quotation/{Oid}','ReportPrintQuotationController@report')->name('AdminApi\ReportPrintQuotationController::report');
    Route::get('/order/{Oid}','ReportPrintOrderController@report')->name('AdminApi\ReportPrintOrderController::report');
    Route::get('/rejectcause','ReportRejectCauseController@report')->name('AdminApi\ReportProduction::report');
    Route::get('/salesgf','ReportSalesGFController@report')->name('AdminApi\ReportProduction::report');
});

Route::prefix('v1/reportproductionviewer')->middleware(['cors'])->group(function () {
    Route::get('/{reportName}','ReportProductionController@view')->name('AdminApi\ReportProduction::view');
    Route::get('/{reportName}','ReportProductionOrderController@view')->name('AdminApi\ReportProduction::view');
    Route::get('/{reportName}','ReportQuotationController@view')->name('AdminApi\ReportProduction::view');
    Route::get('/{reportName}','ReportPrintQuotationController@view')->name('AdminApi\ReportPrintQuotationController::view');
    Route::get('/{reportName}','ReportPrintOrderController@view')->name('AdminApi\ReportPrintOrderController::view');

});