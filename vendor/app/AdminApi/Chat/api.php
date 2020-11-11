<?php

Route::prefix('v1/chat/')->middleware('cors')->group(function () {
    Route::get('test2', 'ChatController@testOneSignal');
    Route::get('history', 'ChatController@history');
    Route::get('users', 'ChatController@users');
    Route::post('send', 'ChatController@sendMessage');
    Route::post('test', 'ChatController@sendMessageTest');
    Route::post('attach/file', 'ChatController@upload');
    Route::post('room/accept', 'ChatController@roomSupportAccept');
    Route::post('room/close', 'ChatController@roomSupportClose');
    Route::post('room/private', 'ChatController@roomPrivate');
    Route::post('room/group', 'ChatController@roomGroup');
    Route::post('room/group/user/add', 'ChatController@roomGroupAddUser');
    Route::post('room/group/user/delete', 'ChatController@roomGroupDeleteUser');
    Route::post('room/group/delete', 'ChatController@roomGroupDelete');
    Route::post('room/group/leave', 'ChatController@roomGroupLeave');
});
