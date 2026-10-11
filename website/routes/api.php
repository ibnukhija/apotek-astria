<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Panggil fungsi loginMobile yang ada di AuthController
Route::post('/login-mobile', [AuthController::class, 'loginMobile']);