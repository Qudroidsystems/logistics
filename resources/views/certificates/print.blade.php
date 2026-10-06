{{-- resources/views/certificates/print.blade.php --}}
@extends('layouts.master')

@section('content')
@php $t = $certificate->template; $W = (int)($t->width ?? 1123); $H = (int)($t->height ?? 794); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0"><i class="ri-printer-line me-2 text-primary"></i>{{ $certificate->serial }}</h4>
            <div class="small text-muted">{{ trim(($certificate->student->firstname ?? '').' '.($certificate->student->lastname ?? '')) }} · {{ $t->name ?? '' }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('certificates.show', $certificate) }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Details</a>
            <button class="btn btn-outline-secondary btn-sm" id="btnPreview"><i class="ri-eye-line me-1"></i>Preview</button>
            <button class="btn btn-primary btn-sm" id="btnGenerate" @disabled($awaitingApproval || $limitReached)><i class="ri-download-2-line me-1"></i>Generate &amp; download PDF</button>
        </div>
    </div>

    @if($awaitingApproval)
        <div class="alert alert-warning"><i class="ri-time-line me-1"></i>This certificate is awaiting approval. An authorised user must approve it before it can be generated.
            @can('Approve certificates')<form method="POST" action="{{ route('certificates.approve', $certificate) }}" class="d-inline ms-2">@csrf<button class="btn btn-sm btn-success"><i class="ri-check-double-line me-1"></i>Approve now</button></form>@endcan
        </div>
    @elseif($limitReached)
        <div class="alert alert-danger"><i class="ri-lock-line me-1"></i>The generation limit ({{ $limit }}) for this student has been reached. A user with "Override certificate limit" can still generate it.</div>
    @endif

    <div id="genAlert" class="alert d-none"></div>

    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted">Copies generated: <strong id="genCount">{{ $certificate->generation_count }}</strong>@if($limit) / {{ $limit }}@endif</div>
        </div>
        <div style="overflow:auto;background:#e9ecef;border-radius:8px;padding:14px;text-align:center">
            <canvas id="printCanvas" style="box-shadow:0 2px 16px rgba(0,0,0,.15);background:#fff;max-width:100%"></canvas>
        </div>
    </div></div>
