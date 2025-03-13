<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

Route::post('/upload-roster', [EventController::class, 'uploadRoster']);
Route::get('/events-between', [EventController::class, 'getEventsBetweenDates']);
Route::get('/flights-next-week', [EventController::class, 'getFlightsNextWeek']);
Route::get('/standby-next-week', [EventController::class, 'getStandbyNextWeek']);
Route::get('/flights/from/{location}', [EventController::class, 'getFlightsFromLocation']);

