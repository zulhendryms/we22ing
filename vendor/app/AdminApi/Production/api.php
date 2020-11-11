<?php

Route::prefix('v1/productionprocess')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionProcessController@list')->name('AdminApi\ProductionProcess::list');
    Route::get('/config', 'ProductionProcessController@config')->name('AdminApi\ProductionProcess::config');
    Route::get('/', 'ProductionProcessController@index')->name('AdminApi\ProductionProcess::index');
    Route::get('/{data}', 'ProductionProcessController@show')->name('AdminApi\ProductionProcess::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionProcessController@save')->name('AdminApi\ProductionProcess::save');
    Route::delete('/{data}', 'ProductionProcessController@destroy')->name('AdminApi\ProductionProcess::destroy');
});

Route::prefix('v1/productionreviewspesification')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'ProductionReviewSpecificationController@presearch')->name('AdminApi\Productionreview::presearch');
    Route::get('/list', 'ProductionReviewSpecificationController@list')->name('AdminApi\Productionreview::list');
    Route::get('/config', 'ProductionReviewSpecificationController@config')->name('AdminApi\Productionreview::config');
    Route::get('/', 'ProductionReviewSpecificationController@index')->name('AdminApi\Productionreview::index');
    Route::get('/{data}', 'ProductionReviewSpecificationController@show')->name('AdminApi\Productionreview::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionReviewSpecificationController@save')->name('AdminApi\Productionreview::save');
    Route::delete('/{data}', 'ProductionReviewSpecificationController@destroy')->name('AdminApi\Productionreview::destroy');
});

Route::prefix('v1/productionquestionnaire')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/process', 'ProductionQuestionnaireController@showProcess')->name('AdminApi\ProductionQuestionnaire::showProcess');
    Route::get('/list', 'ProductionQuestionnaireController@list')->name('AdminApi\ProductionQuestionnaire::list');
    Route::get('/config', 'ProductionQuestionnaireController@config')->name('AdminApi\ProductionQuestionnaire::config');
    Route::get('/', 'ProductionQuestionnaireController@index')->name('AdminApi\ProductionQuestionnaire::index');
    Route::get('/{data}', 'ProductionQuestionnaireController@show')->name('AdminApi\ProductionQuestionnaire::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionQuestionnaireController@save')->name('AdminApi\ProductionQuestionnaire::save');
    Route::delete('/{data}', 'ProductionQuestionnaireController@destroy')->name('AdminApi\ProductionQuestionnaire::destroy');
});

Route::prefix('v1/productionthickness')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionThicknessController@list')->name('AdminApi\ProductionThickness::list');
    Route::get('/config', 'ProductionThicknessController@config')->name('AdminApi\ProductionThickness::config');
    Route::get('/', 'ProductionThicknessController@index')->name('AdminApi\ProductionThickness::index');
    Route::get('/{data}', 'ProductionThicknessController@show')->name('AdminApi\ProductionThickness::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionThicknessController@save')->name('AdminApi\ProductionThickness::save');
    Route::delete('/{data}', 'ProductionThicknessController@destroy')->name('AdminApi\ProductionThickness::destroy');
});

Route::prefix('v1/productionshape')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionShapeController@list')->name('AdminApi\ProductionShape::list');
    Route::get('/config', 'ProductionShapeController@config')->name('AdminApi\ProductionShape::config');
    Route::get('/', 'ProductionShapeController@index')->name('AdminApi\ProductionShape::index');
    Route::get('/{data}', 'ProductionShapeController@show')->name('AdminApi\ProductionShape::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionShapeController@save')->name('AdminApi\ProductionShape::save');
    Route::delete('/{data}', 'ProductionShapeController@destroy')->name('AdminApi\ProductionShape::destroy');
});

Route::prefix('v1/productionprice')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'ProductionPriceController@config')->name('AdminApi\ProductionPrice::config');
    Route::get('/list', 'ProductionPriceController@list')->name('AdminApi\ProductionPrice::list');
    Route::get('/', 'ProductionPriceController@index')->name('AdminApi\ProductionPrice::index');
    Route::get('/{data}', 'ProductionPriceController@show')->name('AdminApi\ProductionPrice::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionPriceController@save')->name('AdminApi\ProductionPrice::save');
    Route::delete('/{data}', 'ProductionPriceController@destroy')->name('AdminApi\ProductionPrice::destroy');
});

Route::prefix('v1/productionorder')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionOrderController@list')->name('AdminApi\ProductionOrder::list');
    Route::post('/generate', 'ProductionOrderController@generateDescription')->name('AdminApi\ProductionOrder::generateDescription');
    Route::get('/', 'ProductionOrderController@index')->name('AdminApi\ProductionOrder::index');
    Route::get('/{data}', 'ProductionOrderController@show')->name('AdminApi\ProductionOrder::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionOrderController@save')->name('AdminApi\ProductionOrder::save');
    Route::delete('/{data}', 'ProductionOrderController@destroy')->name('AdminApi\ProductionOrder::destroy');
    Route::post('/{data}/post', 'ProductionOrderController@post');
    Route::post('/{data}/entry', 'ProductionOrderController@entry');
    Route::post('/{data}/quoted', 'ProductionOrderController@quoted');
    Route::post('/{data}/cancelled', 'ProductionOrderController@cancelled');
    Route::get('/{data}/quotation', 'ProductionOrderController@quotation');
});

