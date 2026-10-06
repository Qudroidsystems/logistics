{{-- Shared styles for My Subject Vetting pages -- same design language as My Classes / My Subjects --}}
<style>
:root {
    --cb-navy:   #0f2342;
    --cb-teal:   #0d9488;
    --cb-sky:    #0ea5e9;
    --cb-amber:  #f59e0b;
    --cb-rose:   #f43f5e;
    --cb-green:  #22c55e;
    --cb-violet: #7c3aed;
    --cb-muted:  #64748b;
    --cb-border: #e2e8f0;
    --cb-radius: 14px;
    --cb-shadow: 0 4px 16px rgba(15,35,66,.10);
}
body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; }

.cb-hero { background: linear-gradient(135deg, var(--cb-navy) 0%, #1e4a7e 55%, #0d9488 100%); border-radius: var(--cb-radius); padding: 32px 36px; margin-bottom: 28px; position: relative; overflow: hidden; }
.cb-hero::before { content: ''; position: absolute; top: -80px; right: -80px; width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%); border-radius: 50%; }
.cb-hero h1 { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: #fff; margin: 0 0 8px; }
.cb-hero p { font-size: 13px; color: rgba(255,255,255,.72); margin: 0; }
.cb-hero .meta-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
.cb-meta-pill { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 20px; padding: 4px 14px; font-size: 12px; font-weight: 600; color: #fff; display: inline-flex; align-items: center; gap: 5px; }
.cb-back { display: inline-flex; align-items: center; gap: 6px; color: rgba(255,255,255,.85); font-size: 12px; font-weight: 600; text-decoration: none; margin-bottom: 10px; }
.cb-back:hover { color: #fff; }

.cb-stat { background: #fff; border: 1px solid var(--cb-border); border-radius: var(--cb-radius); padding: 20px 22px; position: relative; overflow: hidden; transition: transform .15s, box-shadow .15s; height: 100%; }
.cb-stat:hover { transform: translateY(-2px); box-shadow: var(--cb-shadow); }
.cb-stat .stat-accent { position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.cb-stat .stat-value { font-size: 30px; font-weight: 700; color: var(--cb-navy); line-height: 1; margin-top: 8px; }
.cb-stat .stat-label { font-size: 12px; color: var(--cb-muted); margin-top: 5px; font-weight: 500; }
.cb-stat .stat-ico { font-size: 36px; opacity: .08; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); }

.cb-card { background: #fff; border: 1px solid var(--cb-border); border-radius: var(--cb-radius); box-shadow: var(--cb-shadow); overflow: hidden; animation: fadeInUp .4s ease; }
.cb-card-header { padding: 18px 24px; border-bottom: 1px solid var(--cb-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: linear-gradient(to right, #f8fafc, #f0fdf9); }
.cb-card-header h5 { font-size: 15px; font-weight: 700; color: var(--cb-navy); margin: 0; display: flex; align-items: center; gap: 8px; }
.cb-count { background: var(--cb-teal); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }

.cb-toolbar { padding: 14px 24px; border-bottom: 1px solid var(--cb-border); display: flex; gap: 10px; flex-wrap: wrap; align-items: center; background: #fff; }
.cb-search { position: relative; flex: 1 1 220px; max-width: 320px; }
.cb-search input { width: 100%; border: 1.5px solid var(--cb-border); border-radius: 10px; padding: 8px 12px 8px 34px; font-size: 13px; }
.cb-search input:focus { outline: none; border-color: var(--cb-teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
.cb-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--cb-muted); }
.cb-select { border: 1.5px solid var(--cb-border); border-radius: 10px; padding: 7px 10px; font-size: 13px; background: #fff; }
.term-chips { display: flex; gap: 6px; flex-wrap: wrap; }
.term-chip { border: 1.5px solid var(--cb-border); background: #fff; color: #475569; border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; }
.term-chip:hover { border-color: var(--cb-teal); color: var(--cb-teal); }
.term-chip.active { background: var(--cb-teal); border-color: var(--cb-teal); color: #fff; }

.cb-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cb-table thead th { background: linear-gradient(135deg, var(--cb-navy), #1e4a7e); color: #fff; padding: 13px 14px; font-weight: 600; font-size: 12px; white-space: nowrap; text-align: left; letter-spacing: .3px; position: sticky; top: 0; z-index: 1; }
.cb-table tbody td { padding: 12px 14px; vertical-align: middle; border-bottom: 1px solid var(--cb-border); color: #334155; }
.cb-table tbody tr:hover td { background: #f0fdf9; }
.cb-table tbody tr:last-child td { border-bottom: none; }

.subject-name { font-weight: 700; color: var(--cb-navy); }
.subject-code { font-family: ui-monospace, monospace; font-size: 11px; color: var(--cb-muted); background: #f1f5f9; border-radius: 6px; padding: 1px 6px; margin-left: 6px; }
.class-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; background: linear-gradient(135deg, #f8fafc, #f1f5f9); color: var(--cb-navy); border: 1px solid var(--cb-border); }
.class-badge i { color: var(--cb-teal); }
.arm-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; background: var(--cb-teal); color: #fff; text-transform: uppercase; margin-left: 4px; }
.term-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; }
.term-1 { background: #dbeafe; color: #1e40af; }
.term-2 { background: #dcfce7; color: #166534; }
.term-3 { background: #fef3c7; color: #92400e; }
.session-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background: #e0e7ff; color: #3730a3; }

.progress-cell { min-width: 170px; }
.progress-track { height: 7px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width .6s cubic-bezier(.25,.8,.25,1); }
.progress-meta { display: flex; justify-content: space-between; gap: 8px; font-size: 11px; color: var(--cb-muted); margin-top: 4px; }

.status-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.st-pending   { background: #fef3c7; color: #92400e; }
.st-completed { background: #dcfce7; color: #15803d; }
.st-rejected  { background: #fee2e2; color: #b91c1c; }

.action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; text-decoration: none; transition: all .25s ease; border: 1px solid transparent; white-space: nowrap; background: none; cursor: pointer; }
.btn-open { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0369a1; border-color: #7dd3fc; }
.btn-open:hover { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; border-color: #0ea5e9; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(14,165,233,.25); }
.btn-more { padding: 7px 9px; background: #f8fafc; color: #475569; border-color: var(--cb-border); }
.btn-more:hover { background: #f1f5f9; }
.btn-vet-all { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; border-color: #86efac; }
.btn-vet-all:hover { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
.btn-unvet-all { background: #fff; color: #b91c1c; border-color: #fecaca; }
.btn-unvet-all:hover { background: #fee2e2; }
.btn-complete { background: linear-gradient(135deg, var(--cb-teal), #0f766e); color: #fff; }
.btn-complete:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13,148,136,.3); }
.btn-reject { background: #fff; color: #b91c1c; border-color: #fecaca; }
.btn-reject:hover { background: #fee2e2; color: #991b1b; }
.btn-disabled { background: #f1f5f9; color: #94a3b8; border-color: var(--cb-border); cursor: not-allowed; }
.action-btn:disabled { opacity: .55; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

.empty-state { text-align: center; padding: 56px 24px; }
.empty-state i { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 14px; }
.empty-state h6 { font-size: 17px; color: var(--cb-navy); margin-bottom: 6px; }
.empty-state p { color: var(--cb-muted); font-size: 13px; margin: 0; }
.no-match { display: none; }

@keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
    .cb-hero { padding: 24px 20px; }
    .cb-hero h1 { font-size: 22px; }
    .cb-toolbar, .cb-card-header { padding: 12px 16px; }
    .cb-table.stack thead { display: none; }
    .cb-table.stack tbody tr { display: block; border-bottom: 1px solid var(--cb-border); padding: 6px 0; }
    .cb-table.stack tbody td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border: none; padding: 7px 16px; text-align: right; }
    .cb-table.stack tbody td::before { content: attr(data-label); font-weight: 700; color: var(--cb-navy); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; text-align: left; }
    .progress-cell { min-width: 0; }
    .progress-cell > div { flex: 1; max-width: 60%; }
}
@media (prefers-reduced-motion: reduce) { .cb-card, .progress-fill, .cb-stat { animation: none !important; transition: none !important; } }
</style>
