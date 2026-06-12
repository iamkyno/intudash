<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IntuDash') — SMS Campaign Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ── Design tokens ───────────────────────────────────────────── */
        :root {
            --color-brand:          #E8694A;
            --color-brand-hover:    #C04A2E;
            --color-brand-subtle:   #FDF0EC;
            --color-ink:            #0B0E0F;
            --color-ink-raised:     #16201E;
            --color-mist:           #F4F0ED;
            --color-mist-secondary: rgba(244,240,237,0.55);
            --color-mist-tertiary:  rgba(244,240,237,0.22);
            --color-border:         rgba(232,105,74,0.18);
            --color-border-strong:  rgba(232,105,74,0.35);
            --color-success:        #4CAF7D;
            --color-warning:        #F4A91B;
            --color-danger:         #E8694A;
            --color-info:           #5C9EE8;
            --color-neutral:        #888780;

            --sidebar-w: 248px;
            --topbar-h:  56px;
            --radius:    8px;
            --radius-sm: 5px;
        }

        /* ── Reset / base ────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--color-ink);
            color: var(--color-mist);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        a:hover { color: var(--color-brand); }

        /* ── Sidebar ─────────────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--color-ink);
            border-right: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            z-index: 200;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            height: var(--topbar-h);
            border-bottom: 1px solid var(--color-border);
            flex-shrink: 0;
        }
        .sidebar-brand .brand-icon {
            width: 28px; height: 28px;
            background: var(--color-brand);
            border-radius: 7px;
            display: grid; place-items: center;
            font-size: 14px; color: #fff;
            flex-shrink: 0;
        }
        .sidebar-brand .brand-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--color-mist);
            letter-spacing: -0.01em;
        }
        .sidebar-brand .brand-sub {
            font-size: 10px;
            color: var(--color-mist-tertiary);
            letter-spacing: 0.02em;
            line-height: 1;
            margin-top: 1px;
        }

        .sidebar-nav { padding: 8px 0; flex: 1; }

        .nav-section-label {
            padding: 16px 20px 5px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--color-mist-tertiary);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 20px;
            font-size: 13.5px;
            font-weight: 450;
            color: rgba(244,240,237,0.62);
            border-left: 2px solid transparent;
            transition: color 0.15s, background 0.15s, border-color 0.15s;
            margin: 1px 0;
        }
        .sidebar-link i { font-size: 14px; width: 16px; text-align: center; flex-shrink: 0; opacity: 0.7; }
        .sidebar-link:hover {
            color: var(--color-mist);
            background: rgba(244,240,237,0.04);
            border-left-color: var(--color-border-strong);
        }
        .sidebar-link.active {
            color: var(--color-brand);
            background: rgba(232,105,74,0.08);
            border-left-color: var(--color-brand);
            font-weight: 500;
        }
        .sidebar-link.active i { opacity: 1; }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--color-border);
            flex-shrink: 0;
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 8px;
            border-radius: var(--radius-sm);
        }
        .sidebar-user:hover { background: rgba(244,240,237,0.04); }
        .user-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(232,105,74,0.18);
            color: var(--color-brand);
            display: grid; place-items: center;
            font-size: 11px; font-weight: 600;
            flex-shrink: 0;
        }
        .user-info .user-name { font-size: 13px; font-weight: 500; color: var(--color-mist); line-height: 1.2; }
        .user-info .user-role { font-size: 11px; color: var(--color-mist-tertiary); }

        /* ── Main content ────────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ──────────────────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--topbar-h);
            background: var(--color-ink);
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 12px;
        }
        .topbar-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--color-mist);
            letter-spacing: -0.01em;
        }
        .topbar-spacer { flex: 1; }
        .topbar-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(244,180,27,0.12);
            color: var(--color-warning);
            border: 1px solid rgba(244,169,27,0.25);
        }
        .topbar-divider { width: 1px; height: 18px; background: var(--color-border); }
        .topbar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            background: transparent;
            color: var(--color-mist-secondary);
            cursor: pointer;
            transition: all 0.15s;
            font-size: 14px;
        }
        .topbar-btn:hover { border-color: var(--color-border-strong); color: var(--color-mist); background: rgba(244,240,237,0.04); }

        /* ── Page content ────────────────────────────────────────────── */
        .page { padding: 24px; flex: 1; }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .page-header h1 {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-mist);
            letter-spacing: -0.02em;
            margin: 0;
        }
        .page-header p { font-size: 13px; color: var(--color-mist-secondary); margin: 2px 0 0; }

        /* ── Cards ───────────────────────────────────────────────────── */
        .card {
            background: var(--color-ink-raised);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--color-border);
            background: transparent;
        }
        .card-header-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--color-mist);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .card-header-title i { color: var(--color-brand); font-size: 13px; }
        .card-body { padding: 18px; }
        .card-body-flush { padding: 0; }

        /* ── Stat cards ──────────────────────────────────────────────── */
        .stat-card {
            background: var(--color-ink-raised);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 18px 20px;
            transition: border-color 0.15s;
        }
        .stat-card:hover { border-color: var(--color-border-strong); }
        .stat-label {
            font-size: 11.5px;
            font-weight: 500;
            color: rgba(244,240,237,0.45);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 600;
            color: var(--color-mist);
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .stat-value.brand { color: var(--color-brand); }
        .stat-value.success { color: var(--color-success); }
        .stat-value.warning { color: var(--color-warning); }
        .stat-value.danger { color: var(--color-danger); }
        .stat-meta {
            margin-top: 5px;
            font-size: 12px;
            color: rgba(244,240,237,0.38);
        }
        .stat-dot {
            display: inline-block;
            width: 7px; height: 7px;
            border-radius: 50%;
            margin-right: 5px;
            flex-shrink: 0;
        }

        /* ── Tables ──────────────────────────────────────────────────── */
        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--color-mist);
            --bs-table-border-color: var(--color-border);
            --bs-table-hover-bg: rgba(244,240,237,0.04);
            --bs-table-hover-color: var(--color-mist);
            margin: 0;
        }
        .table thead th {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: rgba(244,240,237,0.45);
            border-bottom: 1px solid var(--color-border);
            padding: 10px 16px;
            white-space: nowrap;
            background: transparent;
        }
        .table tbody td {
            padding: 12px 16px;
            color: rgba(244,240,237,0.75);
            border-bottom: 1px solid var(--color-border);
            vertical-align: middle;
            font-size: 13.5px;
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: rgba(244,240,237,0.025); }
        .table-link { color: var(--color-mist) !important; font-weight: 500; }
        .table-link:hover { color: var(--color-brand) !important; }
        .table-muted { color: rgba(244,240,237,0.4) !important; font-size: 12.5px; }

        /* ── Badges ──────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.01em;
            border: 1px solid transparent;
        }
        .badge-brand    { background: rgba(232,105,74,0.15);  color: var(--color-brand);   border-color: rgba(232,105,74,0.25); }
        .badge-success  { background: rgba(76,175,125,0.12);  color: var(--color-success);  border-color: rgba(76,175,125,0.22); }
        .badge-warning  { background: rgba(244,169,27,0.12);  color: var(--color-warning);  border-color: rgba(244,169,27,0.22); }
        .badge-danger   { background: rgba(232,105,74,0.12);  color: var(--color-danger);   border-color: rgba(232,105,74,0.22); }
        .badge-info     { background: rgba(92,158,232,0.12);  color: var(--color-info);     border-color: rgba(92,158,232,0.22); }
        .badge-neutral  { background: rgba(136,135,128,0.12); color: var(--color-neutral);  border-color: rgba(136,135,128,0.22); }
        .badge-dot::before { content: ''; display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

        /* ── Buttons ─────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            line-height: 1;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .btn-primary {
            background: var(--color-brand);
            color: #fff;
            border-color: var(--color-brand);
        }
        .btn-primary:hover { background: var(--color-brand-hover); border-color: var(--color-brand-hover); color: #fff; }
        .btn-ghost {
            background: transparent;
            color: var(--color-mist-secondary);
            border-color: var(--color-border);
        }
        .btn-ghost:hover { background: rgba(244,240,237,0.05); border-color: var(--color-border-strong); color: var(--color-mist); }
        .btn-danger-ghost {
            background: transparent;
            color: var(--color-danger);
            border-color: rgba(232,105,74,0.25);
        }
        .btn-danger-ghost:hover { background: rgba(232,105,74,0.08); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn-icon { width: 30px; height: 30px; padding: 0; justify-content: center; }

        /* ── Forms ───────────────────────────────────────────────────── */
        .form-label {
            font-size: 12.5px;
            font-weight: 500;
            color: rgba(244,240,237,0.7);
            margin-bottom: 6px;
        }
        .form-control, .form-select {
            background: rgba(244,240,237,0.05);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            color: var(--color-mist);
            font-size: 13.5px;
            padding: 8px 12px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-control::placeholder { color: rgba(244,240,237,0.28); }
        .form-control:focus, .form-select:focus {
            background: rgba(244,240,237,0.06);
            border-color: var(--color-brand);
            box-shadow: 0 0 0 3px rgba(232,105,74,0.12);
            color: var(--color-mist);
            outline: none;
        }
        .form-select option { background: var(--color-ink-raised); color: var(--color-mist); }
        .form-control.is-invalid { border-color: var(--color-danger); }
        .invalid-feedback { color: var(--color-danger); font-size: 12px; }
        .input-group-text {
            background: rgba(244,240,237,0.05);
            border: 1px solid var(--color-border);
            color: rgba(244,240,237,0.5);
            font-size: 13px;
        }
        .form-check-input {
            background-color: rgba(244,240,237,0.08);
            border-color: var(--color-border-strong);
        }
        .form-check-input:checked {
            background-color: var(--color-brand);
            border-color: var(--color-brand);
        }
        .form-check-input:focus { box-shadow: 0 0 0 3px rgba(232,105,74,0.12); }

        /* ── Alerts / toasts ─────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 13.5px;
            border: 1px solid transparent;
            margin-bottom: 16px;
        }
        .alert .btn-close { filter: none; opacity: 0.5; margin-left: auto; align-self: center; }
        .alert .btn-close:hover { opacity: 1; }
        .alert-success { background: rgba(76,175,125,0.08); border-color: rgba(76,175,125,0.2); color: var(--color-success); }
        .alert-success .btn-close { filter: invert(60%) sepia(60%) saturate(400%) hue-rotate(100deg); }
        .alert-danger  { background: rgba(232,105,74,0.08); border-color: rgba(232,105,74,0.2);  color: var(--color-danger);  }
        .alert-danger .btn-close { filter: invert(50%) sepia(60%) saturate(500%) hue-rotate(330deg); }
        .alert ul { margin: 6px 0 0 16px; padding: 0; }

        /* ── Progress ────────────────────────────────────────────────── */
        .progress {
            background: rgba(244,240,237,0.08);
            border-radius: 99px;
            overflow: hidden;
        }
        .progress-bar { border-radius: 99px; transition: width 0.4s ease; }
        .progress-bar.brand   { background: var(--color-brand); }
        .progress-bar.success { background: var(--color-success); }
        .progress-bar.danger  { background: var(--color-danger); }
        .progress-bar.warning { background: var(--color-warning); }

        /* ── SMS counter ─────────────────────────────────────────────── */
        .sms-counter {
            background: rgba(244,240,237,0.03);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 12px 14px;
            font-size: 12.5px;
        }

        /* ── Pagination ──────────────────────────────────────────────── */
        .pagination { gap: 3px; }
        .page-link {
            background: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-mist-secondary);
            border-radius: var(--radius-sm) !important;
            padding: 5px 10px;
            font-size: 13px;
        }
        .page-link:hover { background: rgba(244,240,237,0.05); border-color: var(--color-border-strong); color: var(--color-mist); }
        .page-item.active .page-link { background: var(--color-brand); border-color: var(--color-brand); color: #fff; }
        .page-item.disabled .page-link { opacity: 0.3; }

        /* ── Modal ───────────────────────────────────────────────────── */
        .modal-content {
            background: var(--color-ink-raised);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
        }
        .modal-header {
            border-bottom: 1px solid var(--color-border);
            padding: 16px 20px;
        }
        .modal-title { font-size: 15px; font-weight: 600; color: var(--color-mist); }
        .modal-header .btn-close { filter: invert(80%); opacity: 0.5; }
        .modal-header .btn-close:hover { opacity: 1; }
        .modal-body { padding: 20px; }
        .modal-footer { border-top: 1px solid var(--color-border); padding: 14px 20px; gap: 8px; }
        .modal-backdrop { background: rgba(11,14,15,0.7); backdrop-filter: blur(4px); }

        /* ── Utilities ───────────────────────────────────────────────── */
        .text-brand   { color: var(--color-brand) !important; }
        .text-mist    { color: var(--color-mist) !important; }
        .text-muted   { color: rgba(244,240,237,0.55) !important; }
        .text-faint   { color: rgba(244,240,237,0.35) !important; }
        .text-success { color: var(--color-success) !important; }
        .text-warning { color: var(--color-warning) !important; }
        .text-danger  { color: var(--color-danger) !important; }
        .text-info    { color: var(--color-info) !important; }
        .border-subtle { border-color: var(--color-border) !important; }
        .divider { border: none; border-top: 1px solid var(--color-border); margin: 16px 0; }
        code {
            background: rgba(232,105,74,0.1);
            color: var(--color-brand);
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* ── Scrollbar ───────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(244,240,237,0.12); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(244,240,237,0.2); }

        /* ── Mobile ──────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.25s ease; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .page { padding: 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-send-fill"></i></div>
        <div>
            <div class="brand-name">IntuDash</div>
            <div class="brand-sub">SMS Platform</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-squares-fill"></i> Dashboard
        </a>

        <div class="nav-section-label">CRM</div>
        <a href="{{ route('clients.index') }}" class="sidebar-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Clients
        </a>

        <div class="nav-section-label">Campaigns</div>
        <a href="{{ route('campaigns.index') }}" class="sidebar-link {{ request()->routeIs('campaigns.index') && !request('status') ? 'active' : '' }}">
            <i class="bi bi-megaphone"></i> All Campaigns
        </a>
        <a href="{{ route('campaigns.index', ['status' => 'draft']) }}" class="sidebar-link {{ request('status') === 'draft' ? 'active' : '' }}">
            <i class="bi bi-file-earmark"></i> Drafts
        </a>
        <a href="{{ route('campaigns.index', ['status' => 'ready_to_schedule']) }}" class="sidebar-link {{ request('status') === 'ready_to_schedule' ? 'active' : '' }}">
            <i class="bi bi-calendar-check"></i> Ready to Schedule
        </a>
        <a href="{{ route('campaigns.index', ['status' => 'scheduled']) }}" class="sidebar-link {{ request('status') === 'scheduled' ? 'active' : '' }}">
            <i class="bi bi-clock"></i> Scheduled
        </a>
        <a href="{{ route('campaigns.create') }}" class="sidebar-link {{ request()->routeIs('campaigns.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle"></i> New Campaign
        </a>

        <div class="nav-section-label">Billing</div>
        <a href="{{ route('invoices.index') }}" class="sidebar-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Invoices
        </a>

        <div class="nav-section-label">System</div>
        <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i> Settings
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                <div class="user-role">Administrator</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="ms-auto">
                @csrf
                <button type="submit" class="topbar-btn" title="Sign out" style="width:26px;height:26px;font-size:13px;">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Main --}}
<div class="main" id="main">
    {{-- Topbar --}}
    <header class="topbar">
        <button class="topbar-btn d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
        <div class="topbar-spacer"></div>
        @if(config('services.smsportal.test_mode'))
            <span class="topbar-pill"><i class="bi bi-flask"></i> Test Mode</span>
        @endif
        <div class="topbar-divider"></div>
        <span style="font-size:12px;color:var(--color-mist-tertiary);">{{ date('d M Y') }}</span>
    </header>

    {{-- Page --}}
    <div class="page">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>
                    <strong>Please fix the following:</strong>
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
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
