<?php

Route::middleware('auth:api')->group(function () {
    // Route::post('pos/{id}/calculate', 'POSController@calculate')->name('Core\POS::calculate');
    // Route::post('pos/{id}/paid', 'POSStatusController@paid')->name('Core\POS::status.paid');
    // Route::post('pos/{id}/cancel', 'POSStatusController@cancel')->name('Core\POS::status.cancel');
    // Route::post('pos/{id}/complete', 'POSStatusController@complete')->name('Core\POS::status.complete');
    // Route::post('pos/{id}/details', 'POSDetailController@store')->name('Core\POS::detail.store');

    Route::post('pos/{id}/eticket/generate', 'POSETicketController@generate')->name('Core\POS::eticket.generate');
    Route::post('pos/{id}/eticket/send', 'POSETicketController@send')->name('Core\POS::eticket.send');
    Route::post('pos/{id}/eticket/upload', 'POSETicketController@upload')->name('Core\POS::eticket.upload');
    Route::post('pos/{id}/eticket/applystock', 'POSETicketController@applyFromStock')->name('Core\POS::eticket.stock.apply');

    Route::post('eticketupload/{id}', 'POSETicketController@itemUpload')->name('Core\POS::etickets.upload.item');
    
    // Route::post('invitation/{id}/process', 'InvitationController@process')->name('Core\POS::invitation.process');
    // Route::post('invitation/{id}/verify', 'InvitationController@verify')->name('Core\POS::invitation.verify');

});
