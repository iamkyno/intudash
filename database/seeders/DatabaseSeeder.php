<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@intudash.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // App settings defaults
        $defaults = [
            'company_name' => 'IntuDash SMS Services',
            'company_email' => 'billing@intudash.com',
            'company_phone' => '+27 11 000 0000',
            'invoice_prefix' => 'INV',
            'vat_enabled' => '1',
            'vat_rate' => '15',
            'default_internal_cost' => '0.1200',
            'default_client_rate' => '0.2500',
            'smsportal_test_mode' => '1',
        ];

        foreach ($defaults as $key => $value) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => 'general']);
        }

        // Demo clients
        $clients = [
            ['company_name' => 'Acme Corp', 'contact_person' => 'John Smith', 'email' => 'john@acme.co.za', 'phone' => '+27831234567', 'default_sms_rate' => 0.30],
            ['company_name' => 'TechStart ZA', 'contact_person' => 'Sarah Johnson', 'email' => 'sarah@techstart.co.za', 'phone' => '+27729876543', 'default_sms_rate' => 0.25],
            ['company_name' => 'Retail Plus', 'contact_person' => 'Mike Brown', 'email' => 'mike@retailplus.co.za', 'phone' => '+27611112222', 'default_sms_rate' => 0.28],
        ];

        foreach ($clients as $clientData) {
            $client = Client::firstOrCreate(
                ['email' => $clientData['email']],
                array_merge($clientData, [
                    'billing_address' => '123 Business Park, Sandton, Gauteng, 2196',
                    'status' => 'active',
                ])
            );

            // Create a demo campaign for each client
            $campaign = Campaign::create([
                'client_id' => $client->id,
                'user_id' => $admin->id,
                'name' => 'Demo Campaign — ' . $client->company_name,
                'message' => 'Hello! This is a test SMS from ' . $client->company_name . '. Reply STOP to unsubscribe.',
                'status' => 'draft',
                'internal_cost_per_sms' => 0.12,
                'client_rate_per_sms' => $client->default_sms_rate,
                'estimated_recipients' => 100,
                'sms_segments' => 1,
                'estimated_cost' => 12.00,
                'estimated_charge' => $client->default_sms_rate * 100,
                'estimated_profit' => ($client->default_sms_rate - 0.12) * 100,
                'provider' => 'smsportal',
            ]);

            // Add some demo recipients
            $phones = ['+27831111001', '+27831111002', '+27831111003', '+27831111004', '+27831111005'];
            foreach ($phones as $i => $phone) {
                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'name' => 'Demo Recipient ' . ($i + 1),
                    'phone' => $phone,
                    'phone_normalized' => $phone,
                    'status' => 'valid',
                ]);
            }

            $campaign->update([
                'status' => 'recipients_uploaded',
                'actual_recipients' => 5,
                'estimated_recipients' => 5,
                'estimated_cost' => 5 * 0.12,
                'estimated_charge' => 5 * $client->default_sms_rate,
                'estimated_profit' => 5 * ($client->default_sms_rate - 0.12),
            ]);
        }

        // One paid campaign with invoice to show the full workflow
        $client = Client::first();
        $paidCampaign = Campaign::create([
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'name' => 'Paid & Ready Campaign',
            'message' => 'Special offer just for you! Use code SAVE20 for 20% off your next purchase.',
            'status' => 'ready_to_schedule',
            'internal_cost_per_sms' => 0.12,
            'client_rate_per_sms' => 0.30,
            'estimated_recipients' => 50,
            'actual_recipients' => 50,
            'sms_segments' => 1,
            'estimated_cost' => 6.00,
            'estimated_charge' => 15.00,
            'estimated_profit' => 9.00,
            'admin_override_payment' => false,
            'provider' => 'smsportal',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'campaign_id' => $paidCampaign->id,
            'invoice_number' => 'INV-2024-0001',
            'status' => 'paid',
            'sms_quantity' => 50,
            'sms_rate' => 0.30,
            'subtotal' => 15.00,
            'vat_enabled' => true,
            'vat_rate' => 15,
            'vat_amount' => 2.25,
            'total' => 17.25,
            'paid_at' => now()->subDay(),
            'due_date' => now()->addDays(6),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Bulk SMS — Paid & Ready Campaign (50 SMS @ R0.30 each)',
            'quantity' => 50,
            'unit_price' => 0.30,
            'total' => 15.00,
        ]);
    }
}