Route::prefix('v1/productionorderitem')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'ProductionOrderItemController@index')->name('AdminApi\ProductionOrderItem::index');
    Route::get('/listorderitemprocess', 'ProductionOrderItemController@listorderitemprocess')->name('AdminApi\ProductionOrderItem::listorderitemprocess');
    Route::get('/{data}', 'ProductionOrderItemController@show')->name('AdminApi\ProductionOrderItem::show');
    Route::post('/create', 'ProductionOrderItemController@create')->name('AdminApi\ProductionOrderItem::create');
    Route::post('/saveorderitemprocess', 'ProductionOrderItemController@saveorderitemprocess')->name('AdminApi\ProductionOrderItem::saveorderitemprocess');
    Route::put('/edit/{Oid}', 'ProductionOrderItemController@edit')->name('AdminApi\ProductionOrderItem::edit');
    Route::delete('/{data}', 'ProductionOrderItemController@destroy')->name('AdminApi\ProductionOrderItem::destroy');
});

Route::prefix('v1/productionorderdetail')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'ProductionOrderDetailController@index')->name('AdminApi\ProductionOrderDetail::index');
    Route::get('/{data}', 'ProductionOrderDetailController@show')->name('AdminApi\ProductionOrderDetail::show');
    Route::post('/save', 'ProductionOrderDetailController@save')->name('AdminApi\ProductionOrderDetail::save');
    // Route::post('/create', 'ProductionOrderDetailController@create')->name('AdminApi\ProductionOrderDetail::create');
    // Route::put('/edit/{Oid}', 'ProductionOrderDetailController@edit')->name('AdminApi\ProductionOrderDetail::edit');
    Route::delete('/{data}', 'ProductionOrderDetailController@destroy')->name('AdminApi\ProductionOrderDetail::destroy');
});

Route::prefix('v1/production')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'ProductionController@index')->name('AdminApi\Production::index');
    Route::get('/list', 'ProductionController@listProduction')->name('AdminApi\Production::listproduction');
    Route::get('/listitem', 'ProductionController@listProductionItem')->name('AdminApi\Production::listproductionitem');
    Route::get('/listitemshipping', 'ProductionController@listProductionItemShipping')->name('AdminApi\Production::listProductionItemShipping');
    Route::get('/listdetail', 'ProductionController@listProductionDetail')->name('AdminApi\Production::listproductiondetail');
    Route::get('/{data}', 'ProductionController@show')->name('AdminApi\Production::show');    
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionController@save')->name('AdminApi\Production::save');
    Route::delete('/{data}', 'ProductionController@destroy')->name('AdminApi\Production::destroy');
});

Route::prefix('v1/stockupdate')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionStockController@list')->name('AdminApi\ProductionStock::list');
    Route::get('/listdetail', 'ProductionStockController@listDetail')->name('AdminApi\ProductionStock::listdetail');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionStockController@save')->name('AdminApi\ProductionStock::save');
    Route::delete('/{data}', 'ProductionStockController@destroy')->name('AdminApi\ProductionStock::destroy');
});

Route::prefix('v1/tracking')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/summary', 'ProductionTrackingController@trackingsummary')->name('AdminApi\ProductionTracking::trackingsummary');
    Route::get('/detail', 'ProductionTrackingController@trackingdetail')->name('AdminApi\ProductionTracking::trackingdetail');
    Route::get('/peritem', 'ProductionTrackingController@trackingperitem')->name('AdminApi\ProductionTracking::trackingperitem');
});

Route::prefix('v1/productionuserprocess')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionUserProcessController@list')->name('AdminApi\ProductionProcess::list');
    Route::get('/', 'ProductionUserProcessController@index')->name('AdminApi\ProductionUserProcess::index');
    Route::get('/{data}', 'ProductionUserProcessController@show')->name('AdminApi\ProductionUserProcess::show');
    Route::match(['put', 'post'], '/{Oid?}', 'ProductionUserProcessController@save')->name('AdminApi\ProductionUserProcess::save');
    Route::delete('/{data}', 'ProductionUserProcessController@destroy')->name('AdminApi\ProductionUserProcess::destroy');
});

Route::prefix('v1/productionpriceprocess')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionPriceProcessController@list')->name('AdminApi\ProductionProcess::list');
    Route::get('/detailconfig', 'ProductionPriceProcessController@detailconfig')->name('AdminApi\ProductionProcess::detailconfig');
    Route::get('/config', 'ProductionPriceProcessController@config')->name('AdminApi\ProductionProcess::config');
    Route::get('/', 'ProductionPriceProcessController@index')->name('AdminApi\ProductionPriceProcess::index');
    Route::get('/autocomplete', 'ProductionPriceProcessController@autocomplete')->name('AdminApi\ProductionPriceProcess::autocomplete');
    Route::get('/{data}', 'ProductionPriceProcessController@show')->name('AdminApi\ProductionPriceProcess::show');
    Route::post('/create', 'ProductionPriceProcessController@create')->name('AdminApi\ProductionPriceProcess::create');
    Route::put('/edit/{Oid}', 'ProductionPriceProcessController@edit')->name('AdminApi\ProductionPriceProcess::edit');
    Route::delete('/{data}', 'ProductionPriceProcessController@destroy')->name('AdminApi\ProductionPriceProcess::destroy');
});

Route::prefix('v1/productionunitconvertion')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'ProductionUnitConvertionController@list')->name('AdminApi\ProductionProcess::list');
    Route::get('/', 'ProductionUnitConvertionController@index')->name('AdminApi\ProductionUnitConvertion::index');
    Route::get('/{data}', 'ProductionUnitConvertionController@show')->name('AdminApi\ProductionUnitConvertion::show');
    Route::post('/create', 'ProductionUnitConvertionController@create')->name('AdminApi\ProductionUnitConvertion::create');
    Route::put('/edit/{Oid}', 'ProductionUnitConvertionController@edit')->name('AdminApi\ProductionUnitConvertion::edit');
    Route::delete('/{data}', 'ProductionUnitConvertionController@destroy')->name('AdminApi\ProductionUnitConvertion::destroy');
});

