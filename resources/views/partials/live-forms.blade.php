{{-- Submits POST forms inside [data-live-page] in the background and swaps in the updated section, so the page never reloads. --}}
<script>
(function () {
    if (window.__liveForms) return;
    window.__liveForms = true;

    function notice(root, text) {
        var box = document.createElement('div');
        box.className = 'cb-banner warning';
        box.innerHTML = '<i class="ri-error-warning-line"></i><div></div>';
        box.lastChild.textContent = text;
        var at = root.querySelector('.container-fluid') || root;
        at.insertBefore(box, at.firstChild);
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.addEventListener('submit', async function (e) {
        var form = e.target;
        var root = form.closest('[data-live-page]');
        if (!root || e.defaultPrevented || form.hasAttribute('data-no-live')) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        e.preventDefault();

        var data = new FormData(form);
        if (e.submitter && e.submitter.name) data.append(e.submitter.name, e.submitter.value);
        var buttons = form.querySelectorAll('button, input[type=submit]');
        buttons.forEach(function (b) { b.disabled = true; });
        root.style.opacity = '0.6';

        try {
            var res = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'Accept': 'text/html' } });
            if (res.status === 419) { window.location.reload(); return; }
            var doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            var fresh = doc.querySelector('[data-live-page]');
            if (!fresh) {
                if (res.ok && res.url) { window.location.assign(res.url); return; }
                throw new Error('status ' + res.status);
            }
            root.replaceWith(fresh);
            if (res.url && res.url !== window.location.href) history.replaceState(null, '', res.url);
            var banner = fresh.querySelector('.cb-banner');
            if (banner) banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (err) {
            root.style.opacity = '';
            buttons.forEach(function (b) { b.disabled = false; });
            notice(root, 'That did not go through. Check your connection and try again.');
        }
    });
})();
</script>
