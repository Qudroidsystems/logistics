{{--
    Keeps a dashboard live: reloads the page every $seconds (default 60) while the tab is visible, unless the person is
    typing or a dialog is open. A switch in the corner turns it off; the choice is remembered in this browser.
--}}
@php $seconds = max(15, (int) ($seconds ?? 60)); @endphp
<div class="position-fixed bottom-0 end-0 m-3 px-3 py-2 bg-white rounded-pill shadow-sm small d-flex align-items-center gap-2" style="z-index:1030" id="arBox">
    <span class="d-inline-block rounded-circle" id="arDot" style="width:8px;height:8px;background:#0ab39c"></span>
    <span id="arText">Live · updated {{ now()->format('H:i') }}</span>
    <div class="form-check form-switch mb-0 ms-1"><input class="form-check-input" type="checkbox" id="arToggle" checked aria-label="Refresh by itself"></div>
</div>
<script>
(function () {
    var KEY = 'autoRefresh:' + location.pathname, every = {{ $seconds }} * 1000, toggle = document.getElementById('arToggle');
    var on = true;
    try { on = localStorage.getItem(KEY) !== 'off'; } catch (e) {}
    function paint() { toggle.checked = on; document.getElementById('arDot').style.background = on ? '#0ab39c' : '#ced4da'; if (!on) document.getElementById('arText').textContent = 'Auto-refresh off'; }
    toggle.addEventListener('change', function () { on = toggle.checked; try { localStorage.setItem(KEY, on ? 'on' : 'off'); } catch (e) {} paint(); });
    paint();
    setInterval(function () {
        var busy = document.activeElement && /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) && document.activeElement !== toggle;
        if (on && !document.hidden && !busy && !document.querySelector('.modal.show')) location.reload();
    }, every);
})();
</script>
