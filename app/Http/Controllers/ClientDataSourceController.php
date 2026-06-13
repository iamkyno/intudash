<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientDataSource;
use App\Services\DataSourceService;
use Illuminate\Http\Request;

class ClientDataSourceController extends Controller
{
    public function index(Client $client)
    {
        return view('data_sources.index', [
            'client'  => $client,
            'sources' => $client->dataSources()->latest()->get(),
        ]);
    }

    public function create(Client $client)
    {
        return view('data_sources.create', ['client' => $client]);
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'driver'        => 'required|in:mysql,pgsql,sqlsrv',
            'host'          => 'required|string|max:255',
            'port'          => 'required|integer|min:1|max:65535',
            'database'      => 'required|string|max:191',
            'username'      => 'required|string|max:191',
            'password'      => 'nullable|string|max:500',
            'table_or_view' => 'nullable|string|max:191',
            'custom_query'  => 'nullable|string',
            'col_name'      => 'nullable|string|max:191',
            'col_phone'     => 'nullable|string|max:191',
            'col_email'     => 'nullable|string|max:191',
        ]);

        $source = new ClientDataSource(\Illuminate\Support\Arr::except($data, ['password']));
        $source->client_id = $client->id;
        if (!empty($data['password'])) {
            $source->password = $data['password'];
        }
        $source->save();

        return redirect()->route('clients.data-sources.index', $client)
            ->with('success', 'Data source saved.');
    }

    public function edit(Client $client, ClientDataSource $dataSource)
    {
        abort_unless($dataSource->client_id === $client->id, 403);
        return view('data_sources.edit', ['client' => $client, 'source' => $dataSource]);
    }

    public function update(Request $request, Client $client, ClientDataSource $dataSource)
    {
        abort_unless($dataSource->client_id === $client->id, 403);

        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'driver'        => 'required|in:mysql,pgsql,sqlsrv',
            'host'          => 'required|string|max:255',
            'port'          => 'required|integer|min:1|max:65535',
            'database'      => 'required|string|max:191',
            'username'      => 'required|string|max:191',
            'password'      => 'nullable|string|max:500',
            'table_or_view' => 'nullable|string|max:191',
            'custom_query'  => 'nullable|string',
            'col_name'      => 'nullable|string|max:191',
            'col_phone'     => 'nullable|string|max:191',
            'col_email'     => 'nullable|string|max:191',
        ]);

        $dataSource->fill(\Illuminate\Support\Arr::except($data, ['password']));
        if (!empty($data['password'])) {
            $dataSource->password = $data['password'];
        }
        $dataSource->save();

        return redirect()->route('clients.data-sources.index', $client)
            ->with('success', 'Data source updated.');
    }

    public function destroy(Client $client, ClientDataSource $dataSource)
    {
        abort_unless($dataSource->client_id === $client->id, 403);
        $dataSource->delete();
        return back()->with('success', 'Data source deleted.');
    }

    public function preview(Request $request, Client $client, ClientDataSource $dataSource, DataSourceService $svc)
    {
        abort_unless($dataSource->client_id === $client->id, 403);
        try {
            $result = $svc->preview($dataSource);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function import(Request $request, Client $client, ClientDataSource $dataSource, DataSourceService $svc)
    {
        abort_unless($dataSource->client_id === $client->id, 403);

        $request->validate(['campaign_id' => 'required|integer|exists:campaigns,id']);
        $campaign = Campaign::where('id', $request->campaign_id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        try {
            $result = $svc->importToCampaign($dataSource, $campaign);
            return back()->with('success', "Imported {$result['imported']} recipients ({$result['skipped']} skipped).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
