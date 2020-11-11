<?php

Route::prefix('v1/feed')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'FeedController@list')->name('AdminApi\FeedController::list');
    Route::post('/post/{Oid?}', 'FeedController@save')->name('AdminApi\FeedController::post');
    Route::post('/like', 'FeedController@postLike')->name('AdminApi\FeedController::postLike');
});

Route::prefix('v1/task')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/field', 'TaskController@field')->name('AdminApi\TaskController::field');
    Route::get('/list', 'TaskController@list')->name('AdminApi\TaskController::list');
    Route::get('/config', 'TaskController@config')->name('AdminApi\Task::config');
    Route::get('/presearch', 'TaskController@presearch')->name('AdminApi\Task::presearch');
    Route::post('/status/{data}', 'TaskController@statusChange')->name('AdminApi\Task::statusChange');
    Route::post('/start/{data}', 'TaskController@statusStart')->name('AdminApi\Task::statusStart');
    Route::post('/end/{data}', 'TaskController@statusEnd')->name('AdminApi\Task::statusEnd');
    Route::post('/open/{data}', 'TaskController@statusOpen')->name('AdminApi\Task::statusOpen');
    Route::post('/pending/{data}', 'TaskController@statusPending')->name('AdminApi\Task::statusPending');
    Route::post('/request/{data}', 'TaskController@statusRequest')->name('AdminApi\Task::statusRequest');
    Route::get('/', 'TaskController@index')->name('AdminApi\Task::index');
    Route::get('/{data}', 'TaskController@show')->name('AdminApi\Task::show');
    Route::match(['put', 'post'], '/update/{Oid?}', 'TaskController@saveEdit')->name('AdminApi\Task::save');
    Route::match(['put', 'post'], '/{Oid?}', 'TaskController@save')->name('AdminApi\Task::save');
    Route::delete('/{data}', 'TaskController@destroy')->name('AdminApi\Task::destroy');
});

Route::prefix('v1/tasktemp')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list/chart', 'TaskController@listChart')->name('AdminApi\TaskTempController::listChart');
});

Route::prefix('v1/tasklog')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'TaskLogController@list')->name('AdminApi\TaskLog::list');
    Route::get('/config', 'TaskLogController@config')->name('AdminApi\TaskLog::config');
    Route::get('/presearch', 'TaskLogController@presearch')->name('AdminApi\TaskLog::presearch');
    // Route::get('/', 'TaskLogController@index')->name('AdminApi\TaskLog::index');
});