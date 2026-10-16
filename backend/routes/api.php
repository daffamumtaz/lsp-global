<?php

use App\Http\Controllers\ContentDirectoryController;
use App\Http\Controllers\InformationPageController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProfilePageController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:public-content')->group(function (): void {
    Route::get('/landing-page', [LandingPageController::class, 'show'])->name('api.landing-page.show');
    Route::get('/profile-page', [ProfilePageController::class, 'show'])->name('api.profile-page.show');
    Route::get('/information-page', [InformationPageController::class, 'show'])->name('api.information-page.show');

    Route::get('/organization', [OrganizationController::class, 'show'])->name('api.organization.show');

    Route::get('/content-directory', [ContentDirectoryController::class, 'show'])->name('api.content-directory.show');

    Route::get('/processes', [ProcessController::class, 'index'])->name('api.processes.index');
    Route::get('/processes/{slug}/preview', [ProcessController::class, 'preview'])->middleware('signed:relative')->name('api.processes.preview');
    Route::get('/processes/{slug}', [ProcessController::class, 'show'])->name('api.processes.show');
});
