<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SendingDomain;
use App\Services\SesDomainService;
use Illuminate\Http\Request;

class SendingDomainController extends Controller
{
    public function indexGlobal()
    {
        $domains = SendingDomain::whereNull('client_id')->latest()->get();
        return view('sending_domains.index', ['client' => null, 'domains' => $domains]);
    }

    public function storeGlobal(Request $request, SesDomainService $svc)
    {
        return $this->store($request, null, $svc);
    }

    public function indexForClient(Client $client)
    {
        $domains = $client->sendingDomains()->latest()->get();
        return view('sending_domains.index', ['client' => $client, 'domains' => $domains]);
    }

    public function storeForClient(Request $request, Client $client, SesDomainService $svc)
    {
        return $this->store($request, $client, $svc);
    }

    private function store(Request $request, ?Client $client, SesDomainService $svc)
    {
        $data = $request->validate([
            'domain' => 'required|string|max:191|regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i',
            'label'  => 'nullable|string|max:191',
        ]);

        $domain = SendingDomain::create([
            'client_id' => $client?->id,
            'domain'    => strtolower($data['domain']),
            'label'     => $data['label'] ?? null,
        ]);

        try {
            $svc->beginVerification($domain);
            $message = 'Domain added — add the DNS records below, then click "Recheck Status" once they propagate.';
        } catch (\Throwable $e) {
            $message = 'Domain saved, but the email gateway rejected the verification request: ' . $e->getMessage();
        }

        $redirect = $client
            ? route('clients.sending-domains.index', $client)
            : route('settings.sending-domains.index');

        return redirect($redirect)->with('success', $message);
    }

    public function check(SendingDomain $domain, SesDomainService $svc)
    {
        try {
            $svc->refreshStatus($domain);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not check status: ' . $e->getMessage());
        }

        return back()->with('success', 'Status refreshed.');
    }

    public function destroy(SendingDomain $domain, SesDomainService $svc)
    {
        $svc->deregister($domain);
        $redirect = $domain->client_id
            ? route('clients.sending-domains.index', $domain->client_id)
            : route('settings.sending-domains.index');
        $domain->delete();

        return redirect($redirect)->with('success', 'Sending domain removed.');
    }
}
