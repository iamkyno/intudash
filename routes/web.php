<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

// Webhooks — no session auth; protected by shared secret + rate limiting.
Route::middleware('throttle:webhooks')->group(function () {
    Route::post('webhooks/smsportal', [WebhookController::class, 'smsportal'])
        ->name('webhooks.smsportal');
    Route::post('webhooks/ses', [WebhookController::class, 'ses'])
        ->name('webhooks.ses');
});

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
    Route::get('campaigns/archived', [CampaignController::class, 'archived'])->name('campaigns.archived');
    Route::post('campaigns/{campaign}/archive', [CampaignController::class, 'archive'])->name('campaigns.archive');
    Route::post('campaigns/{campaign}/restore-archive', [CampaignController::class, 'restoreArchive'])->name('campaigns.restore-archive');
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
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

    // Quotes
    Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
    Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
    Route::post('/campaigns/{campaign}/generate-quote', [QuoteController::class, 'generate'])->name('quotes.generate');
    Route::post('/quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept');
    Route::post('/quotes/{quote}/decline', [QuoteController::class, 'decline'])->name('quotes.decline');
    Route::patch('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.update-status');
    Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
