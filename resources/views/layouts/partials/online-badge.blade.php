{{-- Top-bar "staff online" badge — only for users allowed to see it. --}}
@canany(['View online staff', 'View activity log'])
<div class="ms-1 header-item d-none d-sm-flex">
    <a href="{{ route('online-staff.index') }}" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle position-relative" title="Staff online" aria-label="Staff online">
        <i class="ri-user-follow-line fs-22"></i>
        <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-success" id="onlineCount" style="display:none">0</span>
    </a>
</div>
<script>
(function () {
    const el = document.getElementById('onlineCount'); if (!el) return;
    const load = () => fetch(@json(route('online-staff.count')), {headers: {'Accept': 'application/json'}}).then(r => r.ok ? r.json() : null)
        .then(d => { if (d) { el.textContent = d.count; el.style.display = d.count ? '' : 'none'; } }).catch(() => {});
    load(); setInterval(load, 60000);
})();
</script>
@endcanany
