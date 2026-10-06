{{-- resources/views/rooms/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --rm-navy:#0f2342; --rm-teal:#0d9488; --rm-sky:#0ea5e9;
    --rm-border:#e2e8f0; --rm-muted:#64748b; --rm-radius:14px;
    --rm-shadow:0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}

/* ── Hero ─────────────────────────────────────────── */
.rm-hero {
    background: linear-gradient(135deg, var(--rm-navy) 0%, #1e4a7e 55%, var(--rm-teal) 100%);
    border-radius: var(--rm-radius); padding: 24px 28px; margin-bottom: 20px; color: #fff;
    display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;
}
.rm-hero h1 { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
.rm-hero p  { font-size: 13px; opacity: .75; margin: 0; }
.rm-hero .pills { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.rm-pill { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }

/* ── Stats strip ──────────────────────────────────── */
.rm-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
.rm-stat { background: #fff; border: 1px solid var(--rm-border); border-radius: var(--rm-radius); box-shadow: var(--rm-shadow); padding: 14px 16px; }
.rm-stat .v { font-size: 24px; font-weight: 700; color: var(--rm-navy); }
.rm-stat .l { font-size: 11px; color: var(--rm-muted); text-transform: uppercase; margin-top: 2px; }

/* ── Toolbar ──────────────────────────────────────── */
.rm-toolbar {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px;
    background: #fff; border: 1px solid var(--rm-border); border-radius: var(--rm-radius);
    padding: 12px 16px;
}
.rm-toolbar .search-wrap { position: relative; flex: 1; min-width: 220px; max-width: 340px; }
.rm-toolbar .search-wrap input { width: 100%; padding: 8px 12px 8px 34px; border: 1.5px solid var(--rm-border); border-radius: 8px; font-size: 13px; }
.rm-toolbar .search-wrap i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--rm-muted); }
.rm-toolbar select { border: 1.5px solid var(--rm-border); border-radius: 8px; padding: 6px 10px; font-size: 12.5px; min-width: 140px; }

.rm-view-toggle {
    display: inline-flex; border: 1.5px solid var(--rm-border); border-radius: 8px; overflow: hidden;
    margin-left: auto;
}
.rm-view-toggle button {
    background: #fff; border: none; padding: 6px 14px; font-size: 12.5px; font-weight: 600;
    color: var(--rm-muted); cursor: pointer; transition: all .15s;
}
.rm-view-toggle button.active { background: var(--rm-navy); color: #fff; }

/* ── Bulk bar ─────────────────────────────────────── */
.rm-bulk-bar {
    display: none; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px;
    padding: 10px 16px; margin-bottom: 14px; align-items: center; gap: 12px;
}
.rm-bulk-bar.is-active { display: flex; flex-wrap: wrap; }
.rm-bulk-bar .count { font-weight: 600; color: var(--rm-navy); }

/* ── Card grid ────────────────────────────────────── */
.rm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }

.rm-card {
    background: #fff; border: 1px solid var(--rm-border); border-radius: var(--rm-radius);
    box-shadow: var(--rm-shadow); padding: 16px; cursor: pointer; transition: all .15s ease;
    position: relative;
}
.rm-card:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,35,66,.1); border-color: var(--rm-sky); }
.rm-card.is-selected { border-color: var(--rm-sky); background: #F0F9FF; box-shadow: 0 0 0 3px rgba(14,165,233,.15); }

.rm-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
.rm-card-icon {
    width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.rm-card-icon.classroom   { background: #E0F2FE; color: #0369a1; }
.rm-card-icon.laboratory  { background: #EDE9FE; color: #6d28d9; }
.rm-card-icon.auditorium  { background: #FEF3C7; color: #b45309; }
.rm-card-icon.library     { background: #DCFCE7; color: #15803d; }
.rm-card-icon.sports      { background: #FEE2E2; color: #b91c1c; }
.rm-card-icon.other       { background: #E2E8F0; color: #475569; }

.rm-card-code { font-size: 11px; font-weight: 700; color: var(--rm-muted); font-family: monospace; }
.rm-card-title { font-size: 14px; font-weight: 700; color: var(--rm-navy); margin: 4px 0 0; word-break: break-word; }
.rm-card-sub { font-size: 11.5px; color: var(--rm-muted); }

.rm-card-meta { display: flex; gap: 8px; flex-wrap: wrap; margin: 10px 0; }
.rm-chip { font-size: 10.5px; padding: 2px 8px; border-radius: 6px; background: #F1F5F9; color: #334155; font-weight: 600; }
.rm-chip.capacity { background: #E0F2FE; color: #0369a1; }
.rm-chip.inactive { background: #FEE2E2; color: #b91c1c; }

.rm-card-facilities { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px; }
.rm-facility { font-size: 10px; padding: 2px 6px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 4px; color: #64748b; }

.rm-card-stats {
    display: flex; gap: 12px; padding-top: 10px; border-top: 1px dashed #E2E8F0;
    font-size: 11.5px; color: var(--rm-muted);
}
.rm-card-stats > div { display: flex; align-items: center; gap: 4px; cursor: pointer; padding: 2px 4px; border-radius: 6px; transition: all .15s; }
.rm-card-stats > div:hover { background: #F1F5F9; }
.rm-card-stats i { font-size: 13px; }
.rm-card-stats .has-value { color: var(--rm-navy); font-weight: 600; }

.rm-card-actions {
    position: absolute; top: 10px; right: 10px; display: flex; gap: 4px;
    opacity: 0; transition: opacity .15s; background: rgba(255,255,255,.9);
    padding: 4px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08);
}
.rm-card:hover .rm-card-actions { opacity: 1; }
.rm-card-actions button {
    background: #fff; border: 1px solid var(--rm-border); border-radius: 6px;
    width: 26px; height: 26px; display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--rm-muted); cursor: pointer; transition: all .12s;
}
.rm-card-actions button:hover { background: var(--rm-navy); color: #fff; border-color: var(--rm-navy); }
.rm-card-actions button.danger:hover { background: #DC2626; border-color: #DC2626; }

.rm-card-checkbox {
    position: absolute; top: 12px; left: 12px; z-index: 2;
    width: 18px; height: 18px; cursor: pointer;
}
.rm-card.has-checkbox { padding-left: 40px; }

/* ── Table view ───────────────────────────────────── */
.rm-table-wrap { background: #fff; border: 1px solid var(--rm-border); border-radius: var(--rm-radius); overflow: hidden; }
table.rm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
table.rm-table th { background: #F8FAFC; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; color: var(--rm-muted); border-bottom: 1px solid var(--rm-border); font-weight: 700; }
table.rm-table td { padding: 10px 12px; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
table.rm-table tr.rm-row:hover td { background: #F8FAFC; }
table.rm-table tr.is-selected td { background: #F0F9FF; }
table.rm-table .rm-actions-cell { text-align: right; white-space: nowrap; }
table.rm-table .rm-actions-cell button { background: none; border: none; padding: 2px 6px; cursor: pointer; color: var(--rm-muted); font-size: 15px; }
table.rm-table .rm-actions-cell button:hover { color: var(--rm-navy); }
table.rm-table .rm-actions-cell button.danger:hover { color: #DC2626; }

/* Expand chevron cell */
.rm-expand-cell { width: 32px; padding-right: 0 !important; text-align: center; }
.rm-expand-btn {
    background: none; border: none; color: var(--rm-muted); cursor: pointer;
    padding: 4px; border-radius: 6px; transition: all .15s; line-height: 1;
    width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center;
}
.rm-expand-btn:hover { background: #F1F5F9; color: var(--rm-navy); }
.rm-expand-btn i { transition: transform .2s ease; font-size: 16px; }
.rm-expand-btn.is-open i { transform: rotate(90deg); }

/* Stat pills in table cells */
.rm-stat-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; padding: 3px 9px; border-radius: 8px;
    background: #F1F5F9; color: #64748B; font-weight: 600;
    min-width: 34px; justify-content: center;
}
.rm-stat-pill.has-value { background: #EFF6FF; color: #1565C0; }
.rm-stat-pill.is-zero { background: #F8FAFC; color: #CBD5E1; }

/* Expanded detail sub-row */
tr.rm-detail-row td {
    background: #FAFBFC;
    padding: 0 !important;
    border-bottom: 1px solid var(--rm-border);
}
.rm-detail-wrap {
    padding: 16px 20px 20px 52px;
    animation: rmDetailSlide 0.2s ease;
}
@keyframes rmDetailSlide {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.rm-detail-cols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
@media (max-width: 900px) { .rm-detail-cols { grid-template-columns: 1fr; } }
.rm-detail-col h6 {
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;
    color: var(--rm-muted); font-weight: 700; margin: 0 0 10px;
    display: flex; align-items: center; gap: 6px;
}
.rm-detail-col h6 i { font-size: 14px; }
.rm-detail-list {
    list-style: none; margin: 0; padding: 0;
    background: #FFF; border: 1px solid var(--rm-border); border-radius: 8px;
    overflow: hidden;
}
.rm-detail-list li {
    padding: 8px 12px; font-size: 12.5px; color: #334155;
    border-bottom: 1px solid #F1F5F9; line-height: 1.4;
    display: flex; justify-content: space-between; align-items: baseline; gap: 8px;
}
.rm-detail-list li:last-child { border-bottom: none; }
.rm-detail-list li .main { flex: 1; min-width: 0; }
.rm-detail-list li .main strong { color: #0F172A; display: block; }
.rm-detail-list li .main em { font-style: normal; color: #64748B; font-size: 11.5px; }
.rm-detail-list li .meta { color: #94A3B8; font-size: 11px; white-space: nowrap; }
.rm-detail-empty { padding: 16px 12px; text-align: center; color: #94A3B8; font-size: 12.5px; }
.rm-detail-more {
    padding: 8px 12px; background: #F8FAFC; border-top: 1px solid var(--rm-border);
    font-size: 11.5px; color: #1565C0; font-weight: 600; cursor: pointer; text-align: center;
    transition: all .15s;
}
.rm-detail-more:hover { background: #EFF6FF; }

/* ── Empty state ──────────────────────────────────── */
.rm-empty {
    background: #fff; border: 2px dashed var(--rm-border); border-radius: var(--rm-radius);
    text-align: center; padding: 60px 20px;
}
.rm-empty i { font-size: 48px; color: #CBD5E1; margin-bottom: 16px; display: block; }
.rm-empty h5 { color: var(--rm-navy); margin-bottom: 8px; }
.rm-empty p { color: var(--rm-muted); margin-bottom: 20px; font-size: 13px; max-width: 420px; margin-left: auto; margin-right: auto; }

/* ── Detail drawer ────────────────────────────────── */
.rm-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15,35,66,.4); z-index: 1040;
    opacity: 0; pointer-events: none; transition: opacity .2s ease;
}
.rm-drawer-backdrop.is-open { opacity: 1; pointer-events: auto; }

.rm-drawer {
    position: fixed; top: 0; right: 0; height: 100vh; width: 460px; max-width: 100vw;
    background: #fff; z-index: 1050; box-shadow: -8px 0 30px rgba(15,35,66,.15);
    transform: translateX(100%); transition: transform .25s cubic-bezier(.4,0,.2,1);
    display: flex; flex-direction: column;
}
.rm-drawer.is-open { transform: translateX(0); }

.rm-drawer-header {
    background: linear-gradient(135deg, var(--rm-navy), var(--rm-teal));
    color: #fff; padding: 20px 24px; flex-shrink: 0;
}
.rm-drawer-header h4 { font-size: 17px; font-weight: 700; margin: 0 0 4px; }
.rm-drawer-header p { font-size: 12px; opacity: .8; margin: 0; }

.rm-drawer-body { flex: 1; overflow-y: auto; padding: 20px 24px; }

.rm-drawer-section { margin-bottom: 22px; }
.rm-drawer-section h6 { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--rm-muted); font-weight: 700; margin: 0 0 10px; }
.rm-drawer-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px; }
.rm-drawer-row:last-child { border-bottom: none; }
.rm-drawer-row .lbl { color: var(--rm-muted); }
.rm-drawer-row .val { font-weight: 600; color: var(--rm-navy); text-align: right; }

.rm-drawer-footer { padding: 16px 24px; border-top: 1px solid var(--rm-border); display: flex; gap: 8px; flex-wrap: wrap; flex-shrink: 0; }

/* Drawer mini stat strip (clickable) */
.rm-drawer-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
    margin-bottom: 20px;
}
.rm-drawer-stat {
    background: #F8FAFC; border: 1px solid var(--rm-border); border-radius: 10px;
    padding: 12px 8px; text-align: center; cursor: pointer; transition: all .15s;
}
.rm-drawer-stat:hover { border-color: var(--rm-sky); background: #F0F9FF; transform: translateY(-1px); }
.rm-drawer-stat .v { font-size: 20px; font-weight: 700; color: var(--rm-navy); }
.rm-drawer-stat .l { font-size: 10px; color: var(--rm-muted); text-transform: uppercase; margin-top: 3px; letter-spacing: 0.3px; }

/* ── Popover ──────────────────────────────────────── */
.popover { max-width: 340px; font-size: 12.5px; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="rm-hero">
        <div>
            <h1><i class="ri-door-line me-2"></i>{{ $pagetitle }}</h1>
            <p>Manage rooms, map them to classes, and track bookings.</p>
            <div class="pills">
                <span class="rm-pill"><i class="ri-building-line me-1"></i>{{ $rooms->total() }} rooms</span>
                <span class="rm-pill"><i class="ri-user-line me-1"></i>{{ $rooms->sum('capacity') }} total seats</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-light btn-sm" onclick="refreshRoomStats()">
                <i class="ri-refresh-line me-1"></i>Refresh
            </button>
            @can('Create rooms')
            <button class="btn btn-light btn-sm" onclick="resetRoomForm()" data-bs-toggle="modal" data-bs-target="#roomModal">
                <i class="ri-add-line me-1"></i>Add Room
            </button>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i><strong>Error!</strong>
            <ul class="mt-2 mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="rm-stats" id="rmStats">
        <div class="rm-stat"><div class="v" id="statTotal">{{ $rooms->total() }}</div><div class="l">Total Rooms</div></div>
        <div class="rm-stat"><div class="v" id="statClassrooms">{{ $rooms->where('type','classroom')->count() }}</div><div class="l">Classrooms</div></div>
        <div class="rm-stat"><div class="v" id="statLabs">{{ $rooms->where('type','laboratory')->count() }}</div><div class="l">Laboratories</div></div>
        <div class="rm-stat"><div class="v" id="statCapacity">{{ $rooms->sum('capacity') }}</div><div class="l">Total Capacity</div></div>
    </div>

    {{-- Toolbar --}}
    <div class="rm-toolbar">
        <div class="search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" id="rmSearch" placeholder="Search by code, name, or building…" oninput="rmDebouncedFilter()">
        </div>

        <select id="rmFilterType" onchange="rmApplyFilters()">
            <option value="">All Types</option>
            <option value="classroom">Classroom</option>
            <option value="laboratory">Laboratory</option>
            <option value="auditorium">Auditorium</option>
            <option value="library">Library</option>
            <option value="sports">Sports</option>
            <option value="other">Other</option>
        </select>

        <select id="rmFilterBuilding" onchange="rmApplyFilters()">
            <option value="">All Buildings</option>
        </select>

        <select id="rmFilterCapacity" onchange="rmApplyFilters()">
            <option value="">Any Capacity</option>
            <option value="0-20">0–20</option>
            <option value="21-40">21–40</option>
            <option value="41-60">41–60</option>
            <option value="61-100">61–100</option>
            <option value="101-9999">100+</option>
        </select>

        <select id="rmFilterStatus" onchange="rmApplyFilters()">
            <option value="">Any Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="mapped">Has Mappings</option>
            <option value="unmapped">No Mappings</option>
            <option value="booked">Has Upcoming Bookings</option>
        </select>

        <button class="btn btn-sm btn-outline-secondary" onclick="rmClearFilters()">
            <i class="ri-close-line me-1"></i>Clear
        </button>

        <div class="rm-view-toggle">
            <button class="active" data-view="grid" onclick="rmSetView('grid')">
                <i class="ri-grid-line"></i>
            </button>
            <button data-view="table" onclick="rmSetView('table')">
                <i class="ri-list-check"></i>
            </button>
        </div>
    </div>

    {{-- Bulk bar --}}
    <div class="rm-bulk-bar" id="rmBulkBar">
        <i class="ri-checkbox-multiple-line ri-lg text-primary"></i>
        <span class="count" id="rmBulkCount">0 selected</span>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-success" onclick="rmBulkActivate(true)">
                <i class="ri-checkbox-circle-line me-1"></i>Activate
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rmBulkActivate(false)">
                <i class="ri-close-circle-line me-1"></i>Deactivate
            </button>
            <button class="btn btn-sm btn-info text-white" onclick="openBulkMapModal()">
                <i class="ri-links-line me-1"></i>Map to Class
            </button>
            <button class="btn btn-sm btn-danger" onclick="rmBulkDestroy()">
                <i class="ri-delete-bin-line me-1"></i>Delete
            </button>
            <button class="btn btn-sm btn-light" onclick="rmClearSelection()">
                <i class="ri-close-line"></i>
            </button>
        </div>
    </div>

    {{-- Grid view --}}
    <div class="rm-grid" id="rmGrid">
        @forelse($rooms as $room)
            @php
                $typeIcons = [
                    'classroom'  => 'ri-school-line',
                    'laboratory' => 'ri-flask-line',
                    'auditorium' => 'ri-mic-line',
                    'library'    => 'ri-book-open-line',
                    'sports'     => 'ri-basketball-line',
                    'other'      => 'ri-building-line',
                ];
                $typeClass = $room->type ?? 'other';
                $facilities = $room->facilities ?? [];
            @endphp
            <div class="rm-card has-checkbox" data-room-id="{{ $room->id }}"
                 data-type="{{ $room->type }}"
                 data-building="{{ strtolower($room->building ?? '') }}"
                 data-capacity="{{ $room->capacity }}"
                 data-active="{{ $room->is_active ? '1' : '0' }}"
                 data-name="{{ strtolower($room->room_name) }}"
                 data-code="{{ strtolower($room->room_code) }}"
                 onclick="rmCardClick(event, {{ $room->id }})">

                <input type="checkbox" class="rm-card-checkbox"
                       onclick="event.stopPropagation(); rmToggleSelect({{ $room->id }}, this.checked)">

                <div class="rm-card-actions" onclick="event.stopPropagation()">
                    <button onclick="rmViewRoom({{ $room->id }})" title="View details"><i class="ri-eye-line"></i></button>
                    <button onclick="rmViewSchedule({{ $room->id }})" title="View schedule"><i class="ri-calendar-line"></i></button>
                    @can('Edit rooms')
                    <button onclick="rmEditRoom({{ $room->id }})" title="Edit"><i class="ri-edit-line"></i></button>
                    @endcan
                    @can('Manage room bookings')
                    <button onclick="rmBookRoom({{ $room->id }})" title="Book"><i class="ri-calendar-check-line"></i></button>
                    @endcan
                    @can('Delete rooms')
                    <button class="danger" onclick="rmDeleteRoom({{ $room->id }})" title="Delete"><i class="ri-delete-bin-line"></i></button>
                    @endcan
                </div>

                <div class="rm-card-head">
                    <div class="rm-card-icon {{ $typeClass }}">
                        <i class="{{ $typeIcons[$typeClass] ?? 'ri-building-line' }}"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div class="rm-card-code">{{ $room->room_code }}</div>
                        <div class="rm-card-title">{{ $room->room_name }}</div>
                        @if($room->building)
                            <div class="rm-card-sub"><i class="ri-map-pin-line"></i> {{ $room->building }}{{ $room->floor ? ' · ' . $room->floor : '' }}</div>
                        @endif
                    </div>
                </div>

                <div class="rm-card-meta">
                    <span class="rm-chip capacity"><i class="ri-group-line me-1"></i>{{ $room->capacity }} seats</span>
                    <span class="rm-chip">{{ ucfirst($room->type) }}</span>
                    @if(!$room->is_active)
                        <span class="rm-chip inactive">Inactive</span>
                    @endif
                </div>

                @if(!empty($facilities))
                    <div class="rm-card-facilities">
                        @foreach(array_slice($facilities, 0, 3) as $fac)
                            <span class="rm-facility">{{ $fac }}</span>
                        @endforeach
                        @if(count($facilities) > 3)
                            <span class="rm-facility">+{{ count($facilities) - 3 }}</span>
                        @endif
                    </div>
                @endif

                <div class="rm-card-stats" id="rmStats_{{ $room->id }}" onclick="event.stopPropagation()">
                    <div title="Upcoming bookings — click to view" onclick="rmOpenDrawerSection({{ $room->id }}, 'bookings')">
                        <i class="ri-calendar-event-line"></i><span class="stat-bookings">…</span>
                    </div>
                    <div title="Mapped classes — click to view" onclick="rmOpenDrawerSection({{ $room->id }}, 'mappings')">
                        <i class="ri-links-line"></i><span class="stat-mapped">…</span>
                    </div>
                    <div title="Timetable uses — click to view" onclick="rmOpenDrawerSection({{ $room->id }}, 'uses')">
                        <i class="ri-table-line"></i><span class="stat-uses">…</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="rm-empty" style="grid-column:1/-1">
                <i class="ri-door-open-line"></i>
                <h5>No rooms yet</h5>
                <p>Add your first room to start assigning venues to classes, managing bookings, and mapping rooms to subjects in the timetable wizard.</p>
                @can('Create rooms')
                <button class="btn btn-primary" onclick="resetRoomForm()" data-bs-toggle="modal" data-bs-target="#roomModal">
                    <i class="ri-add-line me-1"></i>Add First Room
                </button>
                @endcan
            </div>
        @endforelse
    </div>

    {{-- Table view (hidden by default) --}}
    <div class="rm-table-wrap" id="rmTable" style="display:none">
        <div style="overflow-x:auto">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th class="rm-expand-cell"></th>
                        <th style="width:32px"><input type="checkbox" id="rmSelectAllTable" onchange="rmToggleSelectAll(this.checked)"></th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Building</th>
                        <th style="text-align:center">Bookings</th>
                        <th style="text-align:center">Mapped</th>
                        <th style="text-align:center">Uses</th>
                        <th>Status</th>
                        <th style="width:180px;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rooms as $room)
                        <tr class="rm-row" data-room-id="{{ $room->id }}"
                            data-type="{{ $room->type }}"
                            data-building="{{ strtolower($room->building ?? '') }}"
                            data-capacity="{{ $room->capacity }}"
                            data-active="{{ $room->is_active ? '1' : '0' }}"
                            data-name="{{ strtolower($room->room_name) }}"
                            data-code="{{ strtolower($room->room_code) }}">
                            <td class="rm-expand-cell">
                                <button class="rm-expand-btn" onclick="rmToggleDetailRow({{ $room->id }}, this)">
                                    <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </td>
                            <td><input type="checkbox" class="rm-row-checkbox" onclick="rmToggleSelect({{ $room->id }}, this.checked)"></td>
                            <td><span class="badge bg-primary">{{ $room->room_code }}</span></td>
                            <td class="fw-semibold">{{ $room->room_name }}</td>
                            <td>{{ ucfirst($room->type) }}</td>
                            <td>{{ $room->capacity }}</td>
                            <td>{{ $room->building ?: '—' }}</td>
                            <td style="text-align:center">
                                <span class="rm-stat-pill is-zero" data-stat-type="bookings" data-room-id="{{ $room->id }}">…</span>
                            </td>
                            <td style="text-align:center">
                                <span class="rm-stat-pill is-zero" data-stat-type="mappings" data-room-id="{{ $room->id }}">…</span>
                            </td>
                            <td style="text-align:center">
                                <span class="rm-stat-pill is-zero" data-stat-type="uses" data-room-id="{{ $room->id }}">…</span>
                            </td>
                            <td>
                                @if($room->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="rm-actions-cell">
                                <button onclick="rmViewRoom({{ $room->id }})" title="View"><i class="ri-eye-line"></i></button>
                                <button onclick="rmViewSchedule({{ $room->id }})" title="Schedule"><i class="ri-calendar-line"></i></button>
                                @can('Edit rooms')
                                <button onclick="rmEditRoom({{ $room->id }})" title="Edit"><i class="ri-edit-line"></i></button>
                                @endcan
                                @can('Manage room bookings')
                                <button onclick="rmBookRoom({{ $room->id }})" title="Book"><i class="ri-calendar-check-line"></i></button>
                                @endcan
                                @can('Delete rooms')
                                <button class="danger" onclick="rmDeleteRoom({{ $room->id }})" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                @endcan
                            </td>
                        </tr>
                        <tr class="rm-detail-row" id="rmDetailRow_{{ $room->id }}" style="display:none">
                            <td colspan="12">
                                <div class="rm-detail-wrap" id="rmDetailWrap_{{ $room->id }}">
                                    <div class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm me-2"></div>Loading details…
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $rooms->links('pagination::bootstrap-5') }}
    </div>

</div>
</div>
</div>

{{-- DETAIL DRAWER --}}
<div class="rm-drawer-backdrop" id="rmDrawerBackdrop" onclick="rmCloseDrawer()"></div>
<div class="rm-drawer" id="rmDrawer">
    <div class="rm-drawer-header">
        <h4 id="rmDrawerTitle">Room</h4>
        <p id="rmDrawerSubtitle">—</p>
    </div>
    <div class="rm-drawer-body" id="rmDrawerBody">
        <div class="text-center py-5 text-muted">
            <div class="spinner-border text-primary"></div>
        </div>
    </div>
    <div class="rm-drawer-footer" id="rmDrawerFooter"></div>
</div>

{{-- ─────────────────────────────────────────────────────── --}}
{{-- MODALS                                                   --}}
{{-- ─────────────────────────────────────────────────────── --}}

{{-- Add/Edit Room Modal --}}
<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="roomModalTitle">Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="roomForm">
                    <input type="hidden" id="roomId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roomCode" placeholder="e.g., RM101, LAB01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roomName" placeholder="e.g., Room 101" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type</label>
                            <select class="form-select" id="roomType">
                                <option value="classroom">📚 Classroom</option>
                                <option value="laboratory">🔬 Laboratory</option>
                                <option value="auditorium">🎤 Auditorium</option>
                                <option value="library">📖 Library</option>
                                <option value="sports">⚽ Sports Facility</option>
                                <option value="other">🏢 Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="roomCapacity" value="30" min="1">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Building</label>
                            <input type="text" class="form-control" id="roomBuilding" placeholder="e.g., Main Building">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Floor</label>
                            <input type="text" class="form-control" id="roomFloor" placeholder="e.g., Ground Floor">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Facilities &amp; Equipment</label>
                        <select class="form-select" id="roomFacilities" multiple size="4">
                            <option value="Projector">📽️ Projector</option>
                            <option value="Smartboard">📱 Smartboard</option>
                            <option value="AC">❄️ Air Conditioner</option>
                            <option value="Whiteboard">📝 Whiteboard</option>
                            <option value="Computer">💻 Computer</option>
                            <option value="WiFi">📶 WiFi</option>
                            <option value="Microphone">🎤 Microphone / Sound System</option>
                            <option value="Lab Equipment">🧪 Laboratory Equipment</option>
                            <option value="Sports Equipment">🏀 Sports Equipment</option>
                            <option value="Library Books">📚 Library Books</option>
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="roomIsActive" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="roomNotes" rows="2" placeholder="Any special notes about this room..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="rmSaveRoom()">
                    <i class="ri-save-line me-2"></i>Save Room
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Mappings Modal --}}
<div class="modal fade" id="mappingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="ri-links-line me-2"></i>Mappings — <span id="mapModalRoomName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-start gap-2 mb-3" style="font-size:13px">
                    <i class="ri-information-line ri-lg mt-1"></i>
                    <div>
                        When you generate a timetable with <strong>Strict room mapping</strong>, a lesson can
                        only use a room mapped to its class. Leave <em>Subject</em> blank for "any subject in this class."
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                        <select class="form-select" id="mapClassId"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Subject <span class="text-muted fw-normal">(optional)</span></label>
                        <select class="form-select" id="mapSubjectId">
                            <option value="">— Any subject —</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                        <select class="form-select" id="mapSessionId"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
                        <select class="form-select" id="mapTermId">
                            <option value="">All terms</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Note <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" class="form-control" id="mapNote" maxlength="190" placeholder="e.g. Primary room">
                </div>

                <button class="btn btn-primary" onclick="rmSaveMapping()">
                    <i class="ri-add-line me-2"></i>Add Mapping
                </button>

                <hr class="my-4">

                <h6 class="mb-3">Existing mappings</h6>
                <div id="mappingsList"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Book Room Modal --}}
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">Book Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bookingForm">
                    <input type="hidden" id="bookingRoomId">
                    <div class="mb-3">
                        <label class="form-label">Room</label>
                        <input type="text" class="form-control" id="bookingRoomName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="bookingDate" min="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="bookingStartTime" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" id="bookingEndTime" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <textarea class="form-control" id="bookingPurpose" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recurring</label>
                        <select class="form-select" id="bookingRecurring">
                            <option value="none">One-time</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="rmSubmitBooking()">
                    <i class="ri-calendar-check-line me-2"></i>Confirm Booking
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Schedule Modal --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="scheduleModalTitle">Room Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="scheduleTable">
                        <thead class="table-dark">
                            <tr id="scheduleHeader"><th>Period / Time</th></tr>
                        </thead>
                        <tbody id="scheduleBody">
                            <tr><td colspan="6" class="text-center py-4">Loading schedule...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Map Modal --}}
<div class="modal fade" id="bulkMapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d9488, #0ea5e9);">
                <h5 class="modal-title text-white">
                    <i class="ri-links-line me-2"></i>Bulk Map Rooms to a Class
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:13px">
                    Adds a mapping row for each selected room to the class and subject below. Rooms already
                    mapped to the same target are skipped.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                    <select class="form-select" id="bulkMapClassId"></select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Subject <span class="text-muted fw-normal">(optional)</span>
                        <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                           data-bs-toggle="popover"
                           data-bs-title="Subject (optional)"
                           data-bs-content="Leave blank to say 'these rooms are available for any subject in this class.' Pick a subject to make the mapping subject-specific."></i>
                    </label>
                    <select class="form-select" id="bulkMapSubjectId">
                        <option value="">— Any subject —</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                        <select class="form-select" id="bulkMapSessionId"></select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
                        <select class="form-select" id="bulkMapTermId">
                            <option value="">All terms</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-info text-white" onclick="rmSubmitBulkMap()">
                    <i class="ri-links-line me-1"></i>Apply to <span id="bulkMapCount">0</span> rooms
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@include('partials.apple-alert')

<script>
const RM_CSRF = '{{ csrf_token() }}';

const RM_ROUTES = {
    index:           '{{ route("rooms.index") }}',
    show:            '{{ route("rooms.show", ["id" => "__ID__"]) }}',
    store:           '{{ route("rooms.store") }}',
    update:          '{{ route("rooms.update", ["id" => "__ID__"]) }}',
    destroy:         '{{ route("rooms.destroy", ["id" => "__ID__"]) }}',
    stats:           '{{ route("rooms.stats") }}',
    statsDetail:     '{{ route("rooms.stats-detail", ["roomId" => "__ID__"]) }}',
    mappingCounts:   '{{ route("rooms.mapping-counts") }}',
    mappings:        '{{ route("rooms.mappings", ["roomId" => "__ID__"]) }}',
    storeMapping:    '{{ route("rooms.mappings.store", ["roomId" => "__ID__"]) }}',
    destroyMapping:  '{{ route("rooms.mappings.destroy", ["mappingId" => "__ID__"]) }}',
    book:            '{{ route("rooms.book.room", ["roomId" => "__ID__"]) }}',
    checkAvail:      '{{ route("rooms.check-availability") }}',
    bulkActivate:    '{{ route("rooms.bulk.activate") }}',
    bulkDestroy:     '{{ route("rooms.bulk.destroy") }}',
    bulkMap:         '{{ route("rooms.bulk.map") }}',
    classesList:     '{{ route("api.classes-list") }}',
    subjectsList:    '{{ route("api.subjects-list") }}',
    sessionsList:    '{{ route("api.sessions-list") }}',
    termsList:       '{{ route("api.terms-list") }}',
};

function rmUrl(template, id) {
    return template.replace('__ID__', id);
}

function rmEsc(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

/* ── Selection state ─────────────────────────────────── */
const rmSelected = new Set();

function rmToggleSelect(id, checked) {
    if (checked) rmSelected.add(id);
    else rmSelected.delete(id);
    rmSyncSelectionUI();
}

function rmToggleSelectAll(checked) {
    document.querySelectorAll('.rm-row-checkbox').forEach(cb => {
        cb.checked = checked;
        const row = cb.closest('tr');
        if (row) {
            const id = parseInt(row.dataset.roomId);
            if (checked) rmSelected.add(id);
            else rmSelected.delete(id);
        }
    });
    document.querySelectorAll('.rm-card').forEach(card => {
        const cb = card.querySelector('.rm-card-checkbox');
        if (cb) cb.checked = checked;
        if (checked) rmSelected.add(parseInt(card.dataset.roomId));
        else rmSelected.delete(parseInt(card.dataset.roomId));
    });
    rmSyncSelectionUI();
}

function rmClearSelection() {
    rmSelected.clear();
    document.querySelectorAll('.rm-card-checkbox').forEach(cb => cb.checked = false);
    document.querySelectorAll('.rm-row-checkbox').forEach(cb => cb.checked = false);
    const selAll = document.getElementById('rmSelectAllTable');
    if (selAll) selAll.checked = false;
    rmSyncSelectionUI();
}

function rmSyncSelectionUI() {
    const count = rmSelected.size;
    const bar = document.getElementById('rmBulkBar');
    document.getElementById('rmBulkCount').textContent = count + ' selected';
    bar.classList.toggle('is-active', count > 0);
    document.getElementById('bulkMapCount').textContent = count;

    document.querySelectorAll('.rm-card').forEach(card => {
        card.classList.toggle('is-selected', rmSelected.has(parseInt(card.dataset.roomId)));
    });
    document.querySelectorAll('table.rm-table tbody tr.rm-row').forEach(row => {
        row.classList.toggle('is-selected', rmSelected.has(parseInt(row.dataset.roomId)));
    });
}

/* ── View toggle ─────────────────────────────────────── */
function rmSetView(view) {
    document.querySelectorAll('.rm-view-toggle button').forEach(b => {
        b.classList.toggle('active', b.dataset.view === view);
    });
    document.getElementById('rmGrid').style.display = view === 'grid' ? '' : 'none';
    document.getElementById('rmTable').style.display = view === 'table' ? '' : 'none';
    localStorage.setItem('rm_view', view);
}

/* ── Filters ─────────────────────────────────────────── */
let rmFilterTimer = null;
function rmDebouncedFilter() {
    clearTimeout(rmFilterTimer);
    rmFilterTimer = setTimeout(rmApplyFilters, 200);
}

function rmApplyFilters() {
    const q        = (document.getElementById('rmSearch').value || '').toLowerCase().trim();
    const type     = document.getElementById('rmFilterType').value;
    const building = document.getElementById('rmFilterBuilding').value;
    const capacity = document.getElementById('rmFilterCapacity').value;
    const status   = document.getElementById('rmFilterStatus').value;

    function rowMatches(el) {
        const elType     = el.dataset.type;
        const elBuilding = el.dataset.building;
        const elCapacity = parseInt(el.dataset.capacity);
        const elActive   = el.dataset.active === '1';
        const elName     = el.dataset.name;
        const elCode     = el.dataset.code;

        if (q && !(elName.includes(q) || elCode.includes(q) || elBuilding.includes(q))) return false;
        if (type && elType !== type) return false;
        if (building && elBuilding !== building) return false;

        if (capacity) {
            const [min, max] = capacity.split('-').map(Number);
            if (elCapacity < min || elCapacity > max) return false;
        }

        if (status === 'active' && !elActive) return false;
        if (status === 'inactive' && elActive) return false;

        return true;
    }

    document.querySelectorAll('.rm-card').forEach(card => {
        card.style.display = rowMatches(card) ? '' : 'none';
    });
    document.querySelectorAll('table.rm-table tbody tr.rm-row').forEach(row => {
        const visible = rowMatches(row);
        row.style.display = visible ? '' : 'none';
        // Also hide the paired detail row if its parent is hidden.
        const detailRow = document.getElementById('rmDetailRow_' + row.dataset.roomId);
        if (detailRow && !visible) detailRow.style.display = 'none';
    });
}

function rmClearFilters() {
    document.getElementById('rmSearch').value = '';
    document.getElementById('rmFilterType').value = '';
    document.getElementById('rmFilterBuilding').value = '';
    document.getElementById('rmFilterCapacity').value = '';
    document.getElementById('rmFilterStatus').value = '';
    rmApplyFilters();
}

/* ── Populate building filter ────────────────────────── */
function rmPopulateBuildingFilter() {
    const set = new Set();
    document.querySelectorAll('.rm-card').forEach(card => {
        const b = card.dataset.building;
        if (b) set.add(b);
    });
    const sel = document.getElementById('rmFilterBuilding');
    [...set].sort().forEach(b => {
        const opt = document.createElement('option');
        opt.value = b;
        opt.textContent = b.charAt(0).toUpperCase() + b.slice(1);
        sel.appendChild(opt);
    });
}

/* ── Stats strip (batch) ─────────────────────────────── */
// Cache so we can render the same totals in both card and table views
// without a second request.
let rmStatsCache = {};

async function refreshRoomStats() {
    try {
        const res  = await fetch(RM_ROUTES.stats, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) return;

        rmStatsCache = data.stats;

        Object.entries(data.stats).forEach(([roomId, s]) => {
            // Grid card stats
            const wrap = document.getElementById('rmStats_' + roomId);
            if (wrap) {
                const b = wrap.querySelector('.stat-bookings');
                const m = wrap.querySelector('.stat-mapped');
                const u = wrap.querySelector('.stat-uses');
                if (b) { b.textContent = s.upcoming_bookings; b.classList.toggle('has-value', s.upcoming_bookings > 0); }
                if (m) { m.textContent = s.mapped_classes;    m.classList.toggle('has-value', s.mapped_classes > 0); }
                if (u) { u.textContent = s.timetable_uses;    u.classList.toggle('has-value', s.timetable_uses > 0); }
            }

            // Table stat pills
            const pillB = document.querySelector(`.rm-stat-pill[data-stat-type="bookings"][data-room-id="${roomId}"]`);
            const pillM = document.querySelector(`.rm-stat-pill[data-stat-type="mappings"][data-room-id="${roomId}"]`);
            const pillU = document.querySelector(`.rm-stat-pill[data-stat-type="uses"][data-room-id="${roomId}"]`);
            if (pillB) { pillB.textContent = s.upcoming_bookings; pillB.classList.toggle('has-value', s.upcoming_bookings > 0); pillB.classList.toggle('is-zero', s.upcoming_bookings === 0); }
            if (pillM) { pillM.textContent = s.mapped_classes;    pillM.classList.toggle('has-value', s.mapped_classes > 0);    pillM.classList.toggle('is-zero', s.mapped_classes === 0); }
            if (pillU) { pillU.textContent = s.timetable_uses;    pillU.classList.toggle('has-value', s.timetable_uses > 0);    pillU.classList.toggle('is-zero', s.timetable_uses === 0); }
        });
    } catch (e) { /* silent */ }
}

/* ── Detail row expansion (table view) ───────────────── */
const rmDetailState = {};   // roomId -> { loaded: bool, open: bool, data: {} }

async function rmToggleDetailRow(roomId, btn) {
    const row    = document.getElementById('rmDetailRow_' + roomId);
    const wrap   = document.getElementById('rmDetailWrap_' + roomId);
    if (!row || !wrap) return;

    const isOpen = row.style.display !== 'none';

    if (isOpen) {
        row.style.display = 'none';
        btn.classList.remove('is-open');
        return;
    }

    row.style.display = '';
    btn.classList.add('is-open');

    if (rmDetailState[roomId]?.loaded) {
        rmRenderDetailInto(wrap, rmDetailState[roomId].data, roomId);
        return;
    }

    wrap.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading details…</div>';

    try {
        const res  = await fetch(rmUrl(RM_ROUTES.statsDetail, roomId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed.');

        rmDetailState[roomId] = { loaded: true, open: true, data };
        rmRenderDetailInto(wrap, data, roomId);
    } catch (e) {
        wrap.innerHTML = `<div class="alert alert-danger m-0">Failed: ${rmEsc(e.message)}</div>`;
    }
}

/* Cap each list at 10 initially; "Show all N" expands in place. */
const RM_DETAIL_CAP = 10;
const rmDetailExpanded = {};   // "roomId:section" -> bool

function rmRenderDetailInto(wrap, data, roomId) {
    const bookingSection  = rmRenderBookingList(data.bookings, data.totals.bookings, roomId, 'bookings');
    const mappingSection  = rmRenderMappingList(data.mappings, data.totals.mappings, roomId, 'mappings');
    const useSection      = rmRenderUseList(data.uses, data.totals.uses, roomId, 'uses');

    wrap.innerHTML = `
        <div class="rm-detail-cols">
            <div class="rm-detail-col">
                <h6><i class="ri-calendar-event-line"></i>Upcoming bookings</h6>
                ${bookingSection}
            </div>
            <div class="rm-detail-col">
                <h6><i class="ri-links-line"></i>Mapped to classes</h6>
                ${mappingSection}
            </div>
            <div class="rm-detail-col">
                <h6><i class="ri-table-line"></i>Used in timetables</h6>
                ${useSection}
            </div>
        </div>`;
}

function rmRenderBookingList(bookings, total, roomId, section) {
    if (!bookings.length) {
        return '<div class="rm-detail-list"><div class="rm-detail-empty">No upcoming bookings.</div></div>';
    }
    const key = roomId + ':' + section;
    const expanded = !!rmDetailExpanded[key];
    const list = expanded ? bookings : bookings.slice(0, RM_DETAIL_CAP);

    let html = '<ul class="rm-detail-list">';
    list.forEach(b => {
        html += `<li>
            <div class="main">
                <strong>${rmEsc(b.date)}</strong>
                <em>${rmEsc(b.start_time)} – ${rmEsc(b.end_time)}</em>
            </div>
            <div class="meta">${rmEsc(b.purpose || '—')}</div>
        </li>`;
    });
    html += '</ul>';

    if (total > RM_DETAIL_CAP) {
        const label = expanded ? 'Show less' : `Show all ${total}`;
        html += `<div class="rm-detail-more" onclick="rmToggleDetailExpand(${roomId}, '${section}', this)">${label}</div>`;
    }
    return html;
}

function rmRenderMappingList(mappings, total, roomId, section) {
    if (!mappings.length) {
        return '<div class="rm-detail-list"><div class="rm-detail-empty">Not mapped to any class yet.</div></div>';
    }
    const key = roomId + ':' + section;
    const expanded = !!rmDetailExpanded[key];
    const list = expanded ? mappings : mappings.slice(0, RM_DETAIL_CAP);

    let html = '<ul class="rm-detail-list">';
    list.forEach(m => {
        const subj = m.subject_name ? rmEsc(m.subject_name) : '<em style="color:#94A3B8">Any subject</em>';
        const meta = [m.session_name, m.term_name].filter(Boolean).join(' · ');
        html += `<li>
            <div class="main">
                <strong>${rmEsc(m.class_name)}</strong>
                <em>${subj}</em>
            </div>
            ${meta ? `<div class="meta">${rmEsc(meta)}</div>` : ''}
        </li>`;
    });
    html += '</ul>';

    if (total > RM_DETAIL_CAP) {
        const label = expanded ? 'Show less' : `Show all ${total}`;
        html += `<div class="rm-detail-more" onclick="rmToggleDetailExpand(${roomId}, '${section}', this)">${label}</div>`;
    }
    return html;
}

function rmRenderUseList(uses, total, roomId, section) {
    if (!uses.length) {
        return '<div class="rm-detail-list"><div class="rm-detail-empty">Not used in any timetable.</div></div>';
    }
    const key = roomId + ':' + section;
    const expanded = !!rmDetailExpanded[key];
    const list = expanded ? uses : uses.slice(0, RM_DETAIL_CAP);

    let html = '<ul class="rm-detail-list">';
    list.forEach(u => {
        const subject = u.subject_name ? rmEsc(u.subject_name) : '—';
        const teacher = u.teacher_name ? ' · ' + rmEsc(u.teacher_name) : '';
        html += `<li>
            <div class="main">
                <strong>${rmEsc(u.day)} · ${rmEsc(u.period_name || '')}</strong>
                <em>${subject}${teacher} — ${rmEsc(u.class_name)}</em>
            </div>
            <div class="meta">${rmEsc(u.period_time || '')}</div>
        </li>`;
    });
    html += '</ul>';

    if (total > RM_DETAIL_CAP) {
        const label = expanded ? 'Show less' : `Show all ${total}`;
        html += `<div class="rm-detail-more" onclick="rmToggleDetailExpand(${roomId}, '${section}', this)">${label}</div>`;
    }
    return html;
}

function rmToggleDetailExpand(roomId, section, el) {
    const key = roomId + ':' + section;
    rmDetailExpanded[key] = !rmDetailExpanded[key];

    const state = rmDetailState[roomId];
    if (state?.data) {
        const wrap = document.getElementById('rmDetailWrap_' + roomId);
        if (wrap) rmRenderDetailInto(wrap, state.data, roomId);
    }
}

/* ── Card click / drawer ─────────────────────────────── */
function rmCardClick(event, roomId) {
    if (event.target.closest('.rm-card-actions') || event.target.closest('.rm-card-checkbox')) return;
    if (event.target.closest('.rm-card-stats')) return;   // stat clicks handled separately
    rmViewRoom(roomId);
}

function rmOpenDrawer(title, subtitle, bodyHtml, footerHtml) {
    document.getElementById('rmDrawerTitle').textContent = title;
    document.getElementById('rmDrawerSubtitle').textContent = subtitle;
    document.getElementById('rmDrawerBody').innerHTML = bodyHtml;
    document.getElementById('rmDrawerFooter').innerHTML = footerHtml || '';
    document.getElementById('rmDrawer').classList.add('is-open');
    document.getElementById('rmDrawerBackdrop').classList.add('is-open');
}

function rmCloseDrawer() {
    document.getElementById('rmDrawer').classList.remove('is-open');
    document.getElementById('rmDrawerBackdrop').classList.remove('is-open');
}

/* Open the drawer directly on the stats detail (bookings/mappings/uses). */
async function rmOpenDrawerSection(roomId, section) {
    rmOpenDrawer('Loading…', '', '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary"></div></div>');
    try {
        const res  = await fetch(rmUrl(RM_ROUTES.statsDetail, roomId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Not found.');

        rmDetailState[roomId] = { loaded: true, open: true, data };

        const body = `
            <div class="rm-drawer-section">
                <h6>Overview</h6>
                <div class="rm-drawer-row"><span class="lbl">Code</span><span class="val">${rmEsc(data.room.room_code)}</span></div>
                <div class="rm-drawer-row"><span class="lbl">Name</span><span class="val">${rmEsc(data.room.room_name)}</span></div>
            </div>
            <div class="rm-drawer-stats">
                <div class="rm-drawer-stat" onclick="rmDrawerScrollTo('rmDrawerSection_bookings')">
                    <div class="v">${data.totals.bookings}</div><div class="l">Bookings</div>
                </div>
                <div class="rm-drawer-stat" onclick="rmDrawerScrollTo('rmDrawerSection_mappings')">
                    <div class="v">${data.totals.mappings}</div><div class="l">Mapped</div>
                </div>
                <div class="rm-drawer-stat" onclick="rmDrawerScrollTo('rmDrawerSection_uses')">
                    <div class="v">${data.totals.uses}</div><div class="l">Uses</div>
                </div>
            </div>
            <div class="rm-drawer-section" id="rmDrawerSection_bookings">
                <h6><i class="ri-calendar-event-line me-1"></i>Upcoming bookings</h6>
                ${rmRenderBookingList(data.bookings, data.totals.bookings, roomId, 'bookings')}
            </div>
            <div class="rm-drawer-section" id="rmDrawerSection_mappings">
                <h6><i class="ri-links-line me-1"></i>Mapped to classes</h6>
                ${rmRenderMappingList(data.mappings, data.totals.mappings, roomId, 'mappings')}
            </div>
            <div class="rm-drawer-section" id="rmDrawerSection_uses">
                <h6><i class="ri-table-line me-1"></i>Used in timetables</h6>
                ${rmRenderUseList(data.uses, data.totals.uses, roomId, 'uses')}
            </div>`;

        rmOpenDrawer(data.room.room_name, data.room.room_code, body, '');
        // Auto-focus the requested section
        setTimeout(() => rmDrawerScrollTo('rmDrawerSection_' + section), 50);
    } catch (e) {
        rmOpenDrawer('Error', '', `<div class="alert alert-danger">${rmEsc(e.message)}</div>`);
    }
}

function rmDrawerScrollTo(elementId) {
    const el = document.getElementById(elementId);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function rmViewRoom(roomId) {
    rmOpenDrawer('Loading…', '', '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary"></div></div>');
    try {
        const res  = await fetch(rmUrl(RM_ROUTES.show, roomId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Not found');
        const room = data.room;

        // Pull the cached totals if available.
        const stats = rmStatsCache[roomId] || { upcoming_bookings: 0, mapped_classes: 0, timetable_uses: 0 };

        const facilitiesHtml = (room.facilities && room.facilities.length)
            ? room.facilities.map(f => `<span class="rm-chip">${rmEsc(f)}</span>`).join(' ')
            : '<span class="text-muted">None listed</span>';

        const body = `
            <div class="rm-drawer-section">
                <h6>Overview</h6>
                <div class="rm-drawer-row"><span class="lbl">Code</span><span class="val">${rmEsc(room.room_code)}</span></div>
                <div class="rm-drawer-row"><span class="lbl">Type</span><span class="val">${rmEsc(room.type)}</span></div>
                <div class="rm-drawer-row"><span class="lbl">Capacity</span><span class="val">${room.capacity} seats</span></div>
                <div class="rm-drawer-row"><span class="lbl">Building</span><span class="val">${rmEsc(room.building || '—')}</span></div>
                <div class="rm-drawer-row"><span class="lbl">Floor</span><span class="val">${rmEsc(room.floor || '—')}</span></div>
                <div class="rm-drawer-row"><span class="lbl">Status</span><span class="val">${room.is_active ? 'Active' : 'Inactive'}</span></div>
            </div>
            <div class="rm-drawer-stats">
                <div class="rm-drawer-stat" onclick="rmCloseDrawer();rmOpenDrawerSection(${roomId}, 'bookings')">
                    <div class="v">${stats.upcoming_bookings}</div><div class="l">Bookings</div>
                </div>
                <div class="rm-drawer-stat" onclick="rmCloseDrawer();rmOpenDrawerSection(${roomId}, 'mappings')">
                    <div class="v">${stats.mapped_classes}</div><div class="l">Mapped</div>
                </div>
                <div class="rm-drawer-stat" onclick="rmCloseDrawer();rmOpenDrawerSection(${roomId}, 'uses')">
                    <div class="v">${stats.timetable_uses}</div><div class="l">Uses</div>
                </div>
            </div>
            <div class="rm-drawer-section">
                <h6>Facilities</h6>
                <div>${facilitiesHtml}</div>
            </div>
            ${room.notes ? `
            <div class="rm-drawer-section">
                <h6>Notes</h6>
                <div style="font-size:13px;color:#475569">${rmEsc(room.notes)}</div>
            </div>` : ''}
        `;

        const footer = `
            <button class="btn btn-sm btn-outline-primary" onclick="rmCloseDrawer();rmEditRoom(${room.id})">
                <i class="ri-edit-line me-1"></i>Edit
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="rmCloseDrawer();rmManageMappings(${room.id}, '${rmEsc(room.room_name).replace(/'/g, "\\'")}')">
                <i class="ri-links-line me-1"></i>Mappings
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="rmCloseDrawer();rmViewSchedule(${room.id})">
                <i class="ri-calendar-line me-1"></i>Schedule
            </button>
            <button class="btn btn-sm btn-primary ms-auto" onclick="rmCloseDrawer();rmBookRoom(${room.id})">
                <i class="ri-calendar-check-line me-1"></i>Book
            </button>
        `;

        rmOpenDrawer(room.room_name, room.room_code + ' · ' + (room.building || 'No building'), body, footer);
    } catch (e) {
        rmOpenDrawer('Error', '', `<div class="alert alert-danger">${rmEsc(e.message)}</div>`);
    }
}

/* ── Add / Edit Room ─────────────────────────────────── */
function resetRoomForm() {
    document.getElementById('roomId').value = '';
    document.getElementById('roomCode').value = '';
    document.getElementById('roomName').value = '';
    document.getElementById('roomType').value = 'classroom';
    document.getElementById('roomCapacity').value = '30';
    document.getElementById('roomBuilding').value = '';
    document.getElementById('roomFloor').value = '';
    document.getElementById('roomIsActive').checked = true;
    document.getElementById('roomNotes').value = '';

    const sel = document.getElementById('roomFacilities');
    for (let i = 0; i < sel.options.length; i++) sel.options[i].selected = false;

    document.getElementById('roomModalTitle').innerText = 'Add New Room';
}

async function rmEditRoom(roomId) {
    try {
        const res  = await fetch(rmUrl(RM_ROUTES.show, roomId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Not found');

        const room = data.room;
        document.getElementById('roomId').value = room.id;
        document.getElementById('roomCode').value = room.room_code;
        document.getElementById('roomName').value = room.room_name;
        document.getElementById('roomType').value = room.type;
        document.getElementById('roomCapacity').value = room.capacity;
        document.getElementById('roomBuilding').value = room.building || '';
        document.getElementById('roomFloor').value = room.floor || '';
        document.getElementById('roomIsActive').checked = room.is_active;
        document.getElementById('roomNotes').value = room.notes || '';

        if (room.facilities) {
            const options = document.getElementById('roomFacilities').options;
            for (let i = 0; i < options.length; i++) {
                options[i].selected = room.facilities.includes(options[i].value);
            }
        }

        document.getElementById('roomModalTitle').innerText = 'Edit Room';
        new bootstrap.Modal(document.getElementById('roomModal')).show();
    } catch (e) {
        AppleAlert.error('Could not load room', e.message);
    }
}

async function rmSaveRoom() {
    const id = document.getElementById('roomId').value;
    const facilities = Array.from(document.getElementById('roomFacilities').selectedOptions).map(o => o.value);

    const data = {
        room_code:  document.getElementById('roomCode').value,
        room_name:  document.getElementById('roomName').value,
        type:       document.getElementById('roomType').value,
        capacity:   parseInt(document.getElementById('roomCapacity').value),
        building:   document.getElementById('roomBuilding').value,
        floor:      document.getElementById('roomFloor').value,
        facilities: facilities,
        is_active:  document.getElementById('roomIsActive').checked,
        notes:      document.getElementById('roomNotes').value,
    };

    if (!data.room_code || !data.room_name) {
        return AppleAlert.warning('Missing fields', 'Room code and name are required.');
    }

    const url = id ? rmUrl(RM_ROUTES.update, id) : RM_ROUTES.store;

    AppleAlert.loading('Saving room…');
    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  RM_CSRF,
                'Accept':        'application/json',
            },
            body: JSON.stringify(data),
        });
        const result = await res.json();
        AppleAlert.close();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
            AppleAlert.saved(id ? 'Room updated' : 'Room created');
            setTimeout(() => location.reload(), 900);
        } else {
            AppleAlert.error('Save failed', result.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.close();
        AppleAlert.error('Save failed', e.message);
    }
}

/* ── Delete ──────────────────────────────────────────── */
async function rmDeleteRoom(roomId) {
    const ok = await AppleAlert.confirmDelete(
        'Delete this room?',
        'This action can\'t be undone. The room must not be in use.'
    );
    if (!ok) return;

    AppleAlert.loading('Deleting…');
    try {
        const res  = await fetch(rmUrl(RM_ROUTES.destroy, roomId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': RM_CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        AppleAlert.close();
        if (data.success) {
            AppleAlert.deleted('Room deleted');
            setTimeout(() => location.reload(), 900);
        } else {
            AppleAlert.error('Could not delete', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.close();
        AppleAlert.error('Could not delete', e.message);
    }
}

/* ── Book room ───────────────────────────────────────── */
async function rmBookRoom(roomId) {
    try {
        const res  = await fetch(rmUrl(RM_ROUTES.show, roomId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Not found');

        document.getElementById('bookingRoomId').value = roomId;
        document.getElementById('bookingRoomName').value = data.room.room_name;
        document.getElementById('bookingDate').value = '';
        document.getElementById('bookingStartTime').value = '';
        document.getElementById('bookingEndTime').value = '';
        document.getElementById('bookingPurpose').value = '';
        document.getElementById('bookingRecurring').value = 'none';

        new bootstrap.Modal(document.getElementById('bookingModal')).show();
    } catch (e) {
        AppleAlert.error('Could not open booking form', e.message);
    }
}

async function rmSubmitBooking() {
    const roomId = document.getElementById('bookingRoomId').value;
    const payload = {
        date:            document.getElementById('bookingDate').value,
        start_time:      document.getElementById('bookingStartTime').value,
        end_time:        document.getElementById('bookingEndTime').value,
        purpose:         document.getElementById('bookingPurpose').value,
        recurring_type:  document.getElementById('bookingRecurring').value,
    };

    if (!payload.date || !payload.start_time || !payload.end_time || !payload.purpose) {
        return AppleAlert.warning('Missing fields', 'Please fill in date, times, and purpose.');
    }

    AppleAlert.loading('Checking availability…');
    try {
        const params = new URLSearchParams({
            room_id:    roomId,
            date:       payload.date,
            start_time: payload.start_time,
            end_time:   payload.end_time,
        });

        const availRes  = await fetch(`${RM_ROUTES.checkAvail}?${params}`, { headers: { 'Accept': 'application/json' } });
        const availData = await availRes.json();

        if (!availData.available) {
            AppleAlert.close();
            return AppleAlert.warning('Not available', 'This room is already booked for that time slot.');
        }

        const res = await fetch(rmUrl(RM_ROUTES.book, roomId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': RM_CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        AppleAlert.close();

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
            AppleAlert.saved('Room booked');
            refreshRoomStats();
            // If the drawer for this room is currently open, refresh it.
            if (document.getElementById('rmDrawer').classList.contains('is-open')) {
                rmOpenDrawerSection(parseInt(roomId), 'bookings');
            }
        } else {
            AppleAlert.error('Booking failed', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.close();
        AppleAlert.error('Booking failed', e.message);
    }
}

/* ── Schedule ────────────────────────────────────────── */
async function rmViewSchedule(roomId) {
    try {
        const res  = await fetch(rmUrl(RM_ROUTES.show, roomId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Not found');

        const room = data.room;
        const bookings = data.current_bookings || [];

        document.getElementById('scheduleModalTitle').innerHTML =
            `<i class="ri-door-line me-2"></i>${rmEsc(room.room_name)} — Weekly Schedule`;

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        document.getElementById('scheduleHeader').innerHTML =
            '<th>Period / Time</th>' + days.map(d => `<th class="text-center">${d}</th>`).join('');

        const periods = ['Period 1 (7:30-8:10)', 'Period 2 (8:10-8:50)', 'Period 3 (9:10-9:50)',
                         'Period 4 (9:50-10:30)', 'Period 5 (11:10-11:50)', 'Period 6 (11:50-12:30)'];

        let bodyHtml = '';
        periods.forEach(p => {
            bodyHtml += `<tr><td class="fw-semibold">${p}</td>`;
            days.forEach(d => {
                const b = bookings.find(x => x.day === d && x.period?.name === p);
                if (b) {
                    bodyHtml += `<td class="text-center">
                        <span class="fw-semibold">${rmEsc(b.subject?.subject || 'Booked')}</span><br>
                        <small class="text-muted">${rmEsc(b.teacher?.name || '')}</small>
                    </td>`;
                } else {
                    bodyHtml += `<td class="text-center text-muted">—</td>`;
                }
            });
            bodyHtml += '</tr>';
        });
        document.getElementById('scheduleBody').innerHTML = bodyHtml;

        new bootstrap.Modal(document.getElementById('scheduleModal')).show();
    } catch (e) {
        AppleAlert.error('Could not load schedule', e.message);
    }
}

/* ── Mappings ────────────────────────────────────────── */
let rmMappingRoomId = null;

async function rmManageMappings(roomId, roomName) {
    rmMappingRoomId = roomId;
    document.getElementById('mapModalRoomName').textContent = roomName;

    Promise.all([
        fetch(RM_ROUTES.classesList).then(r => r.json()).catch(() => ({ data: [] })),
        fetch(RM_ROUTES.subjectsList).then(r => r.json()).catch(() => ({ data: [] })),
        fetch(RM_ROUTES.sessionsList).then(r => r.json()).catch(() => ({ data: [] })),
        fetch(RM_ROUTES.termsList).then(r => r.json()).catch(() => ({ data: [] })),
    ]).then(([classes, subjects, sessions, terms]) => {
        rmFillSelect('mapClassId',   classes.data  ?? [], 'id', 'label',   '— Select class —');
        rmFillSelect('mapSubjectId', subjects.data ?? [], 'id', 'subject', null);
        rmFillSelect('mapSessionId', sessions.data ?? [], 'id', 'session', '— Select session —');
        rmFillSelect('mapTermId',    terms.data    ?? [], 'id', 'term',    null);
    });

    rmLoadMappings(roomId);
    new bootstrap.Modal(document.getElementById('mappingsModal')).show();
}

function rmFillSelect(id, items, valueKey, labelKey, placeholder) {
    const el = document.getElementById(id);
    el.innerHTML = '';
    if (placeholder) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        el.appendChild(opt);
    }
    items.forEach(it => {
        const opt = document.createElement('option');
        opt.value = it[valueKey];
        opt.textContent = it[labelKey] ?? '';
        el.appendChild(opt);
    });
}

function rmLoadMappings(roomId) {
    const wrap = document.getElementById('mappingsList');
    wrap.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>';

    fetch(rmUrl(RM_ROUTES.mappings, roomId), { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            if (!d.success || !d.mappings.length) {
                wrap.innerHTML = '<div class="text-center py-4 text-muted"><i class="ri-information-line ri-2x d-block mb-2 opacity-30"></i>No mappings yet.</div>';
                return;
            }
            let html = `<div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Class</th><th>Subject</th><th>Session</th><th>Term</th><th>Note</th><th style="width:50px"></th>
                </tr></thead><tbody>`;
            d.mappings.forEach(m => {
                html += `<tr>
                    <td>${rmEsc(m.class_name || '—')}</td>
                    <td>${m.subject_name ? rmEsc(m.subject_name) : '<em class="text-muted">Any subject</em>'}</td>
                    <td>${rmEsc(m.session_name || '—')}</td>
                    <td>${m.term_name ? rmEsc(m.term_name) : '<em class="text-muted">All</em>'}</td>
                    <td>${m.note ? rmEsc(m.note) : '<span class="text-muted">—</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-link text-danger p-0" onclick="rmDeleteMapping(${m.id})">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            wrap.innerHTML = html;
        });
}

async function rmSaveMapping() {
    const payload = {
        schoolclass_id: document.getElementById('mapClassId').value,
        subject_id:     document.getElementById('mapSubjectId').value || null,
        session_id:     document.getElementById('mapSessionId').value,
        term_id:        document.getElementById('mapTermId').value || null,
        note:           document.getElementById('mapNote').value || null,
    };

    if (!payload.schoolclass_id || !payload.session_id) {
        return AppleAlert.warning('Missing fields', 'Please select a class and a session.');
    }

    try {
        const res = await fetch(rmUrl(RM_ROUTES.storeMapping, rmMappingRoomId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': RM_CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('mapNote').value = '';
            rmLoadMappings(rmMappingRoomId);
            refreshRoomStats();
            AppleAlert.saved('Mapping added');
        } else {
            AppleAlert.error('Could not add mapping', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Could not add mapping', e.message);
    }
}

async function rmDeleteMapping(mappingId) {
    const ok = await AppleAlert.confirmDelete(
        'Remove mapping?',
        'This room will no longer be available for that class/subject.'
    );
    if (!ok) return;

    try {
        const res = await fetch(rmUrl(RM_ROUTES.destroyMapping, mappingId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': RM_CSRF, 'Accept': 'application/json' },
        });
        const d = await res.json();
        if (d.success) {
            rmLoadMappings(rmMappingRoomId);
            refreshRoomStats();
            AppleAlert.deleted('Mapping removed');
        } else {
            AppleAlert.error('Could not remove mapping', d.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Could not remove mapping', e.message);
    }
}

/* ── Bulk actions ────────────────────────────────────── */
async function rmBulkActivate(active) {
    if (!rmSelected.size) return;

    try {
        const res = await fetch(RM_ROUTES.bulkActivate, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': RM_CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                room_ids:  [...rmSelected],
                is_active: active,
            }),
        });
        const data = await res.json();
        if (data.success) {
            AppleAlert.saved(active ? 'Rooms activated' : 'Rooms deactivated');
            setTimeout(() => location.reload(), 900);
        } else {
            AppleAlert.error('Bulk update failed', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Bulk update failed', e.message);
    }
}

async function rmBulkDestroy() {
    if (!rmSelected.size) return;

    const ok = await AppleAlert.destructive(
        `Delete ${rmSelected.size} room(s)?`,
        'This can\'t be undone. Rooms that are still in use will block the whole batch.',
        { confirmText: 'Delete all' }
    );
    if (!ok.isConfirmed) return;

    try {
        const res = await fetch(RM_ROUTES.bulkDestroy, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': RM_CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ room_ids: [...rmSelected] }),
        });
        const data = await res.json();

        if (data.success) {
            AppleAlert.deleted(data.message || 'Rooms deleted');
            setTimeout(() => location.reload(), 900);
        } else if (data.blocked) {
            const list = data.blocked.map(b =>
                `<li><strong>${rmEsc(b.name)}</strong> — <em>${rmEsc(b.reason.replace(/_/g, ' '))}</em></li>`
            ).join('');
            AppleAlert.rich({
                title: 'Some rooms cannot be deleted',
                html: `<p>These rooms are still in use or have upcoming bookings:</p><ul class="apple-alert-list">${list}</ul>`,
                icon: 'warning',
                confirmText: 'Got it',
                theme: 'warning',
            });
        } else {
            AppleAlert.error('Bulk delete failed', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Bulk delete failed', e.message);
    }
}

function openBulkMapModal() {
    if (!rmSelected.size) return;

    Promise.all([
        fetch(RM_ROUTES.classesList).then(r => r.json()).catch(() => ({ data: [] })),
        fetch(RM_ROUTES.subjectsList).then(r => r.json()).catch(() => ({ data: [] })),
        fetch(RM_ROUTES.sessionsList).then(r => r.json()).catch(() => ({ data: [] })),
        fetch(RM_ROUTES.termsList).then(r => r.json()).catch(() => ({ data: [] })),
    ]).then(([classes, subjects, sessions, terms]) => {
        rmFillSelect('bulkMapClassId',   classes.data  ?? [], 'id', 'label',   '— Select class —');
        rmFillSelect('bulkMapSubjectId', subjects.data ?? [], 'id', 'subject', '— Any subject —');
        rmFillSelect('bulkMapSessionId', sessions.data ?? [], 'id', 'session', '— Select session —');
        rmFillSelect('bulkMapTermId',    terms.data    ?? [], 'id', 'term',    'All terms');
        document.getElementById('bulkMapCount').textContent = rmSelected.size;
        new bootstrap.Modal(document.getElementById('bulkMapModal')).show();
    });
}

async function rmSubmitBulkMap() {
    const payload = {
        room_ids:       [...rmSelected],
        schoolclass_id: document.getElementById('bulkMapClassId').value,
        subject_id:     document.getElementById('bulkMapSubjectId').value || null,
        session_id:     document.getElementById('bulkMapSessionId').value,
        term_id:        document.getElementById('bulkMapTermId').value || null,
    };

    if (!payload.schoolclass_id || !payload.session_id) {
        return AppleAlert.warning('Missing fields', 'Please select a class and a session.');
    }

    try {
        const res = await fetch(RM_ROUTES.bulkMap, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': RM_CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('bulkMapModal')).hide();
            AppleAlert.saved(data.message || 'Mappings applied');
            refreshRoomStats();
            rmClearSelection();
        } else {
            AppleAlert.error('Bulk mapping failed', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Bulk mapping failed', e.message);
    }
}

/* ── Init ────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('rm_view') || 'grid';
    rmSetView(savedView);

    rmPopulateBuildingFilter();
    refreshRoomStats();

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));
});
</script>
@endsection