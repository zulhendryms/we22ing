<?php

Route::prefix('v1/publicapproval')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'PublicApprovalController@list');
    Route::get('/config', 'PublicApprovalController@config');
    Route::get('/dashboard', 'PublicApprovalController@dashboard');
    Route::get('/presearch', 'PublicApprovalController@presearch');
    Route::post('/entry', 'PublicApprovalController@statusEntry');
    Route::post('/request', 'PublicApprovalController@statusRequest');
    Route::post('/approve', 'PublicApprovalController@statusApprove');
    Route::post('/reject', 'PublicApprovalController@statusReject');
    Route::post('/submit', 'PublicApprovalController@statusSubmit');
});

Route::prefix('v1/publiccomment')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/create', 'PublicCommentController@create')->name('AdminApi\PublicComment::create');
    Route::get('/list', 'PublicCommentController@list')->name('AdminApi\PublicComment::list');
    Route::get('/config', 'PublicCommentController@config')->name('AdminApi\PublicComment::config');
    Route::get('/', 'PublicCommentController@index')->name('AdminApi\PublicComment::index');
    Route::get('/{data}', 'PublicCommentController@show')->name('AdminApi\PublicComment::show');
    Route::match(['put', 'post'], '/{Oid?}', 'PublicCommentController@save')->name('AdminApi\PublicComment::save');
    Route::delete('/{data}', 'PublicCommentController@destroy')->name('AdminApi\PublicComment::destroy');
});

Route::prefix('v1/file')->middleware(['cors', 'auth:api'])->group(function () {
    // Route::get('/config', 'PublicFileController@config')->name('AdminApi\Item::config');
    Route::post('/upload', 'PublicFileController@upload')->name('AdminApi\File::upload');
    // Route::post('/migrasi', 'PublicFileController@migrasiImage')->name('AdminApi\File::migrasi');
    Route::delete('/{data}', 'PublicFileController@deleteFile')->name('AdminApi\File::deletefile');
    // Route::get('/list', 'PublicFileController@list')->name('AdminApi\Item::list');
    // Route::get('/{data}', 'PublicFileController@show')->name('Admin\Item::getItemTransport');
    // Route::get('/', 'PublicFileController@index')->name('Admin\Item::getItemTransport');
    // Route::match(['put', 'post'], '/{Oid?}', 'PublicFileController@save')->name('AdminApi\::email');
    // Route::delete('/{Oid}', 'PublicFileController@destroy')->name('AdminApi\::deleteemail');
});

Route::prefix('v1/publicdashboard')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'PublicDashboardController@config')->name('AdminApi\PublicDashboard::config');
    Route::get('/list', 'PublicDashboardController@list')->name('AdminApi\PublicDashboard::list');
    Route::get('/', 'PublicDashboardController@index')->name('AdminApi\PublicDashboard::index');
    Route::get('/get', 'PublicDashboardController@generate')->name('AdminApi\PublicDashboard::generate');
    Route::get('/{data}', 'PublicDashboardController@show')->name('AdminApi\PublicDashboard::show');
    Route::match(['put', 'post'], '/{Oid?}', 'PublicDashboardController@save')->name('AdminApi\PublicDashboard::save');
    Route::delete('/{data}', 'PublicDashboardController@destroy')->name('AdminApi\PublicDashboard::destroy');
});

Route::prefix('v1/publicapprovalsetup')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/create', 'PublicApprovalSetupController@create')->name('AdminApi\publicapprovalsetup::create');
    Route::get('/list', 'PublicApprovalSetupController@list')->name('AdminApi\publicapprovalsetup::list');
    Route::get('/config', 'PublicApprovalSetupController@config')->name('AdminApi\publicapprovalsetup::config');
    Route::get('/', 'PublicApprovalSetupController@index')->name('AdminApi\publicapprovalsetup::index');
    Route::get('/{data}', 'PublicApprovalSetupController@show')->name('AdminApi\publicapprovalsetup::show');
    Route::match(['put', 'post'], '/{Oid?}', 'PublicApprovalSetupController@save')->name('AdminApi\publicapprovalsetup::save');
    Route::delete('/{data}', 'PublicApprovalSetupController@destroy')->name('AdminApi\publicapprovalsetup::destroy');
});
