<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDataSourceController;
use App\Http\Controllers\ClientRecipientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\RecipientGroupController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReminderTemplateController;
use App\Http\Controllers\SendingDomainController;
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
    Route::post('campaigns/{campaign}/recipients/import-from-groups', [RecipientController::class, 'importFromGroups'])->name('campaigns.recipients.import-from-groups');

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

    // Reminder templates
    Route::resource('reminder-templates', ReminderTemplateController::class)->except(['show']);

    // Reminders
    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::get('reminders/create', [ReminderController::class, 'create'])->name('reminders.create');
    Route::post('reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::post('reminders/upload', [ReminderController::class, 'upload'])->name('reminders.upload');
    Route::post('reminders/{reminder}/cancel', [ReminderController::class, 'cancel'])->name('reminders.cancel');
    Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

    // Client API token
    Route::post('clients/{client}/api-token', [ClientController::class, 'regenerateApiToken'])->name('clients.api-token');

    // Client data sources (external DB connections)
    Route::get('clients/{client}/data-sources', [ClientDataSourceController::class, 'index'])->name('clients.data-sources.index');
    Route::get('clients/{client}/data-sources/create', [ClientDataSourceController::class, 'create'])->name('clients.data-sources.create');
    Route::post('clients/{client}/data-sources', [ClientDataSourceController::class, 'store'])->name('clients.data-sources.store');
    Route::get('clients/{client}/data-sources/{dataSource}/edit', [ClientDataSourceController::class, 'edit'])->name('clients.data-sources.edit');
    Route::put('clients/{client}/data-sources/{dataSource}', [ClientDataSourceController::class, 'update'])->name('clients.data-sources.update');
    Route::delete('clients/{client}/data-sources/{dataSource}', [ClientDataSourceController::class, 'destroy'])->name('clients.data-sources.destroy');
    Route::post('clients/{client}/data-sources/{dataSource}/preview', [ClientDataSourceController::class, 'preview'])->name('clients.data-sources.preview');
    Route::post('clients/{client}/data-sources/{dataSource}/import', [ClientDataSourceController::class, 'import'])->name('clients.data-sources.import');

    // Sending domains (SES) — agency-wide default + per-client "friendly" marketing domains
    Route::get('settings/sending-domains', [SendingDomainController::class, 'indexGlobal'])->name('settings.sending-domains.index');
    Route::post('settings/sending-domains', [SendingDomainController::class, 'storeGlobal'])->name('settings.sending-domains.store');
    Route::get('clients/{client}/sending-domains', [SendingDomainController::class, 'indexForClient'])->name('clients.sending-domains.index');
    Route::post('clients/{client}/sending-domains', [SendingDomainController::class, 'storeForClient'])->name('clients.sending-domains.store');
    Route::post('sending-domains/{domain}/check', [SendingDomainController::class, 'check'])->name('sending-domains.check');
    Route::delete('sending-domains/{domain}', [SendingDomainController::class, 'destroy'])->name('sending-domains.destroy');

    // Client audience — persistent, groupable recipient lists (independent of any one campaign)
    Route::get('clients/{client}/recipients', [ClientRecipientController::class, 'index'])->name('clients.recipients.index');
    Route::post('clients/{client}/recipients', [ClientRecipientController::class, 'store'])->name('clients.recipients.store');
    Route::post('clients/{client}/recipients/upload', [ClientRecipientController::class, 'upload'])->name('clients.recipients.upload');
    Route::post('clients/{client}/recipients/bulk-assign', [ClientRecipientController::class, 'bulkAssign'])->name('clients.recipients.bulk-assign');
    Route::get('clients/{client}/recipients/{recipient}/edit', [ClientRecipientController::class, 'edit'])->name('clients.recipients.edit');
    Route::put('clients/{client}/recipients/{recipient}', [ClientRecipientController::class, 'update'])->name('clients.recipients.update');
    Route::delete('clients/{client}/recipients/{recipient}', [ClientRecipientController::class, 'destroy'])->name('clients.recipients.destroy');

    Route::post('clients/{client}/recipient-groups', [RecipientGroupController::class, 'store'])->name('clients.recipient-groups.store');
    Route::put('clients/{client}/recipient-groups/{group}', [RecipientGroupController::class, 'update'])->name('clients.recipient-groups.update');
    Route::delete('clients/{client}/recipient-groups/{group}', [RecipientGroupController::class, 'destroy'])->name('clients.recipient-groups.destroy');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
