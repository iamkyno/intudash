<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Checkbox settings — absent from the request means "off".
     */
    private const BOOLEAN_KEYS = ['vat_registered', 'smsportal_test_mode'];

    public function index()
    {
        $raw = AppSetting::all()->pluck('value', 'key');

        // Never expose secret values to the view; surface a "configured" flag instead.
        $settings = [];
        foreach ($raw as $key => $value) {
            if (AppSetting::isEncryptedKey($key)) {
                $settings[$key] = '';
                $settings["{$key}_set"] = !empty($value);
            } else {
                $settings[$key] = $value;
            }
        }

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string',
            'company_vat_number' => 'nullable|string',
            'invoice_prefix' => 'required|string|max:10',
            'vat_registered' => 'boolean',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'default_internal_cost' => 'required|numeric|min:0',
            'default_client_rate' => 'required|numeric|min:0',
            'smsportal_client_id' => 'nullable|string',
            'smsportal_api_secret' => 'nullable|string',
            'smsportal_test_mode' => 'boolean',
            'default_sender_name' => 'nullable|string|max:11',
            'aws_key' => 'nullable|string',
            'aws_secret' => 'nullable|string',
            'aws_region' => 'nullable|string',
            'ses_from_email' => 'nullable|email',
            'ses_from_name' => 'nullable|string',
            'internal_cost_per_email' => 'nullable|numeric|min:0',
            'default_client_rate_per_email' => 'nullable|numeric|min:0',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            // Blank secret submissions keep the existing stored value.
            if (AppSetting::isEncryptedKey($key) && ($value === null || $value === '')) {
                continue;
            }

            AppSetting::set($key, $value ?? '', 'general');
        }

        // Persist checkbox off-states (absent keys mean unchecked).
        foreach (self::BOOLEAN_KEYS as $key) {
            AppSetting::set($key, $request->boolean($key) ? '1' : '0', 'general');
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings saved successfully.');
    }
}
