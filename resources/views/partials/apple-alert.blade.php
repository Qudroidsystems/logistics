{{--
    Shared Apple-style alert theming for SweetAlert2.
    Include once per page:  @include('partials.apple-alert')

    Requires SweetAlert2 to already be loaded on the page.

    IMPORTANT — do not use Swal.mixin here. Mixin instances hold their own
    internal state, and calling Swal.close() from outside a mixin does not
    reliably close a dialog opened by that mixin. That produces the classic
    "stuck dialog" bug: the alert stays on screen and blocks every future
    Swal.fire() call until the page is reloaded.

    This module always calls bare Swal.fire() with a customClass block
    per call, so Swal.close() always closes the one visible dialog.
--}}
<style>
/* ============================================================
   AppleAlert — Apple-style alert theming for SweetAlert2
   ============================================================ */

/* ── Backdrop ────────────────────────────────────────────── */
.swal2-container.apple-alert-container {
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    background: rgba(15, 23, 42, 0.28);
    padding: 20px;
    z-index: 20000;
}
.swal2-container.apple-alert-container.swal2-backdrop-show {
    background: rgba(15, 23, 42, 0.28);
}

/* ── Popup card ──────────────────────────────────────────── */
.swal2-popup.apple-alert {
    border-radius: 20px;
    border: none;
    box-shadow:
        0 20px 60px rgba(15, 23, 42, 0.24),
        0 4px 16px rgba(15, 23, 42, 0.12),
        0 0 0 0.5px rgba(0, 0, 0, 0.04);
    padding: 28px 24px 20px;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display",
                 "Helvetica Neue", Inter, system-ui, sans-serif;
    font-feature-settings: "cv02", "cv03", "cv04", "cv11";
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    max-width: 420px;
    min-width: 320px;
    width: auto;
    background: #FFFFFF;
    animation: appleAlertPop 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes appleAlertPop {
    0%   { transform: scale(0.92); opacity: 0; }
    60%  { transform: scale(1.015); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

/* ── Icon ────────────────────────────────────────────────── */
.swal2-popup.apple-alert .swal2-icon {
    width: 56px;
    height: 56px;
    margin: 4px auto 14px;
    border-width: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.swal2-popup.apple-alert .swal2-icon .swal2-icon-content {
    font-size: 28px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 400;
}
.swal2-popup.apple-alert .swal2-icon.swal2-success {
    background: #E8F9EE;
    color: #34C759;
    border: none;
}
.swal2-popup.apple-alert .swal2-icon.swal2-success .swal2-success-ring {
    display: none;
}
.swal2-popup.apple-alert .swal2-icon.swal2-success [class^='swal2-success-line'] {
    background-color: #34C759;
}
.swal2-popup.apple-alert .swal2-icon.swal2-error {
    background: #FEECEC;
    color: #FF3B30;
    border: none;
}
.swal2-popup.apple-alert .swal2-icon.swal2-error [class^='swal2-x-mark-line'] {
    background-color: #FF3B30;
}
.swal2-popup.apple-alert .swal2-icon.swal2-warning {
    background: #FFF4E5;
    color: #FF9500;
    border: none;
}
.swal2-popup.apple-alert .swal2-icon.swal2-info {
    background: #E5F1FF;
    color: #007AFF;
    border: none;
}
.swal2-popup.apple-alert .swal2-icon.swal2-question {
    background: #EEF1F5;
    color: #8E8E93;
    border: none;
}

/* ── Title ───────────────────────────────────────────────── */
.swal2-popup.apple-alert .swal2-title {
    font-size: 18px;
    font-weight: 600;
    color: #0F172A;
    letter-spacing: -0.015em;
    line-height: 1.3;
    margin: 0 0 6px;
    padding: 0 8px;
}
.swal2-popup.apple-alert.swal2-icon-show .swal2-title {
    margin: 0 0 6px;
}

/* ── HTML / text body ────────────────────────────────────── */
.swal2-popup.apple-alert .swal2-html-container {
    font-size: 14px;
    font-weight: 400;
    color: #475569;
    line-height: 1.5;
    letter-spacing: -0.005em;
    margin: 0;
    padding: 0 8px;
}
.swal2-popup.apple-alert .swal2-html-container code {
    background: #F1F5F9;
    color: #0F172A;
    padding: 2px 6px;
    border-radius: 6px;
    font-family: "SF Mono", ui-monospace, Menlo, monospace;
    font-size: 13px;
    letter-spacing: 0;
}
.swal2-popup.apple-alert .swal2-html-container p {
    margin: 0 0 8px;
}
.swal2-popup.apple-alert .swal2-html-container p:last-child { margin-bottom: 0; }

/* ── Actions row ─────────────────────────────────────────── */
.swal2-popup.apple-alert .swal2-actions {
    margin: 22px 0 0;
    gap: 8px;
    width: 100%;
    justify-content: stretch;
    flex-wrap: nowrap;
}
.swal2-popup.apple-alert .swal2-actions:not(.swal2-loading) .swal2-styled[disabled] {
    opacity: 0.4;
}

/* ── Buttons ─────────────────────────────────────────────── */
.swal2-popup.apple-alert .swal2-styled {
    border: none;
    border-radius: 12px;
    padding: 11px 20px;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: -0.005em;
    font-family: inherit;
    transition: all 0.15s ease;
    box-shadow: none;
    min-height: 44px;
    flex: 1;
    margin: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.swal2-popup.apple-alert .swal2-styled:focus { box-shadow: none; outline: none; }
.swal2-popup.apple-alert .swal2-styled:active { transform: scale(0.97); }

.swal2-popup.apple-alert .swal2-confirm {
    background: #007AFF;
    color: #FFFFFF;
}
.swal2-popup.apple-alert .swal2-confirm:hover { background: #0066D6; }

.swal2-popup.apple-alert .swal2-confirm.apple-destructive { background: #FF3B30; }
.swal2-popup.apple-alert .swal2-confirm.apple-destructive:hover { background: #E63329; }

.swal2-popup.apple-alert .swal2-confirm.apple-warning { background: #FF9500; }
.swal2-popup.apple-alert .swal2-confirm.apple-warning:hover { background: #E68600; }

.swal2-popup.apple-alert .swal2-confirm.apple-success { background: #34C759; }
.swal2-popup.apple-alert .swal2-confirm.apple-success:hover { background: #2DA84A; }

.swal2-popup.apple-alert .swal2-cancel {
    background: #F1F5F9;
    color: #0F172A;
}
.swal2-popup.apple-alert .swal2-cancel:hover { background: #E2E8F0; }

.swal2-popup.apple-alert .swal2-actions:not(:has(.swal2-cancel)) .swal2-confirm {
    flex: 1;
}

/* ── Loading state ───────────────────────────────────────── */
.swal2-popup.apple-alert.apple-loading {
    padding: 32px 24px;
    text-align: center;
}
.swal2-popup.apple-alert.apple-loading .swal2-title {
    font-size: 15px;
    font-weight: 500;
    color: #475569;
    margin-top: 14px;
}
.swal2-popup.apple-alert.apple-loading .swal2-loader {
    border-color: #007AFF transparent #007AFF transparent;
    border-width: 3px;
    width: 36px;
    height: 36px;
    margin: 0 auto;
}
.swal2-popup.apple-alert.apple-loading .swal2-html-container { display: none; }
.swal2-popup.apple-alert.apple-loading .swal2-actions { display: none; }

/* ── Inputs ─────────────────────────────────────────────── */
.swal2-popup.apple-alert .swal2-input,
.swal2-popup.apple-alert .swal2-textarea,
.swal2-popup.apple-alert .swal2-select {
    border-radius: 12px;
    border: 1.5px solid #E2E8F0;
    font-family: inherit;
    font-size: 14px;
    padding: 10px 14px;
    color: #0F172A;
    margin: 14px 0 0;
    width: 100%;
    transition: all 0.15s;
    box-shadow: none;
}
.swal2-popup.apple-alert .swal2-input:focus,
.swal2-popup.apple-alert .swal2-textarea:focus,
.swal2-popup.apple-alert .swal2-select:focus {
    border-color: #007AFF;
    box-shadow: 0 0 0 3.5px rgba(0, 122, 255, 0.15);
    outline: none;
}

/* ── Toast (top-center) ─────────────────────────────────── */
.swal2-container.apple-toast-container {
    backdrop-filter: none;
    background: transparent;
    padding-top: 16px;
    align-items: flex-start;
    justify-content: center;
    z-index: 21000;
}
.swal2-popup.apple-toast {
    background: rgba(15, 23, 42, 0.92);
    color: #FFFFFF;
    border-radius: 14px;
    padding: 12px 18px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.24);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", Inter, system-ui, sans-serif;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: -0.005em;
    width: auto;
    max-width: 380px;
    min-width: 0;
    border: none;
    animation: appleToastIn 0.32s cubic-bezier(0.34, 1.56, 0.64, 1);
    backdrop-filter: blur(30px) saturate(180%);
    -webkit-backdrop-filter: blur(30px) saturate(180%);
}
@keyframes appleToastIn {
    from { transform: translateY(-20px) scale(0.94); opacity: 0; }
    to   { transform: translateY(0) scale(1); opacity: 1; }
}
.swal2-popup.apple-toast .swal2-title {
    font-size: 14px;
    font-weight: 500;
    color: #FFFFFF;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.35;
}
.swal2-popup.apple-toast .swal2-title .apple-toast-icon {
    font-size: 16px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}
.swal2-popup.apple-toast .swal2-html-container { display: none; }
.swal2-popup.apple-toast .swal2-actions { display: none; }
.swal2-popup.apple-toast .swal2-timer-progress-bar {
    background: rgba(255, 255, 255, 0.35);
    height: 2px;
    border-radius: 2px;
}
.swal2-popup.apple-toast.apple-toast-success .apple-toast-icon { color: #34C759; }
.swal2-popup.apple-toast.apple-toast-error   .apple-toast-icon { color: #FF453A; }
.swal2-popup.apple-toast.apple-toast-info    .apple-toast-icon { color: #0A84FF; }
.swal2-popup.apple-toast.apple-toast-warning .apple-toast-icon { color: #FF9F0A; }

/* ── Compact HTML helpers used inside alert bodies ──────── */
.apple-alert-list {
    list-style: none;
    margin: 12px 0 0;
    padding: 0;
    text-align: left;
    border-radius: 12px;
    overflow: hidden;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
}
.apple-alert-list li {
    padding: 10px 14px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #E2E8F0;
    line-height: 1.4;
}
.apple-alert-list li:last-child { border-bottom: none; }
.apple-alert-list li strong { color: #0F172A; }
.apple-alert-list li em { color: #64748B; font-style: normal; }

.apple-alert-badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
    justify-content: center;
}
.apple-alert-badge {
    font-size: 11px;
    padding: 5px 10px;
    border-radius: 8px;
    background: #F1F5F9;
    color: #475569;
    font-weight: 600;
    border: 1px solid #E2E8F0;
    cursor: default;
}
.apple-alert-badge.interactive { cursor: pointer; transition: all .15s; }
.apple-alert-badge.interactive:hover {
    background: #007AFF;
    color: #FFF;
    border-color: #007AFF;
}

.apple-alert-code-block {
    display: inline-block;
    padding: 10px 18px;
    margin: 12px 0 0;
    background: #F1F5F9;
    border-radius: 10px;
    font-family: "SF Mono", ui-monospace, Menlo, monospace;
    font-size: 17px;
    font-weight: 600;
    letter-spacing: 3px;
    color: #0F172A;
    border: 1px solid #E2E8F0;
}

/* ── Reduced motion ─────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
    .swal2-popup.apple-alert,
    .swal2-popup.apple-toast { animation: none; }
}
</style>

<script>
/* ============================================================
   AppleAlert — wrapper around SweetAlert2 with an iOS-style theme.

   Design constraints (see comment at top of file):
     1. Never use Swal.mixin. Always call bare Swal.fire() so
        Swal.close() always closes the visible dialog.
     2. Track a `busy` flag. Before opening a new dialog while one
        is already open, force-close the current one and wait a
        tick, so SweetAlert2 never queues dialogs (which was the
        cause of the stuck-dialog bug).
     3. Expose a guaranteed close() that force-dismisses the
        current popup even if state got out of sync.

   Every helper returns a Promise resolving to Swal's result
   object, so call sites chain .then() exactly as before.
   ============================================================ */
const AppleAlert = (function () {

    let busy = false;        // true while a dialog is open (not toasts)
    let lastDialogPromise = null;

    // Base classes for a themed dialog. Toasts use different classes.
    function baseDialogOptions() {
        return {
            customClass: {
                container: 'apple-alert-container',
                popup:     'apple-alert',
            },
            buttonsStyling: false,
            reverseButtons: false,
            heightAuto: false,
        };
    }

    /* Force-close whatever dialog SweetAlert2 currently has open, and
       wait for the DOM to settle. Used before opening a new dialog so
       the new one is never queued behind a hidden-but-not-dismissed
       dialog. */
    function forceClose() {
        try {
            if (Swal.isVisible && Swal.isVisible()) {
                Swal.close();
            }
        } catch (e) { /* swallow */ }

        // If the popup element is still in the DOM after Swal.close(),
        // remove it manually — this is what unsticks the page when
        // SweetAlert2's internal state has desynced.
        setTimeout(() => {
            const containers = document.querySelectorAll('.swal2-container');
            containers.forEach(el => {
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            document.body.classList.remove('swal2-shown', 'swal2-height-auto');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
        }, 0);

        busy = false;
        lastDialogPromise = null;
    }

    /* Every helper funnels through this. It handles the mutual exclusion
       for dialogs (toasts bypass it). */
    function openDialog(options) {
        forceClose();

        // Defer the actual fire to the next tick so forceClose's
        // setTimeout DOM sweep runs first.
        return new Promise((resolve) => {
            setTimeout(() => {
                busy = true;

                const promise = Swal.fire(Object.assign({}, baseDialogOptions(), options));

                promise.then((result) => {
                    busy = false;
                    lastDialogPromise = null;
                    resolve(result);
                });

                lastDialogPromise = promise;
            }, 0);
        });
    }

    /* Toasts can stack — no busy check, no forceClose. */
    function fireToast(options) {
        const toastOptions = Object.assign({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            heightAuto: false,
            customClass: {
                container: 'apple-toast-container',
                popup:     'apple-toast',
            },
            didOpen: (el) => {
                el.addEventListener('mouseenter', Swal.stopTimer);
                el.addEventListener('mouseleave', Swal.resumeTimer);
            },
        }, options);
        return Swal.fire(toastOptions);
    }

    // ── Loading spinner ────────────────────────────────────────
    function loading(title) {
        return openDialog({
            title: title || 'Processing…',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                const popup = document.querySelector('.swal2-popup.apple-alert');
                if (popup) popup.classList.add('apple-loading');
            },
        });
    }

    /* Hard close — always closes the current dialog, even if the busy
       flag was left in a bad state. */
    function close() {
        forceClose();
    }

    // ── Simple dialogs ─────────────────────────────────────────
    function success(title, text) {
        return openDialog({
            icon: 'success',
            title: title,
            html: text ? text : undefined,
            confirmButtonText: 'OK',
            timer: 2200,
            timerProgressBar: true,
        });
    }

    function error(title, text) {
        return openDialog({
            icon: 'error',
            title: title,
            html: text ? text : undefined,
            confirmButtonText: 'OK',
        });
    }

    function warning(title, text) {
        return openDialog({
            icon: 'warning',
            title: title,
            html: text ? text : undefined,
            confirmButtonText: 'OK',
        });
    }

    function info(title, text) {
        return openDialog({
            icon: 'info',
            title: title,
            html: text ? text : undefined,
            confirmButtonText: 'OK',
        });
    }

    // ── Confirms ───────────────────────────────────────────────
    function confirm(title, text, opts) {
        opts = opts || {};
        return openDialog({
            title: title,
            html: text ? text : undefined,
            icon: opts.icon || undefined,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Continue',
            cancelButtonText:  opts.cancelText  || 'Cancel',
            focusCancel: !!opts.focusCancel,
            width: opts.width || undefined,
        });
    }

    function destructive(title, text, opts) {
        opts = opts || {};
        return openDialog({
            title: title,
            html: text ? text : undefined,
            icon: opts.icon || undefined,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Delete',
            cancelButtonText:  opts.cancelText  || 'Cancel',
            focusCancel: opts.focusCancel !== false,
            width: opts.width || undefined,
            didOpen: () => {
                const btn = document.querySelector('.swal2-popup.apple-alert .swal2-confirm');
                if (btn) btn.classList.add('apple-destructive');
                if (typeof opts.didOpen === 'function') opts.didOpen();
            },
        });
    }

    /* Rich dialog. `theme` picks the confirm button colour:
       'primary' | 'destructive' | 'warning' | 'success' */
    function rich(options) {
        options = options || {};
        const theme = options.theme || 'primary';

        return openDialog({
            title: options.title || '',
            html: options.html || '',
            icon: options.icon || undefined,
            showCancelButton: !!options.showCancelButton,
            confirmButtonText: options.confirmText || 'OK',
            cancelButtonText:  options.cancelText  || 'Cancel',
            focusCancel: options.focusCancel === true,
            width: options.width || undefined,
            allowOutsideClick: options.allowOutsideClick !== false,
            allowEscapeKey: options.allowEscapeKey !== false,
            didOpen: () => {
                if (theme !== 'primary') {
                    const btn = document.querySelector('.swal2-popup.apple-alert .swal2-confirm');
                    if (btn) btn.classList.add('apple-' + theme);
                }
                if (typeof options.didOpen === 'function') options.didOpen();
            },
            preConfirm: typeof options.preConfirm === 'function' ? options.preConfirm : undefined,
        });
    }

    // ── Toast ──────────────────────────────────────────────────
    function toast(message, type, durationMs) {
        type = type || 'success';
        durationMs = durationMs || 2000;

        const icons = {
            success: '✓',
            error:   '✕',
            info:    'ⓘ',
            warning: '⚠',
        };
        const icon = icons[type] || icons.success;

        return fireToast({
            title: `<span class="apple-toast-icon">${icon}</span><span>${message}</span>`,
            timer: durationMs,
            customClass: {
                container: 'apple-toast-container',
                popup:     `apple-toast apple-toast-${type}`,
            },
        });
    }

    // ── Convenience ────────────────────────────────────────────
    async function confirmDelete(title, text) {
        const r = await destructive(title, text, { confirmText: 'Delete' });
        return !!r.isConfirmed;
    }

    function saved(message)    { return toast(message || 'Saved', 'success'); }
    function deleted(message)  { return toast(message || 'Deleted', 'success'); }
    function copied(message)   { return toast(message || 'Copied to clipboard', 'info'); }
    function failed(message)   { return toast(message || 'Something went wrong', 'error', 2600); }

    // ── Public surface ─────────────────────────────────────────
    return {
        loading, close,
        success, error, warning, info,
        confirm, destructive, rich, toast,
        confirmDelete, saved, deleted, copied, failed,

        // Escape hatch: expose the same low-level entry points the
        // module itself uses, in case a caller needs them.
        _openDialog: openDialog,
        _fireToast:  fireToast,
    };
})();
</script>