</div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    const CSRF = '{{ csrf_token() }}';
    const W = {{ $W }}, H = {{ $H }};
    const design = @json($design);
    const data = @json($data);
    const fields = (data && data.fields) ? data.fields : {};
    const images = (data && data.images) ? data.images : {};
    const verifyUrl = @json($verifyUrl);
    const hitUrl = '{{ route('certificates.generate-hit', $certificate) }}';
    const storeUrl = '{{ route('certificates.rendered', $certificate) }}';
    const serial = @json($certificate->serial);
    const orient = '{{ $t->orientation ?? 'landscape' }}';

    function isText(o) { return o.type === 'textbox' || o.type === 'text' || o.type === 'i-text'; }
    function substituteTokens(txt) { return String(txt).replace(/\{\{\s*([\w.]+)\s*\}\}/g, function (m, k) { return (fields[k] != null ? fields[k] : ''); }); }
    function qrDataUrl() { try { const q = new QRious({ value: verifyUrl, size: 400, level: 'M' }); return q.toDataURL('image/png'); } catch (e) { return null; } }

    function replaceWithImage(canvas, obj, url) {
        return new Promise(resolve => {
            const bx = obj.left, by = obj.top, bw = obj.width * (obj.scaleX || 1), bh = obj.height * (obj.scaleY || 1);
            const idx = canvas.getObjects().indexOf(obj);
            fabric.Image.fromURL(url, function (img) {
                const scale = Math.min(bw / img.width, bh / img.height);
                img.set({ left: bx + (bw - img.width * scale) / 2, top: by + (bh - img.height * scale) / 2, scaleX: scale, scaleY: scale, selectable: false });
                canvas.remove(obj);
                canvas.insertAt(img, idx, false);
                resolve();
            }, { crossOrigin: 'anonymous' });
        });
    }

    async function buildCanvas() {
        return new Promise(resolve => {
            const c = new fabric.Canvas('printCanvas', { selection: false, backgroundColor: '#ffffff' });
            c.setWidth(W); c.setHeight(H);
            const src = design && Object.keys(design).length ? design : { objects: [] };
            c.loadFromJSON(src, async function () {
                const objs = c.getObjects().slice();
                for (const o of objs) {
                    const key = o.fieldKey;
                    if (!key) {
                        if (isText(o) && typeof o.text === 'string' && o.text.indexOf('{{') !== -1) { o.set('text', substituteTokens(o.text)); }
                        continue;
                    }
                    if (isText(o)) { o.set('text', String(fields[key] != null ? fields[key] : '')); }
                    else {
                        let url = key === 'cert.qr' ? qrDataUrl() : (images[key] || null);
                        if (url) { try { await replaceWithImage(c, o, url); } catch (e) {} }
                    }
                }
                // fit display
                const wrapW = document.getElementById('printCanvas').parentElement.clientWidth - 30;
                const z = Math.min(1, wrapW / W);
                c.setZoom(z); c.setDimensions({ width: W * z, height: H * z });
                c.renderAll();
                resolve(c);
            });
        });
    }

    let canvasRef = null;
    async function preview() { if (canvasRef) { canvasRef.dispose(); } canvasRef = await buildCanvas(); }
    function alertBox(type, msg) { const a = document.getElementById('genAlert'); a.className = 'alert alert-' + type; a.textContent = msg; a.classList.remove('d-none'); }

    function exportPngDataUrl() {
        // reset zoom to 1 for full-resolution export
        canvasRef.setZoom(1); canvasRef.setDimensions({ width: W, height: H }); canvasRef.renderAll();
        const png = canvasRef.toDataURL({ format: 'png', multiplier: 2 });
        // restore display zoom
        const wrapW = document.getElementById('printCanvas').parentElement.clientWidth - 30;
        const z = Math.min(1, wrapW / W); canvasRef.setZoom(z); canvasRef.setDimensions({ width: W * z, height: H * z }); canvasRef.renderAll();
        return png;
    }

    document.getElementById('btnPreview').onclick = preview;

    document.getElementById('btnGenerate').onclick = function () {
        const btn = this; btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Generating…';
        // 1) server enforces approval + lock and counts the generation
        fetch(hitUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(async ({ ok, j }) => {
                if (!ok || !j.success) { alertBox('danger', j.message || 'Cannot generate.'); btn.disabled = false; btn.innerHTML = '<i class="ri-download-2-line me-1"></i>Generate &amp; download PDF'; return; }
                document.getElementById('genCount').textContent = j.count;
                if (!canvasRef) { await preview(); }
                const png = exportPngDataUrl();
                // 2) build PDF sized to the canvas
                try {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({ orientation: orient === 'portrait' ? 'portrait' : 'landscape', unit: 'pt', format: 'a4' });
                    const pw = pdf.internal.pageSize.getWidth(), ph = pdf.internal.pageSize.getHeight();
                    pdf.addImage(png, 'PNG', 0, 0, pw, ph);
                    pdf.save(serial.replace(/[^A-Za-z0-9_-]/g, '_') + '.pdf');
                } catch (e) { alertBox('warning', 'PDF export failed, downloading image instead.'); const a = document.createElement('a'); a.href = png; a.download = serial.replace(/[^A-Za-z0-9_-]/g, '_') + '.png'; a.click(); }
                // 3) save a copy server-side (best effort)
                fetch(storeUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: JSON.stringify({ image: png }) }).catch(() => {});
                alertBox('success', j.message + ' Copy #' + j.count + '.');
                btn.disabled = false; btn.innerHTML = '<i class="ri-download-2-line me-1"></i>Generate &amp; download PDF';
            })
            .catch(() => { alertBox('danger', 'Generation failed.'); btn.disabled = false; btn.innerHTML = '<i class="ri-download-2-line me-1"></i>Generate &amp; download PDF'; });
    };

    preview();
    window.addEventListener('resize', function () { if (canvasRef) preview(); });
})();
</script>
@endsection
