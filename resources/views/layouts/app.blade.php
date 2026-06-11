<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IntuDash') — SMS Campaign Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1a1d2e;
            --sidebar-text: #a8b2d8;
            --sidebar-active: #6c63ff;
            --header-height: 60px;
        }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .sidebar-brand { padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.07); }
        .sidebar-brand h5 { color: #fff; font-weight: 700; margin: 0; }
        .sidebar-brand small { color: var(--sidebar-text); font-size: 0.72rem; }
        .sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 0.65rem 1.5rem;
            border-radius: 0;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: flex; align-items: center; gap: 0.65rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: rgba(108,99,255,0.15);
            border-left: 3px solid var(--sidebar-active);
        }
        .sidebar .nav-section {
            padding: 0.75rem 1.5rem 0.25rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(168,178,216,0.5);
            font-weight: 600;
        }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar {
            background: #fff;
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            border-bottom: 1px solid #e9ecef;
            position: sticky; top: 0; z-index: 999;
        }
        .page-content { padding: 1.5rem; }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e9ecef;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .stat-card .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 0.75rem;
        }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; color: #1a1d2e; line-height: 1; }
        .stat-card .stat-label { color: #6c757d; font-size: 0.8rem; margin-top: 0.25rem; }
        .card { border: 1px solid #e9ecef; border-radius: 12px; }
        .card-header { background: #fff; border-bottom: 1px solid #e9ecef; font-weight: 600; padding: 1rem 1.25rem; }
        .badge { font-size: 0.72rem; font-weight: 500; }
        .table th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; border-top: none; }
        .sms-counter { background: #f8f9fa; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.82rem; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h5><i class="bi bi-send-fill text-primary me-2"></i>IntuDash</h5>
        <small>SMS Campaign Manager</small>
    </div>
    <nav class="py-2">
        <div class="nav-section">Overview</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <div class="nav-section">CRM</div>
        <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Clients
        </a>
        <div class="nav-section">Campaigns</div>
        <a href="{{ route('campaigns.index') }}" class="nav-link {{ request()->routeIs('campaigns.index') ? 'active' : '' }}">
            <i class="bi bi-megaphone-fill"></i> All Campaigns
        </a>
        <a href="{{ route('campaigns.index', ['status' => 'draft']) }}" class="nav-link">
            <i class="bi bi-file-earmark-text"></i> Drafts
        </a>
        <a href="{{ route('campaigns.index', ['status' => 'ready_to_schedule']) }}" class="nav-link">
            <i class="bi bi-calendar-check-fill"></i> Ready to Schedule
        </a>
        <a href="{{ route('campaigns.index', ['status' => 'scheduled']) }}" class="nav-link">
            <i class="bi bi-clock-fill"></i> Scheduled
        </a>
        <a href="{{ route('campaigns.create') }}" class="nav-link {{ request()->routeIs('campaigns.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle-fill"></i> New Campaign
        </a>
        <div class="nav-section">Billing</div>
        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
            <i class="bi bi-receipt-cutoff"></i> Invoices
        </a>
        <div class="nav-section">System</div>
        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i> Settings
        </a>
    </nav>
</div>

<div class="main-content">
    <div class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-md-none me-3" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h6 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h6>
        <div class="ms-auto d-flex align-items-center gap-2">
            @if(config('services.smsportal.test_mode'))
                <span class="badge bg-warning text-dark"><i class="bi bi-flask"></i> Test Mode</span>
            @endif
            <span class="text-muted small">{{ auth()->user()->name ?? 'Admin' }}</span>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill me-2"></i><strong>Please fix the errors below:</strong>
                <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
