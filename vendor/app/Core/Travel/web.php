<?php

Route::middleware('auth')->group(function() {
    Route::get('traveltransaction/{id}/eticket', 'TravelETicketController@show');
    Route::get('traveltransaction/{id}/itinerary', 'TravelItineraryController@show');
});

