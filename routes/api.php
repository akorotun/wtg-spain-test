<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
Route::get('/imports/{importId}', [ImportController::class, 'show'])->name('imports.show');

Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');

Route::post('/offers/{offerId}/reservations', [ReservationController::class, 'store'])->name('reservation.store');
