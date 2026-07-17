<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\DB;

class CampaignRecipientImportService
{
    /**
     * Validate, dedupe, and insert a batch of contacts (anything with
     * ->name/->phone/->email, e.g. ClientRecipient rows) into a campaign's
     * recipient list — the same logic a CSV upload runs, just fed from a
     * saved group instead of a file. Returns the same shape of stats.
     */
    public function importContacts(Campaign $campaign, iterable $contacts): array
    {
        $type = $campaign->campaign_type ?? 'sms';

        $stats = ['total' => 0, 'valid' => 0, 'duplicate' => 0, 'invalid' => 0];
        $existingNumbers = $campaign->recipients()
            ->where('status', 'valid')
            ->pluck('phone_normalized')
            ->filter()
            ->toArray();

        $seenNumbers = [];
        $rows = [];
        $now = now();

        foreach ($contacts as $contact) {
            $stats['total']++;

            $row = RecipientValidationService::validateRow(
                $contact->name ?? null,
                $contact->phone ?? '',
                $contact->email ?? '',
                $type
            );

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

        DB::transaction(function () use ($rows, $campaign, $stats) {
            foreach (array_chunk($rows, 500) as $chunk) {
                CampaignRecipient::insert($chunk);
            }

            if ($stats['valid'] > 0 && $campaign->status === 'draft') {
                $campaign->update(['status' => 'recipients_uploaded']);
            }
        });

        $campaign->recalculateEstimates();

        return $stats;
    }
}
