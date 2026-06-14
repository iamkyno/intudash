<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\SmsLog;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_clients'      => Client::count(),
            'draft_campaigns'    => Campaign::where('status', 'draft')->count(),
            'awaiting_payment'   => Campaign::where('status', 'awaiting_payment')->count(),
            'ready_to_schedule'  => Campaign::where('status', 'ready_to_schedule')->count(),
            'scheduled'          => Campaign::where('status', 'scheduled')->count(),
            'sent_campaigns'     => Campaign::whereIn('status', ['completed', 'partially_completed'])->count(),
            'failed_sms'         => SmsLog::whereIn('status', ['undelivered', 'expired', 'failed', 'no_route'])->count(),
            'delivered_sms'      => SmsLog::where('status', 'delivered')->count(),
            'quarterly_sms'      => SmsLog::whereIn('status', ['delivered', 'submitted'])
                ->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()])
                ->count(),
            'revenue_collected'  => Invoice::where('status', 'paid')->sum('total'),
            'outstanding'        => Invoice::whereIn('status', ['sent', 'overdue'])->sum('total'),
            'estimated_profit'   => Campaign::whereIn('status', ['completed', 'partially_completed'])->sum('actual_profit'),
            'pending_reminders'  => Reminder::where('status', 'pending')->where('send_at', '>', now())->count(),
            'reminders_due'      => Reminder::where('status', 'pending')->where('send_at', '<=', now())->count(),
        ];

        $recentActivity = AuditLog::with('user')->latest()->limit(10)->get();

        $campaignsByStatus = Campaign::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Upcoming scheduled campaigns (next 7 days)
        $upcomingCampaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        // Campaigns needing attention (stuck awaiting payment)
        $needsAttention = Campaign::whereIn('status', ['awaiting_payment', 'ready_to_schedule'])
            ->with('client')
            ->latest()
            ->limit(5)
            ->get();

        $monthlyVolume = SmsLog::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard', compact(
            'stats', 'recentActivity', 'campaignsByStatus',
            'upcomingCampaigns', 'needsAttention', 'monthlyVolume'
        ));
    }
}
