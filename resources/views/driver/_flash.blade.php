@if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
