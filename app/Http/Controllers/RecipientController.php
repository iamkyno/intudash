<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\PhoneNormalizer;
use Illuminate\Http\Request;
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

        return view('campaigns.recipients', compact('campaign', 'recipients', 'stats'));
    }

    public function store(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $result = PhoneNormalizer::validateAndNormalize($request->phone);

        if (!$result['valid']) {
            return back()->with('error', 'Invalid phone number: ' . $result['reason']);
        }

        $existing = $campaign->recipients()
            ->where('phone_normalized', $result['normalized'])
            ->exists();

        CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'phone_normalized' => $result['normalized'],
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

        $file = $request->file('csv_file');
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $stats = ['total' => 0, 'valid' => 0, 'duplicate' => 0, 'invalid' => 0];
        $existingNumbers = $campaign->recipients()
            ->where('status', 'valid')
            ->pluck('phone_normalized')
            ->toArray();

        $newNumbers = [];

        foreach ($csv->getRecords() as $record) {
            $stats['total']++;

            $phone = trim($record['phone'] ?? $record['Phone'] ?? $record['mobile'] ?? '');

            if (empty($phone)) {
                $stats['invalid']++;
                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'name' => $record['name'] ?? $record['Name'] ?? null,
                    'phone' => '',
                    'phone_normalized' => '',
                    'email' => $record['email'] ?? null,
                    'status' => 'invalid',
                    'invalid_reason' => 'Missing phone number',
                ]);
                continue;
            }

            $result = PhoneNormalizer::validateAndNormalize($phone);

            if (!$result['valid']) {
                $stats['invalid']++;
                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'name' => $record['name'] ?? $record['Name'] ?? null,
                    'phone' => $phone,
                    'phone_normalized' => $phone,
                    'email' => $record['email'] ?? null,
                    'status' => 'invalid',
                    'invalid_reason' => $result['reason'],
                ]);
                continue;
            }

            $normalized = $result['normalized'];

            if (in_array($normalized, $existingNumbers) || in_array($normalized, $newNumbers)) {
                $stats['duplicate']++;
                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'name' => $record['name'] ?? $record['Name'] ?? null,
                    'phone' => $phone,
                    'phone_normalized' => $normalized,
                    'email' => $record['email'] ?? null,
                    'status' => 'duplicate',
                ]);
                continue;
            }

            $newNumbers[] = $normalized;
            $stats['valid']++;

            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'name' => $record['name'] ?? $record['Name'] ?? null,
                'phone' => $phone,
                'phone_normalized' => $normalized,
                'email' => $record['email'] ?? null,
                'status' => 'valid',
            ]);
        }

        if ($stats['valid'] > 0 && in_array($campaign->status, ['draft'])) {
            $campaign->update(['status' => 'recipients_uploaded']);
        }

        $campaign->recalculateEstimates();

        return redirect()->route('campaigns.recipients', $campaign)
            ->with('success', "Upload complete: {$stats['valid']} valid, {$stats['duplicate']} duplicates, {$stats['invalid']} invalid.");
    }

    public function exportInvalid(Campaign $campaign)
    {
        $records = $campaign->recipients()
            ->whereIn('status', ['invalid', 'duplicate'])
            ->get();

        $filename = "campaign_{$campaign->id}_invalid_recipients.csv";

        $callback = function () use ($records) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Name', 'Phone', 'Status', 'Reason']);
            foreach ($records as $r) {
                fputcsv($h, [$r->name, $r->phone, $r->status, $r->invalid_reason ?? '']);
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
        $recipient->delete();
        $campaign->recalculateEstimates();
        return back()->with('success', 'Recipient removed.');
    }
}
