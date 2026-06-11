<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SmsLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_clients' => Client::count(),
            'draft_campaigns' => Campaign::where('status', 'draft')->count(),
            'awaiting_payment' => Campaign::where('status', 'awaiting_payment')->count(),
            'ready_to_schedule' => Campaign::where('status', 'ready_to_schedule')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
            'sent_campaigns' => Campaign::whereIn('status', ['completed', 'partially_completed'])->count(),
            'failed_sms' => SmsLog::whereIn('status', ['undelivered', 'expired', 'failed', 'no_route'])->count(),
            'delivered_sms' => SmsLog::where('status', 'delivered')->count(),
            'quarterly_sms' => SmsLog::whereIn('status', ['delivered', 'submitted'])
                ->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()])
                ->count(),
            'revenue_collected' => Invoice::where('status', 'paid')->sum('total'),
            'estimated_profit' => Campaign::whereIn('status', ['completed', 'partially_completed'])->sum('actual_profit'),
        ];

        $recentActivity = AuditLog::with('user')
            ->latest()
            ->limit(15)
            ->get();

        $campaignsByStatus = Campaign::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $monthlyVolume = SmsLog::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard', compact('stats', 'recentActivity', 'campaignsByStatus', 'monthlyVolume'));
    }
}
