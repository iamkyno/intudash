<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\RecipientGroup;
use App\Services\CampaignRecipientImportService;
use App\Services\PhoneNormalizer;
use App\Services\RecipientValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class RecipientController extends Controller
{
    public function index(Campaign $campaign)
    {
        $recipients = $campaign->recipients()->paginate(50);
        $stats = [
            'total' => $campaign->recipients()->count(),
            'valid' => $campaign->recipients()->where('status', 'valid')->count(),
            'duplicate' => $campaign->recipients()->where('status', 'duplicate')->count(),
            'invalid' => $campaign->recipients()->where('status', 'invalid')->count(),
        ];

        $dataSources = $campaign->client
            ? $campaign->client->dataSources()->where('active', true)->get()
            : collect();

        $recipientGroups = $campaign->client
            ? $campaign->client->recipientGroups()->withCount('recipients')->orderBy('name')->get()
            : collect();

        return view('campaigns.recipients', compact('campaign', 'recipients', 'stats', 'dataSources', 'recipientGroups'));
    }

    public function store(Request $request, Campaign $campaign)
    {
        $type = $campaign->campaign_type ?? 'sms';
        $needsPhone = in_array($type, ['sms', 'both']);
        $needsEmail = $type === 'email';

        $request->validate([
            'name'  => 'nullable|string|max:255',
            'phone' => ($needsPhone ? 'required' : 'nullable') . '|string|max:20',
            'email' => ($needsEmail ? 'required' : 'nullable') . '|email|max:255',
        ]);

        $normalized = '';
        $phone = $request->phone ?? '';

        if (!empty($phone)) {
            $result = PhoneNormalizer::validateAndNormalize($phone);
            if (!$result['valid'] && $needsPhone) {
                return back()->with('error', 'Invalid phone number: ' . $result['reason']);
            }
            $normalized = $result['valid'] ? $result['normalized'] : '';
        }

        $existing = $normalized && $campaign->recipients()
            ->where('phone_normalized', $normalized)
            ->exists();

        CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'name' => $request->name,
            'phone' => $phone,
            'phone_normalized' => $normalized,
            'email' => $request->email,
            'status' => $existing ? 'duplicate' : 'valid',
            'invalid_reason' => null,
        ]);

        if ($campaign->status === 'draft') {
            $campaign->update(['status' => 'recipients_uploaded']);
        }

        $campaign->recalculateEstimates();

        return back()->with('success', 'Recipient added.');
    }

    public function upload(Request $request, Campaign $campaign)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $type = $campaign->campaign_type ?? 'sms';

        $file = $request->file('csv_file');
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $stats = ['total' => 0, 'valid' => 0, 'duplicate' => 0, 'invalid' => 0];
        $existingNumbers = $campaign->recipients()
            ->where('status', 'valid')
            ->pluck('phone_normalized')
            ->filter()
            ->toArray();

        $seenNumbers = [];
        $rows = [];
        $now = now();

        foreach ($csv->getRecords() as $record) {
            $stats['total']++;

            $fields = RecipientValidationService::extractCsvFields($record);
            $row = RecipientValidationService::validateRow($fields['name'], $fields['phone'], $fields['email'], $type);

            $base = [
                'campaign_id'      => $campaign->id,
                'name'             => $row['name'],
                'phone'            => $row['phone'] ?? '',
                'phone_normalized' => $row['phone_normalized'] ?: ($row['phone'] ?? ''),
                'email'            => $row['email'],
                'invalid_reason'   => $row['invalid_reason'],
                'created_at'       => $now,
                'updated_at'       => $now,
            ];

            if (!$row['usable']) {
                $stats['invalid']++;
                $base['status'] = 'invalid';
                $rows[] = $base;
                continue;
            }

            // Duplicate detection (phone-based when a phone exists, else email-based).
            $dupeKey = $row['dedupe_key'];
            if ($dupeKey && (in_array($dupeKey, $existingNumbers) || in_array($dupeKey, $seenNumbers))) {
                $stats['duplicate']++;
                $base['status'] = 'duplicate';
                $rows[] = $base;
                continue;
            }

            if ($dupeKey) {
                $seenNumbers[] = $dupeKey;
            }
            $stats['valid']++;
            $base['status'] = 'valid';
            $rows[] = $base;
        }

        // Batched, atomic insert.
        DB::transaction(function () use ($rows, $campaign, $stats) {
            foreach (array_chunk($rows, 500) as $chunk) {
                CampaignRecipient::insert($chunk);
            }

            if ($stats['valid'] > 0 && $campaign->status === 'draft') {
                $campaign->update(['status' => 'recipients_uploaded']);
            }
        });

        $campaign->recalculateEstimates();

        return redirect()->route('campaigns.recipients', $campaign)
            ->with('success', "Upload complete: {$stats['valid']} valid, {$stats['duplicate']} duplicates, {$stats['invalid']} invalid.");
    }

    /**
     * Populate this campaign's recipient list from one or more of the client's
     * saved Recipient Groups — snapshotted through the same validation/dedupe
     * logic as a CSV upload, so downstream sending is unaffected.
     */
    public function importFromGroups(Request $request, Campaign $campaign, CampaignRecipientImportService $importer)
    {
        abort_unless($campaign->client_id, 404);

        $request->validate([
            'group_ids'   => 'required|array|min:1',
            'group_ids.*' => 'exists:recipient_groups,id',
        ]);

        $groups = RecipientGroup::where('client_id', $campaign->client_id)
            ->whereIn('id', $request->group_ids)
            ->with(['recipients' => fn ($q) => $q->active()])
            ->get();

        $contacts = $groups->flatMap->recipients->unique('id');
        $stats = $importer->importContacts($campaign, $contacts);

        return redirect()->route('campaigns.recipients', $campaign)
            ->with('success', "Imported from group(s): {$stats['valid']} valid, {$stats['duplicate']} duplicates, {$stats['invalid']} invalid.");
    }

    public function exportInvalid(Campaign $campaign)
    {
        $records = $campaign->recipients()
            ->whereIn('status', ['invalid', 'duplicate'])
            ->get();

        $filename = "campaign_{$campaign->id}_invalid_recipients.csv";

        $callback = function () use ($records) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Name', 'Phone', 'Email', 'Status', 'Reason']);
            foreach ($records as $r) {
                fputcsv($h, [$r->name, $r->phone, $r->email ?? '', $r->status, $r->invalid_reason ?? '']);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportValid(Campaign $campaign)
    {
        $records = $campaign->recipients()->where('status', 'valid')->get();

        $filename = "campaign_{$campaign->id}_clean_recipients.csv";

        $callback = function () use ($records) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Name', 'Phone', 'Normalized Phone', 'Email']);
            foreach ($records as $r) {
                fputcsv($h, [$r->name, $r->phone, $r->phone_normalized, $r->email ?? '']);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(Campaign $campaign, CampaignRecipient $recipient)
    {
        // Ensure the recipient actually belongs to this campaign (defence-in-depth).
        abort_if($recipient->campaign_id !== $campaign->id, 404);

        $recipient->delete();
        $campaign->recalculateEstimates();
        return back()->with('success', 'Recipient removed.');
    }
}
