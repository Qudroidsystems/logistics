<form method="POST" action="{{ $action }}" class="row g-2 align-items-end">@csrf @if($method === 'PUT') @method('PUT') @endif
    <div class="col-md-3"><label class="small">Code</label><input name="code" class="form-control form-control-sm" value="{{ $item->code }}" {{ $item->is_system ? 'readonly' : '' }} required placeholder="EXAM_DUTY"></div>
    <div class="col-md-5"><label class="small">Name</label><input name="name" class="form-control form-control-sm" value="{{ $item->name }}" required></div>
    <div class="col-md-4"><label class="small">Type</label><select name="type" class="form-select form-select-sm"><option value="earning" @selected($item->type === 'earning')>Earning</option><option value="deduction" @selected($item->type === 'deduction')>Deduction</option></select></div>
    <div class="col-md-4"><label class="small">Worked out as</label><select name="calc" class="form-select form-select-sm">@foreach(\App\Models\PayItem::CALCS as $k => $l)<option value="{{ $k }}" @selected($item->calc === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="small">Default amount ₦</label><input name="default_amount" type="number" step="0.01" min="0" class="form-control form-control-sm" value="{{ $item->default_amount }}"></div>
    <div class="col-md-4"><label class="small">Default rate %</label><input name="default_rate" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" value="{{ $item->default_rate }}"></div>
    <div class="col-12 d-flex flex-wrap gap-3 small">
        <label class="form-check"><input type="checkbox" class="form-check-input" name="taxable" value="1" @checked($item->taxable)> <span class="form-check-label">Taxed (earnings)</span></label>
        <label class="form-check"><input type="checkbox" class="form-check-input" name="pensionable" value="1" @checked($item->pensionable)> <span class="form-check-label">Counts for pension</span></label>
        <label class="form-check"><input type="checkbox" class="form-check-input" name="one_off" value="1" @checked($item->one_off)> <span class="form-check-label">One-off (lump-sum tax)</span></label>
        <label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($item->is_active ?? true)> <span class="form-check-label">Active</span></label>
    </div>
    <div class="col-12"><input name="description" class="form-control form-control-sm mb-2" placeholder="Description (optional)" value="{{ $item->description }}"><button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save</button></div>
</form>
