{{--
    Grouped permission picker.
    Params:
      $pickerId   unique id for this instance (e.g. 'createPermPicker')
      $checkedIds array of permission ids already granted (default [])
--}}
@php
    use App\Support\PermissionMeta;
    $checkedIds = collect($checkedIds ?? [])->map(fn ($v) => (int) $v)->all();
    $groups = \Spatie\Permission\Models\Permission::where('guard_name', 'web')
        ->orderBy('title')->orderBy('name')->get()
        ->groupBy(fn ($p) => $p->title ?: 'Other')->sortKeys();
    $verbOrder = ['View' => 1, 'Preview' => 1, 'Create' => 2, 'Add' => 2, 'Edit' => 3, 'Assign' => 3, 'Approve' => 4, 'Submit' => 4, 'Post' => 4, 'Release' => 4, 'Pay' => 4, 'Manage' => 5, 'Close' => 6, 'Delete' => 7, 'Remove' => 7];
    $totalPerms = $groups->flatten()->count();
@endphp

@once
<style>
.pm-toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:14px; position:sticky; top:0; z-index:3; background:var(--rol-surface,#fff); padding:4px 0; }
.pm-search { flex:1; min-width:200px; position:relative; }
.pm-search input { width:100%; border:1px solid var(--rol-border,#e2e8f0); border-radius:9px; padding:8px 12px 8px 34px; font-size:13px; }
.pm-search i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.pm-count { font-size:12px; font-weight:700; color:#3730a3; background:#eef2ff; border:1px solid #c7d2fe; border-radius:20px; padding:5px 12px; white-space:nowrap; }
.pm-linkbtn { font-size:12px; font-weight:700; color:#6366f1; background:none; border:none; cursor:pointer; padding:5px 8px; border-radius:7px; }
.pm-linkbtn:hover { background:#eef2ff; }
.pm-group { border:1px solid var(--rol-border,#e2e8f0); border-radius:12px; margin-bottom:12px; overflow:hidden; }
.pm-group-head { display:flex; align-items:center; gap:10px; padding:11px 14px; background:#f8fafc; border-bottom:1px solid var(--rol-border,#e9edf3); cursor:pointer; }
.pm-group-head label { display:flex; align-items:center; gap:9px; font-weight:700; font-size:13.5px; color:#1e293b; cursor:pointer; margin:0; flex:1; }
.pm-group-badge { font-size:11px; font-weight:700; color:#64748b; background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:2px 9px; }
.pm-caret { color:#94a3b8; transition:transform .2s; }
.pm-group.collapsed .pm-caret { transform:rotate(-90deg); }
.pm-group.collapsed .pm-group-body { display:none; }
.pm-group-body { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:2px; padding:8px; }
.pm-item { display:flex; align-items:flex-start; gap:10px; padding:9px 11px; border-radius:9px; cursor:pointer; margin:0; transition:background .12s; }
.pm-item:hover { background:#f1f5f9; }
.pm-item input { margin-top:2px; width:16px; height:16px; accent-color:var(--rol-accent,#6366f1); cursor:pointer; flex-shrink:0; }
.pm-item-main { display:flex; flex-direction:column; gap:2px; min-width:0; }
.pm-item-top { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.pm-item-label { font-size:13px; font-weight:600; color:#0f172a; }
.pm-item-desc { font-size:11.5px; color:#64748b; line-height:1.4; }
.pm-chip { font-size:9.5px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; padding:2px 7px; border-radius:5px; white-space:nowrap; }
.pm-chip-view{background:#e0f2fe;color:#0369a1} .pm-chip-create{background:#dcfce7;color:#15803d}
.pm-chip-edit{background:#fef9c3;color:#a16207} .pm-chip-manage{background:#ede9fe;color:#6d28d9}
.pm-chip-delete{background:#fee2e2;color:#b91c1c} .pm-chip-approve{background:#cffafe;color:#0e7490}
.pm-chip-money{background:#fae8ff;color:#a21caf} .pm-chip-other{background:#e2e8f0;color:#475569}
.pm-empty { text-align:center; color:#94a3b8; font-size:13px; padding:26px 0; display:none; }
@media (prefers-color-scheme:dark){
  .pm-group-head{background:#1e293b;border-color:#334155} .pm-group-head label{color:#e2e8f0}
  .pm-item:hover{background:#1e293b} .pm-item-label{color:#f1f5f9} .pm-search input{background:#0f172a;border-color:#334155;color:#e2e8f0}
}
</style>
@endonce

<div class="perm-picker" id="{{ $pickerId }}" data-total="{{ $totalPerms }}">
    <div class="pm-toolbar">
        <div class="pm-search"><i class="bi bi-search"></i><input type="text" placeholder="Search permissions…" class="pm-search-input" autocomplete="off"></div>
        <span class="pm-count"><span class="pm-count-n">0</span> / {{ $totalPerms }} selected</span>
        <button type="button" class="pm-linkbtn pm-all"><i class="bi bi-check2-square"></i> Select all</button>
        <button type="button" class="pm-linkbtn pm-none"><i class="bi bi-square"></i> Clear all</button>
        <button type="button" class="pm-linkbtn pm-toggle-groups"><i class="bi bi-arrows-collapse"></i> Collapse groups</button>
    </div>

    @foreach ($groups as $groupName => $perms)
        @php $slug = Str::slug($groupName); @endphp
        <div class="pm-group" data-group="{{ $slug }}">
            <div class="pm-group-head">
                <label>
                    <input type="checkbox" class="pm-group-all" data-group="{{ $slug }}">
                    {{ $groupName }}
                    <span class="pm-group-badge"><span class="pm-group-count" data-group="{{ $slug }}">0</span>/{{ $perms->count() }}</span>
                </label>
                <i class="bi bi-chevron-down pm-caret"></i>
            </div>
            <div class="pm-group-body">
                @foreach ($perms->sortBy(fn ($p) => ($verbOrder[PermissionMeta::action($p->name)] ?? 9) . $p->name) as $p)
                    @php $m = PermissionMeta::for($p->name); @endphp
                    <label class="pm-item" data-search="{{ Str::lower($p->name . ' ' . $m['desc']) }}">
                        <input type="checkbox" name="permission[]" value="{{ $p->id }}" class="pm-item-check" data-group="{{ $slug }}" @checked(in_array((int) $p->id, $checkedIds))>
                        <span class="pm-item-main">
                            <span class="pm-item-top">
                                <span class="pm-chip {{ PermissionMeta::chipClass($m['action']) }}">{{ $m['action'] }}</span>
                                <span class="pm-item-label">{{ $m['label'] }}</span>
                            </span>
                            <span class="pm-item-desc">{{ $m['desc'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
    <div class="pm-empty">No permissions match your search.</div>
</div>

<script>
(function () {
    const root = document.getElementById(@json($pickerId));
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';

    const items   = () => Array.from(root.querySelectorAll('.pm-item-check'));
    const countEl = root.querySelector('.pm-count-n');

    function groupBoxes(slug) { return Array.from(root.querySelectorAll('.pm-item-check[data-group="' + slug + '"]')); }

    function refresh() {
        countEl.textContent = items().filter(c => c.checked).length;
        root.querySelectorAll('.pm-group-all').forEach(function (g) {
            const boxes = groupBoxes(g.dataset.group);
            const on = boxes.filter(b => b.checked).length;
            g.checked = on > 0 && on === boxes.length;
            g.indeterminate = on > 0 && on < boxes.length;
            const cnt = root.querySelector('.pm-group-count[data-group="' + g.dataset.group + '"]');
            if (cnt) cnt.textContent = on;
        });
    }

    // Per-group select all
    root.querySelectorAll('.pm-group-all').forEach(function (g) {
        g.addEventListener('change', function (e) {
            e.stopPropagation();
            groupBoxes(g.dataset.group).forEach(b => { b.checked = g.checked; });
            refresh();
        });
    });
    // Don't collapse when clicking the group checkbox label
    root.querySelectorAll('.pm-group-head label').forEach(l => l.addEventListener('click', e => e.stopPropagation()));

    // Collapse / expand a group by clicking its header
    root.querySelectorAll('.pm-group-head').forEach(function (h) {
        h.addEventListener('click', () => h.closest('.pm-group').classList.toggle('collapsed'));
    });

    root.querySelectorAll('.pm-item-check').forEach(c => c.addEventListener('change', refresh));

    root.querySelector('.pm-all').addEventListener('click', () => { items().forEach(c => c.checked = true); refresh(); });
    root.querySelector('.pm-none').addEventListener('click', () => { items().forEach(c => c.checked = false); refresh(); });

    const tg = root.querySelector('.pm-toggle-groups');
    tg.addEventListener('click', function () {
        const anyOpen = root.querySelector('.pm-group:not(.collapsed)');
        root.querySelectorAll('.pm-group').forEach(g => g.classList.toggle('collapsed', !!anyOpen));
        tg.innerHTML = anyOpen ? '<i class="bi bi-arrows-expand"></i> Expand groups' : '<i class="bi bi-arrows-collapse"></i> Collapse groups';
    });

    // Search filter
    const search = root.querySelector('.pm-search-input');
    const empty  = root.querySelector('.pm-empty');
    search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        let anyShown = false;
        root.querySelectorAll('.pm-group').forEach(function (grp) {
            let shown = 0;
            grp.querySelectorAll('.pm-item').forEach(function (it) {
                const hit = !q || it.dataset.search.includes(q);
                it.style.display = hit ? '' : 'none';
                if (hit) shown++;
            });
            grp.style.display = shown ? '' : 'none';
            if (q) grp.classList.remove('collapsed');
            if (shown) anyShown = true;
        });
        empty.style.display = anyShown ? 'none' : 'block';
    });

    // Keep the friendly "pick at least one" guard on whichever form this picker sits in.
    const form = root.closest('form');
    if (form && !form.dataset.pmGuard) {
        form.dataset.pmGuard = '1';
        form.addEventListener('submit', function (e) {
            if (!items().some(c => c.checked)) {
                e.preventDefault();
                if (window.Swal) { Swal.fire({ icon:'warning', title:'No permissions selected', text:'Please select at least one permission for this role.', confirmButtonColor:'#6366f1' }); }
                else { alert('Please select at least one permission for this role.'); }
            }
        });
    }

    refresh();
})();
</script>
