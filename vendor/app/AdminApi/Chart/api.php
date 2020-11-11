<?php

Route::prefix('v1/chart')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/pos', 'ChartController@chartpos');
    Route::get('/line', 'ChartController@chartline');
    Route::get('/browser', 'ChartController@chartbrowser');
    Route::get('/radialbar', 'ChartController@chartradialbar');
    Route::get('/retentionbar', 'ChartController@chartretentionbar');
    Route::get('/pie', 'ChartController@chartpie');
    Route::get('/salesbar', 'ChartController@chartsalesbar');
    Route::get('/timeline', 'ChartController@charttimeline');
    Route::get('/linechart', 'ChartController@linechart');
    Route::get('/lineareachart', 'ChartController@lineareachart');
    Route::get('/barchart', 'ChartController@barchart');
    Route::get('/columchart', 'ChartController@columchart');
    Route::get('/piechart', 'ChartController@piechart');
    Route::get('/doughnutchart', 'ChartController@doughnutchart');

    Route::get('/areasquare/posamount', 'AreaSquareController@posamount'); 
    Route::get('/areasquare/posquantity', 'AreaSquareController@posquantity');          
    Route::get('/areasquare/posgroupbya', 'AreaSquareController@posgroupbya');           
    Route::get('/areasquare/posgroupbyb', 'AreaSquareController@posgroupbyb');         
    Route::get('/areasquare/posamountoneweek', 'AreaSquareController@posamountoneweek');         
    Route::get('/areasquare/posgroupbycustomer', 'AreaSquareController@posgroupbycustomer');  
    Route::get('/areasquare/posgroupbyuser', 'AreaSquareController@posgroupbyuser');   
    
    Route::get('/areasquare/data', 'AreaSquareController@data'); 
    Route::get('/linechart/data', 'LineChartController@data'); 
    Route::get('/piechart/data', 'PieChartController@data'); 
    Route::get('/listchart/data', 'ListChartController@data'); 
    Route::get('/barchart/data', 'BarChartController@data'); 

    Route::get('/dashboard', 'DashboardChartController@generate'); 
    Route::get('/dashboard/query', 'DashboardChartController@getListQuery'); 
        
});