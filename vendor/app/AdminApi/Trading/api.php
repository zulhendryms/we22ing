<?php


Route::prefix('v1/purchaseinvoice')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/test', 'PurchaseInvoiceController@testProcess');
    Route::get('/presearch', 'PurchaseInvoiceController@presearch');
    Route::get('/config', 'PurchaseInvoiceController@config');
    Route::get('/list', 'PurchaseInvoiceController@list');
    Route::post('/relatedcashbank/{Oid}', 'PurchaseInvoiceController@relatedCashBank');
    Route::get('/partialdelivery', 'PurchaseInvoiceController@partialDelivery');
    Route::post('/partialdeliveryadd', 'PurchaseInvoiceController@partialDeliveryAdd');
    Route::get('/partialorder', 'PurchaseInvoiceController@partialOrder');
    Route::post('/partialorderadd', 'PurchaseInvoiceController@partialOrderAdd');
    Route::get('/reorderpurchaseinvoice', 'PurchaseInvoiceController@reorderPurchaseInvoiceDetail'); //reorder
    Route::post('/reorderpurchaseinvoiceupdate', 'PurchaseInvoiceController@reorderPurchaseInvoiceDetailUpdate'); //reorder
    Route::match(['put','post'], '/detail', 'PurchaseInvoiceController@savedetail');
    Route::match(['delete'], '/detail/{Oid}', 'PurchaseInvoiceController@deletedetail');
    Route::get('/export', 'PurchaseInvoiceController@export');
    Route::get('/payment', 'PurchaseInvoiceController@payment');
    Route::get('/', 'PurchaseInvoiceController@index');
    Route::get('/{data}', 'PurchaseInvoiceController@show');
    Route::post('/{data}/import', 'PurchaseInvoiceController@import');
    Route::post('/{data}/post', 'PurchaseInvoiceController@post');
    Route::post('/{data}/unpost', 'PurchaseInvoiceController@unpost');
    Route::post('/{data}/cancelled', 'PurchaseInvoiceController@cancelled');
    Route::post('/{data}/convert', 'PurchaseInvoiceController@convertStockTransfer');
    Route::match(['put', 'post'], '/{Oid?}', 'PurchaseInvoiceController@save');
    Route::delete('/{data}', 'PurchaseInvoiceController@destroy');
    Route::post('/{data}/convertcashbank', 'PurchaseInvoiceController@convertToCashBank');
    
});

Route::prefix('v1/purchasedelivery')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'PurchaseDeliveryController@presearch')->name('AdminApi\purchasedelivery::presearch');
    Route::get('/config', 'PurchaseDeliveryController@config')->name('AdminApi\purchasedelivery::config');
    Route::get('/list', 'PurchaseDeliveryController@list')->name('AdminApi\purchasedelivery::list');
    Route::get('/partialorder', 'PurchaseDeliveryController@partialOrder')->name('AdminApi\purchasedelivery::partialorder');
    Route::post('/partialorderadd', 'PurchaseDeliveryController@partialOrderAdd')->name('AdminApi\purchasedelivery::partialOrderAdd');
    Route::get('/reorderpurchasedelivery', 'PurchaseDeliveryController@reorderPurchaseDeliveryDetail'); //reorder
    Route::post('/reorderpurchasedeliveryupdate', 'PurchaseDeliveryController@reorderPurchaseDeliveryDetailUpdate'); //reorder
    Route::get('/', 'PurchaseDeliveryController@index')->name('AdminApi\purchasedelivery::index');
    Route::get('/{data}', 'PurchaseDeliveryController@show')->name('AdminApi\purchasedelivery::show');
    Route::match(['put', 'post'], '/{Oid?}', 'PurchaseDeliveryController@save')->name('AdminApi\purchasedelivery::save');
    Route::delete('/{data}', 'PurchaseDeliveryController@destroy')->name('AdminApi\purchasedelivery::destroy');

    Route::post('/{data}/convert', 'PurchaseDeliveryController@convertToPurchaseInvoice');
    Route::post('/{data}/post', 'PurchaseDeliveryController@statusPost');
    Route::post('/{data}/unpost', 'PurchaseDeliveryController@statusUnpost');
    Route::post('/{data}/cancelled', 'PurchaseDeliveryController@cancelled');
});

Route::prefix('v1/transactionstock')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'TransactionStockController@config')->name('AdminApi\transactionstock::config');
    Route::get('/list', 'TransactionStockController@list')->name('AdminApi\transactionstock::list');
    Route::get('/', 'TransactionStockController@index')->name('AdminApi\transactionstock::index');
    Route::get('/{data}', 'TransactionStockController@show')->name('AdminApi\transactionstock::show');
    Route::match(['put', 'post'], '/{Oid?}', 'TransactionStockController@save')->name('AdminApi\transactionstock::save');
    Route::delete('/{data}', 'TransactionStockController@destroy')->name('AdminApi\transactionstock::destroy');
    Route::post('/{data}/unpost', 'TransactionStockController@unpost');
    Route::post('/{data}/post', 'TransactionStockController@post');


});

