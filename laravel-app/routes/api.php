<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WsMessageController;

Route::post('/ws/messages', [WsMessageController::class, 'store']);
