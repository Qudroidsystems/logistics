<form method="POST" action="{{ $action }}" class="row g-2 mt-1">@csrf @if($t) @method('PUT') @endif
    <div class="col-7"><input name="name" class="form-control form-control-sm" value="{{ $t->name ?? '' }}" placeholder="Name" required></div>
    <div class="col-5"><input name="code" class="form-control form-control-sm" value="{{ $t->code ?? '' }}" placeholder="CODE" required></div>
    <div class="col-4"><input name="days_per_year" type="number" step="0.5" min="0" class="form-control form-control-sm" value="{{ $t->days_per_year ?? 0 }}" title="Days a year (0 = no limit)"></div>
    <div class="col-4"><select name="gender" class="form-select form-select-sm"><option value="">Everyone</option><option value="female" @selected(($t->gender ?? '') === 'female')>Women</option><option value="male" @selected(($t->gender ?? '') === 'male')>Men</option></select></div>
    <div class="col-4"><input name="color" type="color" class="form-control form-control-sm form-control-color w-100" value="{{ $t->color ?? '#0f766e' }}"></div>
    <div class="col-12 d-flex flex-wrap gap-3 small">
        <label class="form-check"><input type="checkbox" class="form-check-input" name="paid" value="1" @checked($t->paid ?? true)> <span class="form-check-label">Paid</span></label>
        <label class="form-check"><input type="checkbox" class="form-check-input" name="requires_document" value="1" @checked($t->requires_document ?? false)> <span class="form-check-label">Document needed</span></label>
        <label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($t->is_active ?? true)> <span class="form-check-label">Active</span></label>
    </div>
    <input type="hidden" name="carry_over_max" value="{{ $t->carry_over_max ?? 0 }}">
    <div class="col-12"><button class="btn btn-sm btn-light">Save</button></div>
</form>
