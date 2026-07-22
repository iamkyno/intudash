@extends('layouts.app')
@section('title', 'Opt-Outs')
@section('page-title', 'Opt-Outs & Replies')

@section('content')
@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="card-header-title"><i class="bi bi-slash-circle"></i> Do-Not-Contact List</span>
                <span class="badge badge-neutral">{{ $optOuts->total() }}</span>
            </div>
            @if($optOuts->isEmpty())
                <div class="card-body text-center py-5" style="color:var(--text-tertiary);">
                    <i class="bi bi-check-circle" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">Nobody has opted out. Anyone who replies STOP will appear here automatically.</p>
                </div>
            @else
                <div class="card-body-flush">
                    <table class="table">
                        <thead><tr><th>Number</th><th>Source</th><th>Keyword</th><th>When</th><th></th></tr></thead>
                        <tbody>
                        @foreach($optOuts as $o)
                            <tr>
                                <td style="font-family:monospace;">{{ $o->phone_normalized }}</td>
                                <td><span class="badge badge-neutral">{{ $o->source === 'sms_reply' ? 'Replied' : 'Manual' }}</span></td>
                                <td class="table-muted">{{ $o->keyword ?: '—' }}</td>
                                <td class="table-muted">{{ $o->opted_out_at?->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('opt-outs.destroy', $o) }}" class="d-inline"
                                          onsubmit="return confirm('Remove {{ $o->phone_normalized }} from the do-not-contact list? They can be messaged again.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-sm btn-icon text-danger" title="Remove"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $optOuts->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-plus-lg me-1"></i>Add Manually</div>
            <div class="card-body">
                <form method="POST" action="{{ route('opt-outs.store') }}">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 0821234567" required>
                        <button class="btn btn-primary" type="submit">Opt Out</button>
                    </div>
                    <small class="text-muted">Stops all campaigns and reminders to this number, everywhere.</small>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="card-header-title"><i class="bi bi-chat-left-text"></i> Recent Replies</span>
                <span class="badge badge-neutral">{{ $replyCount }}</span>
            </div>
            @if($recentReplies->isEmpty())
                <div class="card-body text-center py-4" style="color:var(--text-tertiary);font-size:13px;">
                    No inbound replies yet.
                </div>
            @else
                <div class="card-body-flush" style="max-height:420px;overflow-y:auto;">
                    @foreach($recentReplies as $r)
                        <div style="padding:10px 16px;border-bottom:1px solid var(--surface-border);">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-family:monospace;font-size:13px;">{{ $r->from_number }}</span>
                                @if($r->is_opt_out)<span class="badge badge-danger">Opt-out</span>@endif
                            </div>
                            <div style="font-size:13px;color:var(--text-secondary);margin-top:2px;">{{ $r->message ?: '—' }}</div>
                            <div style="font-size:11px;color:var(--text-tertiary);">{{ $r->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
