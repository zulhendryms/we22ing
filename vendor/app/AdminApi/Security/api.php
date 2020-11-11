<?php

Route::prefix('v1')->middleware(['cors'])->group(function () {
    Route::get('/connection', 'ConnectionController@index');
    Route::get('/test', 'ConnectionController@test');
    Route::post('/notification/test', 'NotificationController@test');
    Route::get('/version', 'ConnectionController@version');
});

Route::prefix('v1')->middleware(['cors'])->group(function () {
    Route::post('/login', 'LoginController@login');
    Route::post('/signin', 'LoginController@loginDev');
}); 

Route::prefix('v1/user')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/company', 'LoginController@changeCompany');
    Route::get('/quickmenu', 'LoginController@quickMenu');
    Route::get('/config', 'UserController@config');
    Route::get('/list', 'UserController@list');
    Route::get('/', 'UserController@index');
    Route::post('/logon', 'LoginController@index');
    Route::post('/logout', 'LoginController@logout');
    Route::get('/favmenu', 'UserController@favmenu');
    Route::post('/notification/create', 'NotificationController@create');
    Route::get('/notification/list', 'NotificationController@list');
    Route::get('/notification', 'NotificationController@quick');
    Route::post('/notification/read/{oid?}', 'NotificationController@read');
    Route::post('/{Oid}', 'UserController@save')->name('AdminApi\User::save');
    Route::get('/{data}', 'UserController@show');
    Route::get('/poshome', 'UserController@posHome');
});

Route::prefix('v1/alluser')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/autocomplete', 'AllUserController@autocomplete');
    Route::get('/config', 'AllUserController@config');
    Route::get('/list', 'UserController@list');
    Route::get('/{data}', 'UserController@show');
    Route::get('/', 'AllUserController@index');
    Route::post('/create', 'UserController@save')->name('AdminApi\User::save');
    // Route::post('/create', 'AllUserController@create');
    Route::put('/{Oid}', 'AllUserController@save')->name('AdminApi\User::save');
    Route::get('/{data}', 'AllUserController@show')->name('AdminApi\User::show');
    Route::delete('/{data}', 'AllUserController@destroy')->name('AdminApi\User::destroy');
    Route::post('/reset', 'AllUserController@reset');
});

Route::prefix('v1/role')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'RoleController@index')->name('AdminApi\Role::index');
    Route::match(['put', 'post'], '/{Code?}', 'RoleController@save')->name('AdminApi\Role::save');
    Route::delete('/{code}', 'RoleController@destroy')->name('AdminApi\Role::destroy');
});

Route::prefix('v1/rolemodule')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/disablefield', 'RoleModuleController@disablefield')->name('AdminApi\RoleModule::disablefield');
    Route::get('/', 'RoleModuleController@index')->name('AdminApi\RoleModule::index');
    Route::get('/{data}', 'RoleModuleController@show')->name('AdminApi\RoleModule::show');
    Route::put('/', 'RoleModuleController@update')->name('AdminApi\RoleModule::update');
    Route::put('/custom', 'RoleModuleController@updateCustom')->name('AdminApi\RoleModule::updateCustom');
});

Route::prefix('v1/menu')->middleware(['cors', 'auth:api'])->group(function () {
    Route::post('/generate/all', 'RoleModuleController@generateAllMenu')->name('AdminApi\RoleModule::generateAllMenu');
    Route::post('/generate/{role}', 'RoleModuleController@generateMenu')->name('AdminApi\RoleModule::generateMenu');
});

Route::prefix('v1/fav')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'UserController@favouriteGet');
    Route::post('/create', 'UserController@favouritePost');
    Route::delete('/', 'UserController@favouriteRemove')->name('AdminApi\Fav::destroy');
});

Route::prefix('v1/admindev')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'DashboardAdminDevController@index')->name('AdminApi\AdminDev::index');
});


Route::prefix('v1/initial')->middleware(['cors', 'auth:api'])->group(function () {
    Route::get('/', 'LoginController@initial')->name('AdminApi\Login::initial');
});