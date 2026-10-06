{{-- Lightweight toast used by the My Subject Vetting pages (no external library needed) --}}
<div id="vetToastHost" aria-live="polite" style="position:fixed;bottom:20px;right:20px;z-index:1090;display:flex;flex-direction:column;gap:8px;"></div>
<script>
function vetToast(message, type) {
    const colors = { success: '#16a34a', danger: '#dc2626', warning: '#d97706', info: '#0d9488' };
    const el = document.createElement('div');
    el.setAttribute('role', 'status');
    el.style.cssText = `background:#fff;border-left:4px solid ${colors[type] || colors.info};padding:11px 16px;border-radius:10px;`
        + 'box-shadow:0 8px 24px rgba(15,35,66,.18);max-width:360px;font-size:13px;color:#1e293b;'
        + 'transition:opacity .3s, transform .3s;opacity:0;transform:translateY(8px);';
    el.textContent = message;
    document.getElementById('vetToastHost').appendChild(el);
    requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; });
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3500);
}
</script>
