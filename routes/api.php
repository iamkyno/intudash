<?php

use App\Http\Controllers\Api\ReminderApiController;
use Illuminate\Support\Facades\Route;

// Webhook endpoints are defined in routes/web.php (secret-protected + throttled).

// Per-client API authenticated by token (X-Api-Key header or Bearer token).
Route::middleware(['api.client', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('reminders', [ReminderApiController::class, 'store'])->name('api.reminders.store');
});
