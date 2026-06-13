<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ClientDataSource;
use Illuminate\Support\Facades\DB;

class DataSourceService
{
    /**
     * Test connectivity and return a sample of up to 5 rows.
     */
    public function preview(ClientDataSource $source): array
    {
        $pdo = $this->connect($source);
        $rows = $this->fetchRows($pdo, $source, 5);
        return ['success' => true, 'rows' => $rows, 'count' => count($rows)];
    }

    /**
     * Pull all rows from the data source and upsert them as campaign recipients.
     * Returns ['imported' => int, 'skipped' => int].
     */
    public function importToCampaign(ClientDataSource $source, Campaign $campaign): array
    {
        $pdo  = $this->connect($source);
        $rows = $this->fetchRows($pdo, $source);

        $imported = 0;
        $skipped  = 0;

        DB::transaction(function () use ($rows, $source, $campaign, &$imported, &$skipped) {
            foreach (array_chunk($rows, 500) as $chunk) {
                $records = [];
                foreach ($chunk as $row) {
                    $phone = $source->col_phone ? ($row[$source->col_phone] ?? null) : null;
                    $email = $source->col_email ? ($row[$source->col_email] ?? null) : null;
                    $name  = $source->col_name  ? ($row[$source->col_name]  ?? null) : null;

                    if (!$phone && !$email) {
                        $skipped++;
                        continue;
                    }

                    $normalized = null;
                    if ($phone) {
                        $res = PhoneNormalizer::validateAndNormalize((string) $phone);
                        if (!$res['valid']) {
                            $skipped++;
                            continue;
                        }
                        $normalized = $res['normalized'];
                    }

                    $records[] = [
                        'campaign_id'      => $campaign->id,
                        'client_id'        => $campaign->client_id,
                        'name'             => $name ? mb_substr((string) $name, 0, 191) : null,
                        'phone'            => $phone ? mb_substr((string) $phone, 0, 30) : null,
                        'phone_normalized' => $normalized,
                        'email'            => $email ? mb_substr((string) $email, 0, 191) : null,
                        'is_valid'         => true,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                    $imported++;
                }
                if ($records) {
                    CampaignRecipient::insertOrIgnore($records);
                }
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function connect(ClientDataSource $source): \PDO
    {
        $dsn = match ($source->driver) {
            'mysql'  => "mysql:host={$source->host};port={$source->port};dbname={$source->database};charset=utf8mb4",
            'pgsql'  => "pgsql:host={$source->host};port={$source->port};dbname={$source->database}",
            'sqlsrv' => "sqlsrv:Server={$source->host},{$source->port};Database={$source->database}",
        };

        $pdo = new \PDO($dsn, $source->username, $source->password, [
            \PDO::ATTR_TIMEOUT => 10,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        return $pdo;
    }

    private function fetchRows(\PDO $pdo, ClientDataSource $source, ?int $limit = null): array
    {
        if ($source->custom_query) {
            $sql = $source->custom_query;
            if ($limit !== null) {
                $sql = "SELECT * FROM ({$sql}) AS _ds LIMIT {$limit}";
            }
        } else {
            $table = $this->quoteIdentifier($source->table_or_view, $source->driver);
            $sql   = "SELECT * FROM {$table}";
            if ($limit !== null) {
                $sql .= " LIMIT {$limit}";
            }
        }

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function quoteIdentifier(string $name, string $driver): string
    {
        $q = $driver === 'sqlsrv' ? ['[', ']'] : ['`', '`'];
        return $q[0] . str_replace($q[1], '', $name) . $q[1];
    }
}
