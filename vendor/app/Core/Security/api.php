<?php

Route::post('/login', 'LoginController@login');
Route::get('/domain', 'LoginController@domain')->middleware('cors');
Route::get('/version', 'LoginController@version')->middleware('cors');