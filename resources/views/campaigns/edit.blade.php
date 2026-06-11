@extends('layouts.app')
@section('title', 'Edit Campaign')
@section('page-title', 'Edit Campaign')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Edit — {{ $campaign->name }}</div>
            <div class="card-body">
                <form action="{{ route('campaigns.update', $campaign) }}" method="POST">
                    @csrf @method('PUT')
                    @include('campaigns._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Update Campaign</button>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">SMS Counter</div>
            <div class="card-body">
                <div id="sms-counter" class="sms-counter">
                    <div class="d-flex justify-content-between">
                        <span>Characters: <strong id="char-count">0</strong></span>
                        <span>Segments: <strong id="segment-count">1</strong></span>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" id="char-progress"></div>
                    </div>
                    <small class="text-muted mt-1 d-block" id="char-remaining"></small>
                    <div id="extended-warning" class="alert alert-warning py-1 px-2 mt-2 small d-none">
                        Extended GSM characters detected — each counts as 2.
                    </div>
                    <div id="multi-segment-warning" class="alert alert-info py-1 px-2 mt-2 small d-none">
                        Multi-segment SMS (153 chars/segment).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const EXTENDED = ['^','{','}','\\','[','~',']','|','€'];
function countSms(msg) {
    let len = 0, hasExtended = false;
    for (const ch of msg) {
        if (EXTENDED.includes(ch)) { len += 2; hasExtended = true; }
        else len++;
    }
    const segments = len <= 160 ? 1 : Math.ceil(len / 153);
    const remaining = len <= 160 ? (160 - len) : (153 - (len % 153));
    return { len, segments, hasExtended, remaining };
}
function updateEstimator() {
    const msg = document.getElementById('message').value;
    const { len, segments, hasExtended, remaining } = countSms(msg);
    document.getElementById('char-count').textContent = len;
    document.getElementById('segment-count').textContent = segments;
    document.getElementById('char-remaining').textContent = remaining + ' remaining';
    const pct = Math.min((len / 160) * 100, 100);
    const bar = document.getElementById('char-progress');
    bar.style.width = pct + '%';
    bar.className = 'progress-bar ' + (len > 160 ? 'bg-danger' : len > 140 ? 'bg-warning' : 'bg-success');
    document.getElementById('extended-warning').classList.toggle('d-none', !hasExtended);
    document.getElementById('multi-segment-warning').classList.toggle('d-none', segments <= 1);
}
document.getElementById('message')?.addEventListener('input', updateEstimator);
updateEstimator();
</script>
@endpush
