{{-- resources/views/layouts/partials/notification-bell.blade.php — topbar bell --}}
@php
    $pnUnread = 0;
    try { $pnUnread = \Illuminate\Support\Facades\Schema::hasTable('notifications') ? auth()->user()->unreadNotifications()->count() : 0; } catch (\Throwable $e) {}
@endphp
<div class="position-relative" id="pn-wrapper">
    <button type="button" id="pn-btn" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle position-relative" style="width:38px;height:38px;" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
        <i class="ri-notification-3-line fs-20"></i>
        <span id="pn-count" class="position-absolute badge rounded-pill bg-danger" style="top:2px;right:0;font-size:10px;{{ $pnUnread ? '' : 'display:none;' }}">{{ $pnUnread > 99 ? '99+' : $pnUnread }}</span>
    </button>
    <div id="pn-dropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:-60px;width:340px;max-width:92vw;background:var(--vz-dropdown-bg,#fff);border:1px solid var(--vz-border-color,#e9ebec);border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,.14);z-index:9999;overflow:hidden;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <strong style="font-size:14px;">Notifications</strong>
            <button type="button" id="pn-readall" class="btn btn-link btn-sm p-0 text-decoration-none">Mark all read</button>
        </div>
        <div id="pn-list" style="max-height:360px;overflow:auto;"><div class="p-3 small text-muted">Loading…</div></div>
        <a href="{{ route('notifications.index') }}" class="d-block text-center py-2 border-top small text-decoration-none">View all</a>
    </div>
</div>
<script>
(function () {
    const btn = document.getElementById('pn-btn'), dd = document.getElementById('pn-dropdown'), list = document.getElementById('pn-list'), count = document.getElementById('pn-count');
    const FEED = @json(route('notifications.feed')), READALL = @json(route('notifications.read-all'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function setCount(n) { count.textContent = n > 99 ? '99+' : n; count.style.display = n ? '' : 'none'; }
    async function load() {
        try {
            const j = await fetch(FEED, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
            setCount(j.unread || 0);
            list.innerHTML = (j.items || []).length ? j.items.map(n =>
                '<a href="' + esc(n.url) + '" class="d-flex gap-2 px-3 py-2 text-decoration-none border-bottom" style="color:inherit;' + (n.read ? '' : 'background:rgba(13,148,136,.06);') + '">' +
                '<i class="' + esc(n.icon) + ' fs-18" style="color:#0d9488"></i><span class="flex-grow-1"><span class="d-block fw-semibold" style="font-size:13px">' + esc(n.title) + '</span>' +
                '<span class="d-block text-muted" style="font-size:12px">' + esc(n.body) + '</span><span class="text-muted" style="font-size:11px">' + esc(n.time) + '</span></span></a>').join('')
                : '<div class="p-4 text-center small text-muted">No notifications yet</div>';
        } catch (e) { list.innerHTML = '<div class="p-3 small text-danger">Could not load notifications.</div>'; }
    }
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const open = dd.style.display === 'none';
        dd.style.display = open ? 'block' : 'none';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) load();
    });
    document.addEventListener('click', e => { if (!document.getElementById('pn-wrapper').contains(e.target)) dd.style.display = 'none'; });
    document.getElementById('pn-readall').addEventListener('click', async () => {
        await fetch(READALL, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
        load();
    });
    // Refresh the badge every 2 minutes while the page is open.
    setInterval(() => { if (!document.hidden) fetch(FEED, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(j => setCount(j.unread || 0)).catch(() => {}); }, 120000);
})();
</script>
