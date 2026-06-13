@php $source = $source ?? null; @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $source?->name) }}" placeholder="e.g. Booking system DB">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Driver <span class="text-danger">*</span></label>
        <select name="driver" id="ds-driver" class="form-select @error('driver') is-invalid @enderror"
                onchange="updateDefaultPort()">
            @foreach(['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlsrv' => 'SQL Server'] as $val => $label)
            <option value="{{ $val }}" {{ old('driver', $source?->driver ?? 'mysql') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('driver')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Port <span class="text-danger">*</span></label>
        <input type="number" name="port" id="ds-port" class="form-control @error('port') is-invalid @enderror"
               value="{{ old('port', $source?->port ?? 3306) }}" min="1" max="65535">
        @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Host <span class="text-danger">*</span></label>
        <input type="text" name="host" class="form-control @error('host') is-invalid @enderror"
               value="{{ old('host', $source?->host) }}" placeholder="db.example.com or 192.168.1.10">
        @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Database Name <span class="text-danger">*</span></label>
        <input type="text" name="database" class="form-control @error('database') is-invalid @enderror"
               value="{{ old('database', $source?->database) }}" placeholder="my_app_db">
        @error('database')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
               value="{{ old('username', $source?->username) }}" autocomplete="off">
        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Password {{ $source ? '(leave blank to keep current)' : '' }}</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <hr class="my-1">
        <p class="small fw-semibold mb-2" style="color:var(--text-secondary);">Source — use either a table/view name OR a custom SQL query</p>
    </div>

    <div class="col-md-6">
        <label class="form-label">Table or View</label>
        <input type="text" name="table_or_view" class="form-control @error('table_or_view') is-invalid @enderror"
               value="{{ old('table_or_view', $source?->table_or_view) }}" placeholder="contacts">
        @error('table_or_view')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Custom SQL Query <span style="font-size:11px;color:var(--text-tertiary);">(overrides table above)</span></label>
        <textarea name="custom_query" rows="3" class="form-control font-monospace @error('custom_query') is-invalid @enderror"
                  placeholder="SELECT name, phone, email FROM bookings WHERE status = 'confirmed'">{{ old('custom_query', $source?->custom_query) }}</textarea>
        @error('custom_query')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <hr class="my-1">
        <p class="small fw-semibold mb-2" style="color:var(--text-secondary);">Column Mappings — which columns contain name, phone, email</p>
    </div>

    <div class="col-md-4">
        <label class="form-label">Name Column</label>
        <input type="text" name="col_name" class="form-control"
               value="{{ old('col_name', $source?->col_name) }}" placeholder="name">
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone Column</label>
        <input type="text" name="col_phone" class="form-control"
               value="{{ old('col_phone', $source?->col_phone) }}" placeholder="phone">
    </div>
    <div class="col-md-4">
        <label class="form-label">Email Column</label>
        <input type="text" name="col_email" class="form-control"
               value="{{ old('col_email', $source?->col_email) }}" placeholder="email">
    </div>
</div>

<script>
const PORT_DEFAULTS = { mysql: 3306, pgsql: 5432, sqlsrv: 1433 };
function updateDefaultPort() {
    const driver = document.getElementById('ds-driver')?.value;
    const portEl = document.getElementById('ds-port');
    if (portEl && PORT_DEFAULTS[driver]) portEl.value = PORT_DEFAULTS[driver];
}
</script>
