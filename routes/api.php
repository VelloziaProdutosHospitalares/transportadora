<?php

use App\Http\Controllers\OctalogWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('octalog/webhook', OctalogWebhookController::class)
    ->middleware('octalog.webhook.secret');