Route::prefix('v1/purchaseorder')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'PurchaseOrderController@presearch')->name('AdminApi\purchaseorder::presearch');
    Route::get('/config', 'PurchaseOrderController@config')->name('AdminApi\purchaseorder::config');
    Route::get('/list', 'PurchaseOrderController@list')->name('AdminApi\purchaseorder::list');
    Route::post('/relatedpurchaseinvoice/{Oid}', 'PurchaseOrderController@relatedPurchaseInvoice');
    Route::post('/relatedpurchasedelivery/{Oid}', 'PurchaseOrderController@relatedPurchaseDelivery');
    Route::get('/reorderpurchaseorder', 'PurchaseOrderController@reorderPurchaseOrderDetail'); //reorder
    Route::post('/reorderpurchaseorderupdate', 'PurchaseOrderController@reorderPurchaseOrderDetailUpdate'); //reorder
    
    Route::get('/', 'PurchaseOrderController@index')->name('AdminApi\purchaseorder::index');
    Route::get('/listpurchasehistory/{data}', 'PurchaseOrderController@listPurchaseHistory')->name('AdminApi\purchaseorder::listpurchasehistory');
    Route::get('/{data}', 'PurchaseOrderController@show')->name('AdminApi\purchaseorder::show');
    Route::match(['put', 'post'], '/{Oid?}', 'PurchaseOrderController@save')->name('AdminApi\purchaseorder::save');
    Route::delete('/{data}', 'PurchaseOrderController@destroy')->name('AdminApi\purchaseorder::destroy');

    Route::post('/{data}/convert1', 'PurchaseOrderController@convertToPurchaseDelivery');
    Route::post('/{data}/convert2', 'PurchaseOrderController@convertToPurchaseInvoice');
    Route::post('/{data}/post', 'PurchaseOrderController@statusPost');
    Route::post('/{data}/unpost', 'PurchaseOrderController@statusUnpost');
    Route::post('/{data}/entry', 'PurchaseOrderController@statusEntry');
    Route::post('/{data}/cancel', 'PurchaseOrderController@statusCancel');
    Route::post('/{data}/convert', 'PurchaseOrderController@convertToPurchaseOrder');
});

Route::prefix('v1/salesorder')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'SalesOrderController@presearch')->name('AdminApi\purchaseorder::presearch');
    Route::get('/config', 'SalesOrderController@config')->name('AdminApi\purchaseorder::config');
    Route::get('/list', 'SalesOrderController@list')->name('AdminApi\purchaseorder::list');
    
    Route::post('/relatedsalesdelivery/{Oid}', 'SalesOrderController@relatedSalesDelivery');
    Route::post('/relatedsalesinvoice/{Oid}', 'SalesOrderController@relatedSalesInvoice');
    Route::get('/', 'SalesOrderController@index')->name('AdminApi\purchaseorder::index');
    Route::get('/{data}', 'SalesOrderController@show')->name('AdminApi\purchaseorder::show');
    Route::match(['put', 'post'], '/{Oid?}', 'SalesOrderController@save')->name('AdminApi\purchaseorder::save');
    Route::delete('/{data}', 'SalesOrderController@destroy')->name('AdminApi\purchaseorder::destroy');

    Route::post('/{data}/convertsd', 'SalesOrderController@convertToSalesDelivery');
    Route::post('/{data}/convertsi', 'SalesOrderController@convertToSalesInvoice');
    Route::post('/{data}/post', 'SalesOrderController@statusPost');
    Route::post('/{data}/unpost', 'SalesOrderController@statusUnpost');
    Route::post('/{data}/entry', 'SalesOrderController@statusEntry');
    Route::post('/{data}/cancel', 'SalesOrderController@statusCancel');
    Route::post('/{data}/convert', 'SalesOrderController@convertToPurchaseOrder');
});

Route::prefix('v1/poexport')->group(function () {
    Route::get('/', 'PurchaseInvoiceController@export');
});

Route::prefix('v1/salesinvoice')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'SalesInvoiceController@presearch')->name('AdminApi\SalesInvoice::presearch');
    Route::get('/list', 'SalesInvoiceController@list');
    Route::get('/config', 'SalesInvoiceController@config');
    Route::post('/relatedsalesinvoice/{Oid}', 'SalesInvoiceController@relatedCashBank');

    Route::get('/', 'SalesInvoiceController@index')->name('AdminApi\SalesInvoice::index');
    Route::get('/payment', 'SalesInvoiceController@payment');
    Route::get('/{data}', 'SalesInvoiceController@show')->name('AdminApi\SalesInvoice::show');
    Route::match(['put', 'post'], '/{Oid?}', 'SalesInvoiceController@save')->name('AdminApi\SalesInvoice::save');
    Route::post('/{data}/post', 'SalesInvoiceController@post');
    Route::post('/{data}/unpost', 'SalesInvoiceController@unpost');
    Route::post('/{data}/cancelled', 'SalesInvoiceController@cancelled');
    Route::post('/{data}/convert', 'SalesInvoiceController@convertToReceipt');
    Route::delete('/{data}', 'SalesInvoiceController@destroy')->name('AdminApi\SalesInvoice::destroy');
});

Route::prefix('v1/stock')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'StockController@index')->name('AdminApi\Stock::index');
});

Route::prefix('v1/stocktransfer')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'StockTransferController@config');
    Route::get('/list', 'StockTransferController@list');
    Route::get('/', 'StockTransferController@index')->name('AdminApi\StockTransfer::index');
    Route::get('/{data}', 'StockTransferController@show')->name('AdminApi\StockTransfer::show');
    Route::match(['put', 'post'], '/{Oid?}', 'StockTransferController@save')->name('AdminApi\StockTransfer::save');
    Route::delete('/{data}', 'StockTransferController@destroy')->name('AdminApi\StockTransfer::destroy');
    Route::post('/{data}/post', 'StockTransferController@post');
    Route::post('/{data}/unpost', 'StockTransferController@unpost');
    Route::post('/{data}/cancel', 'StockTransferController@cancelled');
});
