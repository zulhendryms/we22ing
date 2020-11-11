<?php

Route::prefix('v1/humanresourceattendance')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'HumanResourceAttendanceController@list')->name('AdminApi\HRSAttendance::list');
    Route::get('/config', 'HumanResourceAttendanceController@config')->name('AdminApi\HRSAttendance::config');
    Route::get('/', 'HumanResourceAttendanceController@index')->name('AdminApi\HRSAttendance::index');
    Route::get('/{data}', 'HumanResourceAttendanceController@show')->name('AdminApi\HRSAttendance::show');
    Route::match(['put', 'post'], '/{Oid?}', 'HumanResourceAttendanceController@save')->name('AdminApi\HRSAttendance::save');
    Route::delete('/{data}', 'HumanResourceAttendanceController@destroy')->name('AdminApi\HRSAttendance::destroy');
});

Route::prefix('v1/humanresourceshiftrequest')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'HumanResourceShiftRequestController@list')->name('AdminApi\HRSShiftRequest::list');
    Route::get('/config', 'HumanResourceShiftRequestController@config')->name('AdminApi\HRSShiftRequest::config');
    Route::get('/', 'HumanResourceShiftRequestController@index')->name('AdminApi\HRSShiftRequest::index');
    Route::get('/{data}', 'HumanResourceShiftRequestController@show')->name('AdminApi\HRSShiftRequest::show');
    Route::match(['put', 'post'], '/{Oid?}', 'HumanResourceShiftRequestController@save')->name('AdminApi\HRSShiftRequest::save');
    Route::delete('/{data}', 'HumanResourceShiftRequestController@destroy')->name('AdminApi\HRSShiftRequest::destroy');
});

Route::prefix('v1/humanresourceleaverequest')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'HumanResourceLeaveRequestController@list')->name('AdminApi\HRSLeaveRequest::list');
    Route::get('/config', 'HumanResourceLeaveRequestController@config')->name('AdminApi\HRSLeaveRequest::config');
    Route::get('/', 'HumanResourceLeaveRequestController@index')->name('AdminApi\HRSLeaveRequest::index');
    Route::get('/{data}', 'HumanResourceLeaveRequestController@show')->name('AdminApi\HRSLeaveRequest::show');
    Route::match(['put', 'post'], '/{Oid?}', 'HumanResourceLeaveRequestController@save')->name('AdminApi\HRSLeaveRequest::save');
    Route::delete('/{data}', 'HumanResourceLeaveRequestController@destroy')->name('AdminApi\HRSLeaveRequest::destroy');
});

Route::prefix('v1/humanresourceovertimerequest')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/list', 'HumanResourceOvertimeRequestController@list')->name('AdminApi\HRSOvertimeRequest::list');
    Route::get('/config', 'HumanResourceOvertimeRequestController@config')->name('AdminApi\HRSOvertimeRequest::config');
    Route::get('/', 'HumanResourceOvertimeRequestController@index')->name('AdminApi\HRSOvertimeRequest::index');
    Route::get('/{data}', 'HumanResourceOvertimeRequestController@show')->name('AdminApi\HRSOvertimeRequest::show');
    Route::match(['put', 'post'], '/{Oid?}', 'HumanResourceOvertimeRequestController@save')->name('AdminApi\HRSOvertimeRequest::save');
    Route::delete('/{data}', 'HumanResourceOvertimeRequestController@destroy')->name('AdminApi\HRSOvertimeRequest::destroy');
});
?>