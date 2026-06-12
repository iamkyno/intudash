<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount(['campaigns', 'invoices'])
            ->latest()
            ->paginate(20);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string|max:20',
            'billing_address' => 'nullable|string',
            'vat_number' => 'nullable|string|max:50',
            'default_sms_rate' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $client = Client::create($validated);
        AuditLogService::log('client_created', $client, null, $validated);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load([
            'campaigns' => fn($q) => $q->latest()->limit(10),
            'invoices' => fn($q) => $q->latest()->limit(10),
        ]);

        $stats = [
            'total_campaigns' => $client->campaigns()->count(),
            'total_sms_sent' => $client->smsLogs()->whereIn('status', ['delivered', 'submitted'])->count(),
            'total_billed' => $client->invoices()->where('status', 'paid')->sum('total'),
            'total_profit' => $client->campaigns()->sum('actual_profit'),
        ];

        $activeCampaignCount = $client->campaigns()
            ->whereNotIn('status', ['cancelled', 'completed', 'failed'])
            ->count();

        return view('clients.show', compact('client', 'stats', 'activeCampaignCount'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'phone' => 'required|string|max:20',
            'billing_address' => 'nullable|string',
            'vat_number' => 'nullable|string|max:50',
            'default_sms_rate' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $old = $client->toArray();
        $client->update($validated);
        AuditLogService::log('client_updated', $client, $old, $validated);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Request $request, Client $client)
    {
        $force = $request->boolean('force');
        $activeCampaigns = $client->campaigns()
            ->whereNotIn('status', ['cancelled', 'completed', 'failed'])
            ->count();

        if ($activeCampaigns > 0 && !$force) {
            return back()->with('error', "Cannot delete: this client has {$activeCampaigns} active campaign(s). Archive or cancel them first, or force delete.");
        }

        AuditLogService::log('client_deleted', $client);
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted.');
    }
}
