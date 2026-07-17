<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\RecipientGroup;
use Illuminate\Http\Request;

class RecipientGroupController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $exists = $client->recipientGroups()->where('name', $data['name'])->exists();
        if ($exists) {
            return back()->with('error', "A group named \"{$data['name']}\" already exists for this client.");
        }

        $client->recipientGroups()->create($data);

        return back()->with('success', "Group \"{$data['name']}\" created.");
    }

    public function update(Request $request, Client $client, RecipientGroup $group)
    {
        abort_unless($group->client_id === $client->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $duplicate = $client->recipientGroups()->where('name', $data['name'])->where('id', '!=', $group->id)->exists();
        if ($duplicate) {
            return back()->with('error', "A group named \"{$data['name']}\" already exists for this client.");
        }

        $group->update($data);

        return back()->with('success', 'Group updated.');
    }

    public function destroy(Client $client, RecipientGroup $group)
    {
        abort_unless($group->client_id === $client->id, 404);
        $group->delete();
        return back()->with('success', "Group \"{$group->name}\" deleted — its recipients were not removed.");
    }
}
