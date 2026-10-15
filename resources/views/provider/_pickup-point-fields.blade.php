@php $f = fn ($k, $d = '') => old($k, $v->$k ?? $d); @endphp
<div class="col-4"><input name="code" class="form-control form-control-sm" placeholder="Code (IKJ01)" maxlength="12" required value="{{ $f('code') }}"></div>
<div class="col-8"><input name="name" class="form-control form-control-sm" placeholder="Name (Ikeja office)" maxlength="120" required value="{{ $f('name') }}"></div>
<div class="col-12"><input name="line1" class="form-control form-control-sm" placeholder="Address" maxlength="255" required value="{{ $f('line1') }}"></div>
<div class="col-12"><input name="landmark" class="form-control form-control-sm" placeholder="Landmark (optional)" maxlength="255" value="{{ $f('landmark') }}"></div>
<div class="col-6"><input name="lat" class="form-control form-control-sm" placeholder="Latitude" inputmode="decimal" required value="{{ $f('lat') }}"></div>
<div class="col-6"><input name="lng" class="form-control form-control-sm" placeholder="Longitude" inputmode="decimal" required value="{{ $f('lng') }}"></div>
<div class="col-6"><input name="phone" class="form-control form-control-sm" placeholder="Phone" maxlength="24" value="{{ $f('phone') }}"></div>
<div class="col-6"><div class="input-group input-group-sm"><input type="number" name="hold_days" class="form-control" min="1" max="30" value="{{ $f('hold_days', 5) }}"><span class="input-group-text">days kept</span></div></div>
<div class="col-12"><input name="opening_hours" class="form-control form-control-sm" placeholder="Opening hours (Mon to Sat, 8am to 6pm)" maxlength="160" value="{{ $f('opening_hours') }}"></div>
