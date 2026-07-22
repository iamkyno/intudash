<div class="card mb-3">
    <div class="card-header" style="cursor:pointer;" onclick="this.nextElementSibling.classList.toggle('d-none')" title="Toggle status guide">
        <span class="card-header-title"><i class="bi bi-info-circle"></i> Campaign Status Guide</span>
        <span style="font-size:11px;color:var(--text-tertiary);">click to expand</span>
    </div>
    <div class="card-body d-none">
        <div class="row g-2">
            @foreach([
                ['draft',              'badge-neutral', 'Draft',              'Campaign created but not yet ready. Add recipients or set an estimated count.'],
                ['recipients_uploaded','badge-info',    'Recipients Uploaded', 'Recipients CSV has been imported. Ready to generate a quote or invoice.'],
                ['invoice_generated',  'badge-info',    'Invoice Generated',   'Invoice has been issued. Awaiting payment from the client.'],
                ['awaiting_payment',   'badge-warning',  'Awaiting Payment',    'Payment reminder stage. Campaign unlocks once invoice is marked paid.'],
                ['ready_to_schedule',  'badge-success',  'Ready to Schedule',   'Invoice paid. You can now schedule or send the campaign.'],
                ['scheduled',          'badge-brand',   'Scheduled',           'Campaign is queued and will start sending at the scheduled date/time.'],
                ['sending',            'badge-info',    'Sending',             'Campaign is actively dispatching SMS messages via the queue.'],
                ['paused',             'badge-warning',  'Paused',              'Sending was paused manually. Resume to continue or Stop to cancel.'],
                ['completed',          'badge-success',  'Completed',           'All messages sent and final delivery receipts received from the gateway.'],
                ['partially_completed','badge-warning',  'Partially Completed', 'Some messages were delivered, others failed. Check the delivery report.'],
                ['failed',             'badge-danger',  'Failed',              'All messages failed to send. Check provider logs and retry if needed.'],
                ['cancelled',          'badge-neutral', 'Cancelled',           'Campaign was manually stopped and will not send.'],
            ] as [$status, $badge, $label, $desc])
            <div class="col-md-6">
                <div class="d-flex align-items-start gap-2" style="padding:6px 0;border-bottom:1px solid var(--surface-border);">
                    <span class="badge {{ $badge }} badge-dot" style="margin-top:2px;flex-shrink:0;white-space:nowrap;">{{ $label }}</span>
                    <span style="font-size:12px;color:var(--text-secondary);">{{ $desc }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
