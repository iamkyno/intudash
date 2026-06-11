<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

// Webhook — no auth
Route::post('webhooks/smsportal', [WebhookController::class, 'smsportal'])
    ->name('webhooks.smsportal');

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clients
    Route::resource('clients', ClientController::class);

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::get('campaigns/{campaign}/report', [CampaignController::class, 'report'])->name('campaigns.report');
    Route::get('campaigns/{campaign}/export-report', [CampaignController::class, 'exportReport'])->name('campaigns.export-report');

    // Recipients
    Route::get('campaigns/{campaign}/recipients', [RecipientController::class, 'index'])->name('campaigns.recipients');
    Route::post('campaigns/{campaign}/recipients', [RecipientController::class, 'store'])->name('campaigns.recipients.store');
    Route::post('campaigns/{campaign}/recipients/upload', [RecipientController::class, 'upload'])->name('campaigns.recipients.upload');
    Route::get('campaigns/{campaign}/recipients/export-invalid', [RecipientController::class, 'exportInvalid'])->name('campaigns.recipients.export-invalid');
    Route::get('campaigns/{campaign}/recipients/export-valid', [RecipientController::class, 'exportValid'])->name('campaigns.recipients.export-valid');
    Route::delete('campaigns/{campaign}/recipients/{recipient}', [RecipientController::class, 'destroy'])->name('campaigns.recipients.destroy');

    // Invoices
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('campaigns/{campaign}/invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
