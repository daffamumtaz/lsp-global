<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfilePageController;
use Illuminate\Support\Facades\Route;

Route::get('/landing-page', [LandingPageController::class, 'show'])->name('api.landing-page.show');
Route::get('/profile-page', [ProfilePageController::class, 'show'])->name('api.profile-page.show');
