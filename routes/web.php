<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf' => true]);
});

Route::get('/health', \App\Http\Controllers\HealthCheckController::class);

Route::get('/', function () {
    return view('app');
});

Route::fallback(function () {
    return view('app');
});

