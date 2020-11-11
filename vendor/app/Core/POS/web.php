<?php

Route::get('e/{key}', 'DownloadETicketController@index')->name('Core\POS::eticket');
Route::get('p/{key}', 'DownloadETicketController@index2')->name('Core\POS::eticket2');
Route::get('x/{key}', 'DownloadETicketController@index3')->name('Core\Export::excel');
Route::get('r/{key}', 'DownloadETicketController@exportReport')->name('Core\Export::report');

Route::post('pos/{id}/eticket/send', 'POSETicketController@send')->name('Core\POS::eticket.send_web');
