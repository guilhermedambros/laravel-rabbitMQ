<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RabbitController;


Route::post('/send-message', [RabbitController::class, 'send']);
Route::get('/receive-message', [RabbitController::class, 'receive']);