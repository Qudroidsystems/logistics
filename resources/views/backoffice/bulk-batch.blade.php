@extends('layouts.master')

@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0);
    $badge = ['ready' => ['Ready', 'bg-success-subtle text-success'], 'invalid' => ['Needs fixing', 'bg-danger-subtle text-danger'],
              'booked' => ['Booked', 'bg-success'], 'failed' => ['Not booked', 'bg-danger']];
    $bad = $b->row_count - $b->ok_count;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Bulk booking" icon="ri-file-list-3-line" :subtitle="($b->filename ?? 'Upload').' · '.$b->row_count.' rows'" :back="route('provider.bulk')" back-label="Bulk booking" />
    @include('provider._flash')

    @if($b->status === 'checked')
        <div class="cb-banner {{ $bad ? 'warning' : 'info' }}"><i class="ri-information-line"></i><div>
            {{ $b->ok_count }} of {{ $b->row_count }} rows are ready to book.
            @if($bad) {{ $bad }} {{ $bad === 1 ? 'row needs' : 'rows need' }} fixing and will be left out; fix them in your sheet and upload those rows again. @endif
            Payment: {{ \App\Modules\Backoffice\ManualBookingService::PAYMENTS[$defaults['payment']] ?? $defaults['payment'] }}.
        </div></div>
        <div class="d-flex gap-2 mb-3">
            @if($b->ok_count)
                <form method="POST" action="{{ route('provider.bulk.book', $b->public_id) }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Booking, please wait…'">@csrf
                    <button class="btn btn-primary">Book {{ $b->ok_count }} {{ $b->ok_count === 1 ? 'delivery' : 'deliveries' }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route('provider.bulk.discard', $b->public_id) }}">@csrf<button class="btn btn-light">Discard</button></form>
        </div>
    @elseif($b->status === 'booked')
        <div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ $b->booked_count }} booked{{ $b->failed_count ? ', '.$b->failed_count.' not booked' : '' }} on {{ \Illuminate\Support\Carbon::parse($b->booked_at)->format('j M Y, H:i') }}.</div></div>
    @elseif($b->status === 'booking')
        <div class="cb-banner info"><i class="ri-loader-4-line"></i><div>Booking is under way. Refresh this page in a moment.</div></div>
    @else
        <div class="cb-banner warning"><i class="ri-delete-bin-line"></i><div>This upload was discarded. Nothing was booked.</div></div>
    @endif

    <x-cb.card title="Rows" icon="ri-table-line" :count="count($rows)" :flush="true">
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Line</th><th>Reference</th><th>Drop-off</th><th>Receiver</th><th class="text-end">Price</th><th>Result</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
                @php $in = json_decode($r->input, true); $errs = $r->errors ? json_decode($r->errors, true) : []; @endphp
                <tr>
                    <td class="small text-muted">{{ $r->line }}</td>
                    <td class="small">{{ $r->reference ?? '–' }}</td>
                    <td class="small">{{ $in['dropoff']['line1'] ?? '–' }}</td>
                    <td class="small">{{ $in['dropoff']['contact_name'] ?? '' }}<div class="text-muted">{{ $in['dropoff']['contact_phone'] ?? '' }}</div></td>
                    <td class="text-end small">{{ $r->price ? $naira($r->price) : (isset($in['price']) ? $naira($in['price']) : 'Price list') }}</td>
                    <td class="small">
                        <span class="badge {{ $badge[$r->status][1] }}">{{ $badge[$r->status][0] }}</span>
                        @if($r->shipment_public_id)<a href="{{ route('provider.job', $r->shipment_public_id) }}" class="ms-1">{{ $r->tracking_code }}</a>@endif
                        @foreach($errs as $e)<div class="text-danger">{{ $e }}</div>@endforeach
                        @if($r->status === 'failed' && $r->message)<div class="text-danger">{{ $r->message }}</div>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
