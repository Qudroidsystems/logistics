{{-- Shared by the customer and provider negotiation pages. Needs $messages (with decoded 'offer'), $me (user id), $naira. --}}
<x-cb.card title="Conversation" icon="ri-message-3-line">
    @forelse($messages as $m)
        @php $mine = (int) $m['sender_id'] === (int) $me; @endphp
        <div class="d-flex {{ $mine ? 'justify-content-end' : '' }} mb-2">
            <div class="p-2 rounded {{ $mine ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:80%">
                @if($m['kind'] === 'counter_offer')
                    <div class="fw-semibold">Offer: {{ $naira($m['offer']['price'] ?? 0) }}@if(!empty($m['offer']['tip'])) + {{ $naira($m['offer']['tip']) }} tip @endif</div>
                    @if(!empty($m['offer']['note']))<div class="small">{{ $m['offer']['note'] }}</div>@endif
                    @if($m['body'])<div class="small">{{ $m['body'] }}</div>@endif
                @elseif($m['kind'] === 'accept') <em>Accepted the offer</em>
                @elseif($m['kind'] === 'reject') <em>Declined</em>
                @else {{ $m['body'] }} @endif
                <div class="small opacity-75">{{ \Illuminate\Support\Carbon::parse($m['created_at'])->format('d M H:i') }}</div>
            </div>
        </div>
    @empty
        <span class="text-muted">No messages yet.</span>
    @endforelse
</x-cb.card>
