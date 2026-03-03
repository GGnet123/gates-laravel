<?php

use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::post('/withdrawals', [WithdrawalController::class, 'store']);