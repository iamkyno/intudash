<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientRecipient;
use App\Models\RecipientGroup;
use App\Services\RecipientValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ClientRecipientController extends Controller
{
    public function index(Request $request, Client $client)
    {
        $query = $client->recipients()->with('groups');

        if ($request->filled('group_id')) {
            $query->whereHas('groups', fn ($q) => $q->where('recipient_groups.id', $request->group_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $recipients = $query->latest()->paginate(50)->withQueryString();
        $groups = $client->recipientGroups()->withCount('recipients')->orderBy('name')->get();

        $stats = [
            'total'        => $client->recipients()->count(),
            'active'       => $client->recipients()->active()->count(),
            'invalid'      => $client->recipients()->where('status', 'invalid')->count(),
            'unsubscribed' => $client->recipients()->where('status', 'unsubscribed')->count(),
        ];

        return view('clients.recipients.index', compact('client', 'recipients', 'groups', 'stats'));
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'     => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'group_id' => 'nullable|exists:recipient_groups,id',
        ]);

        if (empty($data['phone']) && empty($data['email'])) {
            return back()->with('error', 'Provide at least a phone number or email address.');
        }

        $row = RecipientValidationService::validateRow($data['name'] ?? null, $data['phone'] ?? '', $data['email'] ?? '');

        $existing = null;
        if ($row['phone_normalized'] || $row['email']) {
            $existing = $client->recipients()
                ->where(function ($q) use ($row) {
                    if ($row['phone_normalized']) {
                        $q->orWhere('phone_normalized', $row['phone_normalized']);
                    }
                    if ($row['email']) {
                        $q->orWhere('email', $row['email']);
                    }
                })
                ->first();
        }

        if ($existing) {
            $existing->update([
                'name'             => $row['name'] ?: $existing->name,
                'phone'            => $row['phone'] ?: $existing->phone,
                'phone_normalized' => $row['phone_normalized'] ?: $existing->phone_normalized,
                'email'            => $row['email'] ?: $existing->email,
                'status'           => $row['usable'] ? 'active' : $existing->status,
            ]);
            $recipient = $existing;
            $message = 'Matching recipient already existed — details updated.';
        } else {
            $recipient = ClientRecipient::create([
                'client_id'        => $client->id,
                'name'             => $row['name'],
                'phone'            => $row['phone'],
                'phone_normalized' => $row['phone_normalized'],
                'email'            => $row['email'],
                'status'           => $row['usable'] ? 'active' : 'invalid',
                'invalid_reason'   => $row['invalid_reason'],
            ]);
            $message = 'Recipient added.';
        }

        if (!empty($data['group_id'])) {
            $group = RecipientGroup::where('client_id', $client->id)->findOrFail($data['group_id']);
            $recipient->groups()->syncWithoutDetaching([$group->id]);
        }

        return back()->with('success', $message);
    }

    public function upload(Request $request, Client $client)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'group_id' => 'nullable|exists:recipient_groups,id',
        ]);

        $group = $request->filled('group_id')
            ? RecipientGroup::where('client_id', $client->id)->findOrFail($request->group_id)
            : null;

        $file = $request->file('csv_file');
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $existingPhones = $client->recipients()->whereNotNull('phone_normalized')->pluck('phone_normalized')->flip();
        $existingEmails = $client->recipients()->whereNotNull('email')->pluck('email')->map(fn ($e) => strtolower($e))->flip();

        $stats = ['total' => 0, 'added' => 0, 'duplicate' => 0, 'invalid' => 0];
        $seen = [];
        $rows = [];
        $now = now();

        foreach ($csv->getRecords() as $record) {
            $stats['total']++;

            $fields = RecipientValidationService::extractCsvFields($record);
            $row = RecipientValidationService::validateRow($fields['name'], $fields['phone'], $fields['email']);

            if (!$row['usable']) {
                $stats['invalid']++;
                continue;
            }

            $key = $row['dedupe_key'];
            $alreadyExists = ($row['phone_normalized'] && $existingPhones->has($row['phone_normalized']))
                || ($row['email'] && $existingEmails->has($row['email']))
                || in_array($key, $seen, true);

            if ($alreadyExists) {
                $stats['duplicate']++;
                continue;
            }

            if ($key) {
                $seen[] = $key;
            }
            $stats['added']++;
            $rows[] = [
                'client_id'        => $client->id,
                'name'             => $row['name'],
                'phone'            => $row['phone'],
                'phone_normalized' => $row['phone_normalized'],
                'email'            => $row['email'],
                'status'           => 'active',
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        DB::transaction(function () use ($rows, $client, $group) {
            foreach (array_chunk($rows, 500) as $chunk) {
                ClientRecipient::insert($chunk);
            }

            if ($group && $rows) {
                // Re-select the rows we just inserted (by their dedupe keys) to get IDs for the pivot.
                $phones = array_values(array_filter(array_column($rows, 'phone_normalized')));
                $emails = array_values(array_filter(array_column($rows, 'email')));

                $newIds = ClientRecipient::where('client_id', $client->id)
                    ->where(function ($q) use ($phones, $emails) {
                        if ($phones) {
                            $q->orWhereIn('phone_normalized', $phones);
                        }
                        if ($emails) {
                            $q->orWhereIn('email', $emails);
                        }
                    })
                    ->pluck('id');

                $pivotRows = $newIds->map(fn ($id) => [
                    'recipient_group_id'  => $group->id,
                    'client_recipient_id' => $id,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ])->all();

                foreach (array_chunk($pivotRows, 500) as $chunk) {
                    DB::table('recipient_group_member')->insertOrIgnore($chunk);
                }
            }
        });

        return redirect()->route('clients.recipients.index', $client)
            ->with('success', "Upload complete: {$stats['added']} added, {$stats['duplicate']} duplicates skipped, {$stats['invalid']} invalid.");
    }

    public function edit(Client $client, ClientRecipient $recipient)
    {
        abort_unless($recipient->client_id === $client->id, 404);
        $groups = $client->recipientGroups()->orderBy('name')->get();
        return view('clients.recipients.edit', compact('client', 'recipient', 'groups'));
    }

    public function update(Request $request, Client $client, ClientRecipient $recipient)
    {
        abort_unless($recipient->client_id === $client->id, 404);

        $data = $request->validate([
            'name'        => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'status'      => 'required|in:active,invalid,unsubscribed',
            'group_ids'   => 'nullable|array',
            'group_ids.*' => 'exists:recipient_groups,id',
        ]);

        $row = RecipientValidationService::validateRow($data['name'] ?? null, $data['phone'] ?? '', $data['email'] ?? '');

        $recipient->update([
            'name'             => $row['name'],
            'phone'            => $row['phone'],
            'phone_normalized' => $row['phone_normalized'],
            'email'            => $row['email'],
            'status'           => $data['status'],
            'invalid_reason'   => $data['status'] === 'invalid' ? ($row['invalid_reason'] ?? 'Marked invalid manually') : null,
        ]);

        // Only sync groups that actually belong to this client (defence-in-depth).
        $validGroupIds = $client->recipientGroups()->whereIn('id', $data['group_ids'] ?? [])->pluck('id');
        $recipient->groups()->sync($validGroupIds);

        return redirect()->route('clients.recipients.index', $client)->with('success', 'Recipient updated.');
    }

    public function destroy(Client $client, ClientRecipient $recipient)
    {
        abort_unless($recipient->client_id === $client->id, 404);
        $recipient->delete();
        return back()->with('success', 'Recipient removed.');
    }

    /**
     * Bulk add or remove a set of selected recipients from a group.
     */
    public function bulkAssign(Request $request, Client $client)
    {
        $data = $request->validate([
            'recipient_ids'   => 'required|array|min:1',
            'recipient_ids.*' => 'exists:client_recipients,id',
            'group_id'        => 'required|exists:recipient_groups,id',
            'action'          => 'required|in:add,remove',
        ]);

        $group = RecipientGroup::where('client_id', $client->id)->findOrFail($data['group_id']);
        $recipientIds = $client->recipients()->whereIn('id', $data['recipient_ids'])->pluck('id');

        if ($data['action'] === 'add') {
            $group->recipients()->syncWithoutDetaching($recipientIds);
            $message = "Added {$recipientIds->count()} recipient(s) to \"{$group->name}\".";
        } else {
            $group->recipients()->detach($recipientIds);
            $message = "Removed {$recipientIds->count()} recipient(s) from \"{$group->name}\".";
        }

        return back()->with('success', $message);
    }
}
