<?php

Route::prefix('v1/period')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'PeriodController@index')->name('AdminApi\PeriodController::index');
    Route::post('/{data}/process', 'PeriodController@process');
    Route::post('/{data}/open', 'PeriodController@open');
    Route::post('/{data}/close', 'PeriodController@close');
    Route::post('/process/pos', 'PeriodController@pos');
});

Route::prefix('v1/account')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/dashboard/config', 'AccountDashboardController@config');
    Route::get('/dashboard/field', 'AccountDashboardController@field');
    Route::get('/dashboard/list', 'AccountDashboardController@list');
    Route::get('/dashboard/presearch', 'AccountDashboardController@presearch');
    Route::get('/history/config', 'AccountDashboardController@historyConfig');
    Route::get('/history/list', 'AccountDashboardController@historyList');
    Route::get('/history/presearch', 'AccountDashboardController@historyPresearch');

    Route::get('/config', 'AccountController@config');
    Route::get('/presearch', 'AccountController@presearch');
    Route::get('/list', 'AccountController@list');
    Route::get('/', 'AccountController@index');
    Route::get('/{data}', 'AccountController@show');
    Route::match(['put', 'post'], '/{Oid?}', 'AccountController@save');
    Route::delete('/{data}', 'AccountController@destroy');
});

Route::prefix('v1/generaljournal')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/config', 'GeneralJournalController@config')->name('AdminApi\GeneralJournal::config');
    Route::get('/list', 'GeneralJournalController@list')->name('AdminApi\GeneralJournal::list');
    
    Route::match(['put', 'post'], '/{Oid?}', 'GeneralJournalController@save')->name('AdminApi\GeneralJournal::save');
    Route::post('/{data}/post', 'GeneralJournalController@post');
    Route::post('/{data}/unpost', 'GeneralJournalController@unpost');
    Route::post('/{data}/cancelled', 'GeneralJournalController@cancelled');
    Route::get('/', 'GeneralJournalController@index')->name('AdminApi\GeneralJournal::index');
    Route::get('/{data}', 'GeneralJournalController@show')->name('AdminApi\GeneralJournal::show');
    Route::delete('/{data}', 'GeneralJournalController@destroy')->name('AdminApi\GeneralJournal::destroy');    
});

Route::prefix('v1/cashbank')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/dashboard/config', 'CashBankDashboardController@cashbankConfig')->name('AdminApi\Account::config');
    Route::get('/dashboard/list', 'CashBankDashboardController@cashbankList')->name('AdminApi\Account::list');
    Route::get('/dashboard/presearch', 'CashBankDashboardController@cashbankPresearch')->name('AdminApi\Account::presearch');

    Route::post('/createdetail', 'CashBankController@createdetail')->name('AdminApi\CashBank::createdetail');
    Route::get('/balance/list', 'CashBankController@listcashbank')->name('AdminApi\CashBank::listcashbank');
    Route::get('/presearch', 'CashBankController@presearch')->name('AdminApi\CashBank::presearch');
    Route::get('/balance/config', 'CashBankController@listcashbankconfig')->name('AdminApi\CashBank::listcashbank');
    Route::get('/balance/check/{data}', 'CashBankController@balanceCashBank')->name('AdminApi\CashBank::receipt.invoice');
    Route::post('/reconcile', 'CashBankController@reconcile')->name('AdminApi\CashBank::reconcile');
    Route::post('/unreconcile', 'CashBankController@unreconcile')->name('AdminApi\CashBank::unreconcile');


    Route::delete('/invoice/delete/{data}', 'CashBankController@invoicedelete');
    Route::match(['put','post'], '/detail', 'CashBankController@savedetail')->name('AdminApi\CashBank::payment.savedetail');
    Route::match(['put', 'post'], '/{Oid?}', 'CashBankController@save')->name('AdminApi\CashBank::payment.save');
    Route::get('/config', 'CashBankController@config')->name('AdminApi\CashBank::config');
    Route::get('/list', 'CashBankController@list')->name('AdminApi\CashBank::list');
    Route::get('/', 'CashBankController@index')->name('AdminApi\CashBank::index');
    Route::get('/{data}', 'CashBankController@show')->name('AdminApi\CashBank::show');
    Route::get('/journal/{data}', 'CashBankController@journal')->name('AdminApi\CashBank::journal');
    
    Route::post('/invoice/add', 'CashBankController@invoiceAdd')->name('AdminApi\CashBank::partialinvoiceadd');
    Route::get('/invoice/search', 'CashBankController@invoiceSearch')->name('AdminApi\CashBank::payment.invoice');
    Route::get('/receipt/invoice', 'ReceiptController@invoice')->name('AdminApi\CashBank::receipt.invoice');


    Route::match(['put', 'post'], '/transfer/{Oid?}', 'TransferController@save')->name('AdminApi\CashBank::transfer.save');
    Route::post('/{data}/post', 'CashBankController@post');
    Route::post('/{data}/unpost', 'CashBankController@unpost');
    Route::post('/{data}/cancelled', 'CashBankController@cancelled');
    Route::delete('/{data}', 'CashBankController@destroy')->name('AdminApi\CashBank::destroy');    
});

Route::prefix('v1/cashbanksubmission')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/presearch', 'CashBankSubmissionController@presearch')->name('AdminApi\GeneralJournal::presearch');
    Route::get('/config', 'CashBankSubmissionController@config')->name('AdminApi\GeneralJournal::config');
    Route::get('/list', 'CashBankSubmissionController@list')->name('AdminApi\GeneralJournal::list');
    
    Route::match(['put', 'post'], '/{Oid?}', 'CashBankSubmissionController@save')->name('AdminApi\GeneralJournal::save');
    Route::get('/', 'CashBankSubmissionController@index')->name('AdminApi\GeneralJournal::index');
    Route::get('/{data}', 'CashBankSubmissionController@show')->name('AdminApi\GeneralJournal::show');
    Route::delete('/{data}', 'CashBankSubmissionController@destroy')->name('AdminApi\GeneralJournal::destroy');    
});

Route::prefix('v1/journal')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'JournalController@index')->name('AdminApi\Journal::index');
});

Route::prefix('v1/stockjournal')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/{Oid}', 'StockController@index')->name('AdminApi\Stock::index');
});