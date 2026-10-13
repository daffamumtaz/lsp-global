<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/media/profile-video', function (): BinaryFileResponse {
    return response()->file(resource_path('media/video-profile-lfRo5LN6.webm'), [
        'Content-Type' => 'video/webm',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('media.profile-video');

Route::get('/', function () {
    return view('welcome');
});
