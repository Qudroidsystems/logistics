{{-- resources/views/certificates/templates/designer.blade.php --}}
@extends('layouts.master')

@section('content')
@php $isNew = !$template->exists; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0"><i class="ri-award-line me-2 text-primary"></i>{{ $isNew ? 'New' : 'Edit' }} Certificate Template</h4>
            <div class="small text-muted">Drag items on the canvas. Fields in «guillemets» are filled per student when you generate.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('certificates.templates.index') }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Back</a>
            <button class="btn btn-primary btn-sm" id="cdSave"><i class="ri-save-line me-1"></i>Save template</button>
        </div>
    </div>

    <div id="cdAlert" class="alert d-none"></div>

    <div class="row g-3">
        {{-- Left: tools --}}
        <div class="col-lg-3">
            <div class="card"><div class="card-body">
                <h6 class="fw-semibold"><i class="ri-add-box-line me-1"></i>Add</h6>
                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-outline-secondary btn-sm" id="addHeading"><i class="ri-heading me-1"></i>Heading text</button>
                    <button class="btn btn-outline-secondary btn-sm" id="addText"><i class="ri-text me-1"></i>Body text</button>
                    <button class="btn btn-outline-secondary btn-sm" id="addRect"><i class="ri-rectangle-line me-1"></i>Box / line</button>
                    <label class="btn btn-outline-secondary btn-sm mb-0"><i class="ri-image-add-line me-1"></i>Image… <input type="file" id="addImage" accept="image/*" hidden></label>
                    <label class="btn btn-outline-secondary btn-sm mb-0"><i class="ri-gallery-line me-1"></i>Background… <input type="file" id="addBg" accept="image/*" hidden></label>
                </div>

                <h6 class="fw-semibold"><i class="ri-price-tag-3-line me-1"></i>Insert field</h6>
                <select class="form-select form-select-sm mb-2" id="fieldPicker">
                    <option value="">Choose a field…</option>
                    @foreach($catalog as $group => $items)
                        <optgroup label="{{ $group }}">
                            @foreach($items as $it)
                                <option value="{{ $it['key'] }}" data-label="{{ $it['label'] }}" data-image="{{ !empty($it['image']) ? 1 : 0 }}">{{ $it['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="small text-muted">Text fields drop as «Label»; image fields (photo, logo, QR) drop as a placeholder box that fills at generation.</div>
                <div class="small text-muted mt-2">Tip: inside any text you can also type inline tokens like <code>{{'{{'}}student.name{{'}}'}}</code> or <code>{{'{{'}}testimonial.conduct{{'}}'}}</code> — great for testimonial letters.</div>
            </div></div>

            <div class="card mt-3"><div class="card-body">
                <h6 class="fw-semibold"><i class="ri-settings-3-line me-1"></i>Template settings</h6>
                <label class="form-label small mb-1">Name *</label>
                <input class="form-control form-control-sm mb-2" id="tName" value="{{ $template->name }}" maxlength="150">
                <label class="form-label small mb-1">Type</label>
                <select class="form-select form-select-sm mb-2" id="tKind">
                    <option value="certificate" @selected(($template->kind ?? 'certificate')==='certificate')>Certificate</option>
                    <option value="testimonial" @selected(($template->kind ?? '')==='testimonial')>Testimonial (leaving)</option>
                </select>
                <label class="form-label small mb-1">Description</label>
                <input class="form-control form-control-sm mb-2" id="tDesc" value="{{ $template->description }}" maxlength="500">
                <label class="form-label small mb-1">Orientation</label>
                <select class="form-select form-select-sm mb-2" id="tOrient">
                    <option value="landscape" @selected($template->orientation==='landscape')>Landscape (A4)</option>
                    <option value="portrait" @selected($template->orientation==='portrait')>Portrait (A4)</option>
                </select>
                <label class="form-label small mb-1">Serial prefix</label>
                <input class="form-control form-control-sm mb-2" id="tPrefix" value="{{ $template->serial_prefix ?: 'CERT' }}" maxlength="30">
                <label class="form-label small mb-1">Generation limit / student <span class="text-muted">(blank = unlimited)</span></label>
                <input type="number" class="form-control form-control-sm mb-2" id="tLimit" value="{{ $template->generation_limit }}" min="0" max="1000">
                <div class="form-check mb-1"><input class="form-check-input" type="checkbox" id="tApproval" @checked($template->requires_approval)><label class="form-check-label small" for="tApproval">Require approval before issuing</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" id="tActive" @checked($isNew ? true : $template->is_active)><label class="form-check-label small" for="tActive">Active</label></div>
            </div></div>
        </div>

        {{-- Center: canvas --}}
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light" id="zoomOut"><i class="ri-zoom-out-line"></i></button>
                        <button class="btn btn-light" id="zoomFit">Fit</button>
                        <button class="btn btn-light" id="zoomIn"><i class="ri-zoom-in-line"></i></button>
                    </div>
                    <div class="small text-muted" id="cdDims"></div>
                </div>
                <div id="canvasWrap" style="overflow:auto;background:#e9ecef;border-radius:8px;padding:14px;text-align:center;max-height:70vh">
                    <canvas id="certCanvas" style="box-shadow:0 2px 16px rgba(0,0,0,.15);background:#fff"></canvas>
                </div>
            </div></div>
        </div>

        {{-- Right: properties --}}
        <div class="col-lg-3">
            <div class="card"><div class="card-body" id="propPanel">
                <h6 class="fw-semibold"><i class="ri-edit-2-line me-1"></i>Selected item</h6>
                <div id="noSelection" class="small text-muted">Click an item on the canvas to edit it.</div>
                <div id="propControls" class="d-none">
                    <div id="textProps">
                        <label class="form-label small mb-1">Text</label>
                        <textarea class="form-control form-control-sm mb-2" id="pText" rows="2"></textarea>
                        <div class="row g-1 mb-2">
                            <div class="col-7"><label class="form-label small mb-1">Font</label>
                                <select class="form-select form-select-sm" id="pFont">
                                    <option>Helvetica</option><option>Arial</option><option>Times New Roman</option><option>Georgia</option><option>Garamond</option><option>Courier New</option><option>Verdana</option><option>Brush Script MT</option>
                                </select></div>
                            <div class="col-5"><label class="form-label small mb-1">Size</label><input type="number" class="form-control form-control-sm" id="pSize" min="6" max="200"></div>
                        </div>
                        <div class="d-flex gap-1 mb-2">
                            <button class="btn btn-outline-secondary btn-sm" id="pBold"><i class="ri-bold"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" id="pItalic"><i class="ri-italic"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" id="pUnder"><i class="ri-underline"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" id="pAlignL"><i class="ri-align-left"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" id="pAlignC"><i class="ri-align-center"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" id="pAlignR"><i class="ri-align-right"></i></button>
                        </div>
                        <label class="form-label small mb-1">Colour</label>
                        <input type="color" class="form-control form-control-color form-control-sm mb-2" id="pColor" value="#111111">
                    </div>
                    <hr class="my-2">
                    <div class="d-flex flex-wrap gap-1">
                        <button class="btn btn-outline-secondary btn-sm" id="pFront" title="Bring front"><i class="ri-bring-to-front"></i></button>
                        <button class="btn btn-outline-secondary btn-sm" id="pBack" title="Send back"><i class="ri-send-to-back"></i></button>
                        <button class="btn btn-outline-secondary btn-sm" id="pDup" title="Duplicate"><i class="ri-file-copy-line"></i></button>
                        <button class="btn btn-outline-danger btn-sm" id="pDelete" title="Delete"><i class="ri-delete-bin-line"></i></button>
                    </div>
                </div>
            </div></div>
        </div>
    </div>
</div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script>
(function () {
    const CSRF = '{{ csrf_token() }}';
    const uploadUrl = '{{ route('certificates.assets.upload') }}';
    const isNew = {{ $isNew ? 'true' : 'false' }};
    const storeUrl = '{{ route('certificates.templates.store') }}';
    const updateUrl = isNew ? null : '{{ $isNew ? '' : route('certificates.templates.update', $template) }}';
    const PRESET = { landscape: [1123, 794], portrait: [794, 1123] };
    let W = {{ (int) ($template->width ?: 1123) }}, H = {{ (int) ($template->height ?: 794) }};
    let bgPath = @json($template->background_path);

    const canvas = new fabric.Canvas('certCanvas', { preserveObjectStacking: true, backgroundColor: '#ffffff' });
    let displayScale = 1;

    function applySize() {
        canvas.setWidth(W); canvas.setHeight(H);
        fitZoom();
        document.getElementById('cdDims').textContent = W + ' × ' + H + ' px';
    }
    function fitZoom() {
        const avail = document.getElementById('canvasWrap').clientWidth - 30;
        displayScale = Math.min(1, avail / W);
        canvas.setZoom(displayScale);
        canvas.setDimensions({ width: W * displayScale, height: H * displayScale });
    }
    function setZoom(z) {
        displayScale = Math.max(0.15, Math.min(2, z));
        canvas.setZoom(displayScale);
        canvas.setDimensions({ width: W * displayScale, height: H * displayScale });
    }

    // ── load existing design ──
    const existing = @json($template->design ? json_decode($template->design) : null);
    if (existing) {
        canvas.loadFromJSON(existing, function () { applySize(); canvas.renderAll(); });
    } else {
        applySize();
    }

    // ── add helpers ──
    function addText(text, opts) {
        const t = new fabric.Textbox(text, Object.assign({ left: W/2 - 150, top: H/2 - 20, width: 300, fontSize: 28, fill: '#111111', fontFamily: 'Helvetica', textAlign: 'center' }, opts || {}));
        canvas.add(t).setActiveObject(t); canvas.renderAll();
    }
    document.getElementById('addHeading').onclick = () => addText('Heading', { fontSize: 48, fontWeight: 'bold', top: 120 });
    document.getElementById('addText').onclick = () => addText('Text', { fontSize: 24 });
    document.getElementById('addRect').onclick = () => {
        const r = new fabric.Rect({ left: W/2-120, top: H/2-40, width: 240, height: 80, fill: 'rgba(0,0,0,0)', stroke: '#333', strokeWidth: 2 });
        canvas.add(r).setActiveObject(r); canvas.renderAll();
    };

    document.getElementById('fieldPicker').onchange = function () {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) return;
        const key = opt.value, label = opt.getAttribute('data-label'), isImage = opt.getAttribute('data-image') === '1';
        if (isImage) {
            const box = new fabric.Rect({ left: W/2-90, top: H/2-90, width: 180, height: 180, fill: 'rgba(37,99,235,.08)', stroke: '#2563eb', strokeDashArray: [6,4], strokeWidth: 2 });
            box.fieldKey = key; box.fieldLabel = label;
            canvas.add(box).setActiveObject(box); canvas.renderAll();
        } else {
            const t = new fabric.Textbox('«' + label + '»', { left: W/2-150, top: H/2-16, width: 300, fontSize: 28, fill: '#111111', fontFamily: 'Helvetica', textAlign: 'center' });
            t.fieldKey = key;
            canvas.add(t).setActiveObject(t); canvas.renderAll();
        }
        this.value = '';
    };

    function uploadImage(file, cb) {
        const fd = new FormData(); fd.append('file', file);
        fetch(uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
            .then(r => r.json()).then(j => { if (j.success) cb(j.url, j.path); else alert(j.message || 'Upload failed'); })
            .catch(() => alert('Upload failed'));
    }
    document.getElementById('addImage').onchange = function () {
        if (!this.files[0]) return;
        uploadImage(this.files[0], (url) => {
            fabric.Image.fromURL(url, img => { img.set({ left: W/2-100, top: H/2-100 }); if (img.width > 300) img.scaleToWidth(300); canvas.add(img).setActiveObject(img); canvas.renderAll(); }, { crossOrigin: 'anonymous' });
        });
        this.value = '';
    };
    document.getElementById('addBg').onchange = function () {
        if (!this.files[0]) return;
        uploadImage(this.files[0], (url, path) => {
            bgPath = path;
            fabric.Image.fromURL(url, img => {
                img.scaleToWidth(W); img.scaleToHeight(H);
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: W/img.width, scaleY: H/img.height });
            }, { crossOrigin: 'anonymous' });
        });
        this.value = '';
    };

    // ── orientation ──
    document.getElementById('tOrient').onchange = function () {
        const p = PRESET[this.value] || PRESET.landscape; W = p[0]; H = p[1]; applySize();
    };

    // ── zoom ──
    document.getElementById('zoomIn').onclick = () => setZoom(displayScale + 0.1);
    document.getElementById('zoomOut').onclick = () => setZoom(displayScale - 0.1);
    document.getElementById('zoomFit').onclick = fitZoom;
    window.addEventListener('resize', fitZoom);

    // ── properties ──
    const propControls = document.getElementById('propControls'), noSel = document.getElementById('noSelection'), textProps = document.getElementById('textProps');
    function refreshProps() {
        const o = canvas.getActiveObject();
        if (!o) { propControls.classList.add('d-none'); noSel.classList.remove('d-none'); return; }
        noSel.classList.add('d-none'); propControls.classList.remove('d-none');
        const isText = o.type === 'textbox' || o.type === 'text' || o.type === 'i-text';
        textProps.style.display = isText ? '' : 'none';
        if (isText) {
            document.getElementById('pText').value = o.text || '';
            document.getElementById('pFont').value = o.fontFamily || 'Helvetica';
            document.getElementById('pSize').value = Math.round(o.fontSize || 24);
            document.getElementById('pColor').value = (o.fill && o.fill[0] === '#') ? o.fill : '#111111';
        }
    }
    canvas.on('selection:created', refreshProps);
    canvas.on('selection:updated', refreshProps);
    canvas.on('selection:cleared', refreshProps);

    function withActive(fn) { const o = canvas.getActiveObject(); if (o) { fn(o); canvas.renderAll(); } }
    document.getElementById('pText').oninput = function () { withActive(o => o.set('text', this.value)); };
    document.getElementById('pFont').onchange = function () { withActive(o => o.set('fontFamily', this.value)); };
    document.getElementById('pSize').oninput = function () { withActive(o => o.set('fontSize', parseInt(this.value || 24, 10))); };
    document.getElementById('pColor').oninput = function () { withActive(o => o.set('fill', this.value)); };
    document.getElementById('pBold').onclick = () => withActive(o => o.set('fontWeight', o.fontWeight === 'bold' ? 'normal' : 'bold'));
    document.getElementById('pItalic').onclick = () => withActive(o => o.set('fontStyle', o.fontStyle === 'italic' ? 'normal' : 'italic'));
    document.getElementById('pUnder').onclick = () => withActive(o => o.set('underline', !o.underline));
    document.getElementById('pAlignL').onclick = () => withActive(o => o.set('textAlign', 'left'));
    document.getElementById('pAlignC').onclick = () => withActive(o => o.set('textAlign', 'center'));
    document.getElementById('pAlignR').onclick = () => withActive(o => o.set('textAlign', 'right'));
    document.getElementById('pFront').onclick = () => withActive(o => o.bringToFront());
    document.getElementById('pBack').onclick = () => withActive(o => o.sendToBack());
    document.getElementById('pDelete').onclick = () => withActive(o => canvas.remove(o));
    document.getElementById('pDup').onclick = () => withActive(o => o.clone(c => { c.set({ left: (o.left||0)+20, top: (o.top||0)+20 }); if (o.fieldKey) c.fieldKey = o.fieldKey; canvas.add(c).setActiveObject(c); }, ['fieldKey','fieldLabel']));
    document.addEventListener('keydown', e => { if ((e.key === 'Delete' || e.key === 'Backspace') && canvas.getActiveObject() && !/INPUT|TEXTAREA/.test(document.activeElement.tagName)) { withActive(o => canvas.remove(o)); } });

    // ── save ──
    function alertBox(type, msg) { const a = document.getElementById('cdAlert'); a.className = 'alert alert-' + type; a.textContent = msg; a.classList.remove('d-none'); setTimeout(() => a.classList.add('d-none'), 4000); }
    document.getElementById('cdSave').onclick = function () {
        const name = document.getElementById('tName').value.trim();
        if (!name) { alertBox('warning', 'Please enter a template name.'); return; }
        const design = JSON.stringify(canvas.toJSON(['fieldKey', 'fieldLabel']));
        const payload = {
            name: name,
            kind: document.getElementById('tKind').value,
            description: document.getElementById('tDesc').value,
            orientation: document.getElementById('tOrient').value,
            width: W, height: H,
            serial_prefix: document.getElementById('tPrefix').value,
            generation_limit: document.getElementById('tLimit').value,
            requires_approval: document.getElementById('tApproval').checked ? 1 : 0,
            is_active: document.getElementById('tActive').checked ? 1 : 0,
            design: design,
            background_path: bgPath || ''
        };
        const btn = this; btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving…';
        fetch(isNew ? storeUrl : updateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(j => {
            btn.disabled = false; btn.innerHTML = '<i class="ri-save-line me-1"></i>Save template';
            if (j.success) { alertBox('success', j.message); if (j.redirect) setTimeout(() => location.href = j.redirect, 700); }
            else alertBox('danger', j.message || 'Save failed');
        }).catch(() => { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line me-1"></i>Save template'; alertBox('danger', 'Save failed'); });
    };
})();
</script>
@endsection
