{{-- resources/views/attendance/partials/live-attendance-toast.blade.php
     Include this once in your master layout (e.g. right before </body> in
     app.blade.php) so punch toasts show up on every admin page, not just
     the attendance report. --}}
<div id="latToastStack" style="position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:320px;"></div>

<button id="latToggleBtn" type="button" title="Toggle live attendance notifications">
    <i class="ri-notification-3-line"></i>
    <span id="latToggleLabel">Live</span>
</button>

<style>
.lat-toast {
    background:#fff; border-left:4px solid #4f5fff; border-radius:10px;
    box-shadow:0 8px 24px rgba(0,0,0,.12); padding:12px 16px;
    display:flex; align-items:center; gap:10px; font-family:'Outfit',sans-serif;
    animation:latIn .25s ease; position:relative;
}
.lat-toast.late { border-left-color:#d97706; }
.lat-toast-icon {
    width:34px; height:34px; border-radius:9px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:#eef2ff; color:#4f5fff; font-size:15px;
}
.lat-toast.late .lat-toast-icon { background:#fef3c7; color:#d97706; }
.lat-toast-name { font-weight:600; font-size:13px; color:#1e2a3a; }
.lat-toast-sub  { font-size:11.5px; color:#6b7280; }
.lat-toast-close {
    position:absolute; top:6px; right:8px;
    background:none; border:none; color:#94a3b8; font-size:14px;
    cursor:pointer; line-height:1; padding:2px;
}
.lat-toast-close:hover { color:#1e2a3a; }
@keyframes latIn { from{opacity:0;transform:translateX(24px);} to{opacity:1;transform:translateX(0);} }

#latToggleBtn {
    position:fixed; bottom:20px; right:20px; z-index:99998;
    display:flex; align-items:center; gap:6px;
    background:#fff; border:1px solid #e2e8f0; border-radius:20px;
    padding:8px 14px; font-family:'Outfit',sans-serif; font-size:12px; font-weight:600;
    color:#1e2a3a; cursor:pointer; box-shadow:0 4px 14px rgba(0,0,0,.1);
    transition: background .15s, color .15s, opacity .15s;
}
#latToggleBtn.on { color:#16a34a; }
#latToggleBtn.on i { color:#16a34a; }
#latToggleBtn.off { color:#94a3b8; opacity:.75; }
#latToggleBtn.off i { color:#94a3b8; }
</style>

<script>
(function () {
    const STORAGE_ENABLED_KEY = 'lat_enabled'; // persists across sessions/tabs for this admin's browser
    let enabled = localStorage.getItem(STORAGE_ENABLED_KEY) !== '0'; // default ON

    let lastId = parseInt(sessionStorage.getItem('lat_last_id') || '0', 10);
    let primed = lastId > 0; // on first load, just sync the pointer — don't toast the backlog
    let pollTimer = null;

    const toggleBtn = document.getElementById('latToggleBtn');
    const toggleLabel = document.getElementById('latToggleLabel');
    const stack = document.getElementById('latToastStack');

    function renderToggleState() {
        if (!toggleBtn) return;
        toggleBtn.classList.toggle('on', enabled);
        toggleBtn.classList.toggle('off', !enabled);
        if (toggleLabel) toggleLabel.textContent = enabled ? 'Live' : 'Paused';
        if (stack) stack.style.display = enabled ? '' : 'none';
    }

    function showToast(item) {
        if (!stack) return;

        const el = document.createElement('div');
        el.className = 'lat-toast' + (item.is_late ? ' late' : '');
        el.innerHTML = `
            <button class="lat-toast-close" aria-label="Dismiss">&times;</button>
            <div class="lat-toast-icon"><i class="ri-${item.is_late ? 'time-line' : 'checkbox-circle-line'}"></i></div>
            <div>
                <div class="lat-toast-name">${item.name}</div>
                <div class="lat-toast-sub">${item.type === 'staff' ? 'Staff' : 'Student'} clocked in at ${item.punch_time}${item.is_late ? ' — Late' : ''}</div>
            </div>
        `;
        stack.appendChild(el);

        const dismiss = () => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); };
        el.querySelector('.lat-toast-close').addEventListener('click', dismiss);
        setTimeout(dismiss, 6000); // auto-dismiss if nobody closes it manually
    }

    function poll() {
        if (enabled) {
            fetch(`{{ route('attendance.live-feed') }}?since_id=${lastId}`, {
                headers: { 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.logs && data.logs.length) {
                    if (primed) {
                        data.logs
                            .filter(l => l.type === 'staff') // change/remove this filter to also toast student punches
                            .forEach(showToast);
                    }
                    primed = true;
                }
                lastId = data.last_id || lastId;
                sessionStorage.setItem('lat_last_id', lastId);
            })
            .catch(() => {}) // silent — a missed poll just gets picked up next cycle
            .finally(() => { pollTimer = setTimeout(poll, 6000); });
        } else {
            // Still tick the clock while paused so it resumes cleanly the moment
            // the admin turns it back on, without a burst of backlog toasts.
            pollTimer = setTimeout(poll, 6000);
        }
    }

    toggleBtn?.addEventListener('click', () => {
        enabled = !enabled;
        localStorage.setItem(STORAGE_ENABLED_KEY, enabled ? '1' : '0');
        renderToggleState();
        if (!enabled) {
            // Clear any toasts currently on screen when pausing.
            stack.querySelectorAll('.lat-toast').forEach(t => t.remove());
        }
    });

    renderToggleState();
    poll();
})();
</script>