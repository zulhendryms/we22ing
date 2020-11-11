<?php

Route::post('/login', 'LoginController@store')->name('Core\Security\Web::login.store');

Route::get('/login/external/{provider}', 'SocialLoginController@index')->name('Core\Security\Web::login_external');
Route::get('/login/external/{provider}/callback', 'SocialLoginController@store')->name('Core\Security\Web::login_external_callback');

Route::delete('/logout', 'LoginController@destroy')->name('Core\Security\Web::logout');

Route::post('/register', 'RegisterController@store')->name('Core\Security\Web::register.store');
