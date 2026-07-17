<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\SendingDomain;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class SesDomainService
{
    private ?SesClient $client = null;

    private function client(): SesClient
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = new SesClient([
            'version' => 'latest',
            'region'  => AppSetting::get('aws_region', 'us-east-1'),
            'credentials' => [
                'key'    => AppSetting::get('aws_key', ''),
                'secret' => AppSetting::get('aws_secret', ''),
            ],
        ]);
    }

    /**
     * Register a domain with SES and capture the DNS records the user needs to add.
     * Safe to call again for an existing (still-pending) domain to refetch tokens.
     */
    public function beginVerification(SendingDomain $domain): void
    {
        $client = $this->client();

        $identity = $client->verifyDomainIdentity(['Domain' => $domain->domain]);
        $dkim     = $client->verifyDomainDkim(['Domain' => $domain->domain]);

        $domain->update([
            'verification_token'  => $identity['VerificationToken'] ?? null,
            'dkim_tokens'          => $dkim['DkimTokens'] ?? [],
            'verification_status'  => 'pending',
            'dkim_status'          => 'pending',
            'last_checked_at'      => now(),
        ]);
    }

    /**
     * Poll SES for current verification status and update the local record.
     */
    public function refreshStatus(SendingDomain $domain): SendingDomain
    {
        $client = $this->client();

        $verification = $client->getIdentityVerificationAttributes(['Identities' => [$domain->domain]]);
        $dkim         = $client->getIdentityDkimAttributes(['Identities' => [$domain->domain]]);

        $verifyStatus = strtolower($verification['VerificationAttributes'][$domain->domain]['VerificationStatus'] ?? 'pending');
        $dkimStatus   = strtolower($dkim['DkimAttributes'][$domain->domain]['DkimVerificationStatus'] ?? 'pending');

        $domain->update([
            'verification_status' => in_array($verifyStatus, ['success']) ? 'verified' : (in_array($verifyStatus, ['failed']) ? 'failed' : 'pending'),
            'dkim_status'          => in_array($dkimStatus, ['success']) ? 'verified' : (in_array($dkimStatus, ['failed']) ? 'failed' : 'pending'),
            'last_checked_at'      => now(),
        ]);

        return $domain->fresh();
    }

    public function deregister(SendingDomain $domain): void
    {
        try {
            $this->client()->deleteIdentity(['Identity' => $domain->domain]);
        } catch (AwsException $e) {
            // Non-fatal — the local record is still removed even if SES cleanup fails.
            Log::warning('SES deleteIdentity failed', ['domain' => $domain->domain, 'error' => $e->getAwsErrorMessage()]);
        }
    }
}
