@php
    $items = collect(json_decode($delivery->items ?: '[]'));
    $mapsUrl = $delivery->latitude && $delivery->longitude
        ? 'https://www.google.com/maps?q='.$delivery->latitude.','.$delivery->longitude
        : null;
    $statusLabel = match ($delivery->status) {
        'location_set' => 'Siap Diantar',
        'in_delivery' => 'Sedang Diantar',
        'completed' => 'Selesai',
        default => ucfirst((string) $delivery->status),
    };
@endphp

<article class="delivery-card">
    <div class="delivery-card-body">
        <div class="delivery-title">{{ $delivery->namapasien ?: 'Pasien' }}</div>
        <div class="delivery-meta">
            {{ $delivery->noorder }} | RM {{ $delivery->nocm ?: '-' }}<br>
            Status: <strong>{{ $statusLabel }}</strong>
        </div>

        <div class="delivery-address">
            <strong>Alamat</strong><br>
            {{ $delivery->address_detail ?: '-' }}
            @if ($delivery->recipient_name)
                <br>Penerima: {{ $delivery->recipient_name }}
            @endif
            @if ($delivery->recipient_phone)
                <br>HP: {{ $delivery->recipient_phone }}
            @endif
        </div>

        @if ($items->isNotEmpty())
            <ol class="medicine-list">
                @foreach ($items->take(4) as $item)
                    <li>{{ $item->namaproduk ?? 'Obat' }}{{ ! empty($item->jumlah) ? ' ('.$item->jumlah.')' : '' }}</li>
                @endforeach
            </ol>
        @endif

        <div class="delivery-row">
            <span>Jarak</span>
            <strong>{{ $delivery->distance_km !== null ? number_format((float) $delivery->distance_km, 2, ',', '.').' km' : '-' }}</strong>
        </div>
        <div class="delivery-row">
            <span>Billing</span>
            <strong>{{ $delivery->delivery_fee !== null ? 'Rp '.number_format((float) $delivery->delivery_fee, 0, ',', '.') : '-' }}</strong>
        </div>

        @if ($mode === 'history')
            <div class="delivery-row">
                <span>Diambil</span>
                <strong>{{ $delivery->taken_at ? \Carbon\Carbon::parse($delivery->taken_at)->format('d-m-Y H:i') : '-' }}</strong>
            </div>
            <div class="delivery-row">
                <span>Selesai</span>
                <strong>{{ $delivery->completed_at ? \Carbon\Carbon::parse($delivery->completed_at)->format('d-m-Y H:i') : '-' }}</strong>
            </div>
        @endif

        @if ($mode === 'available')
            <div class="delivery-actions {{ $mapsUrl ? '' : 'single' }}">
                @if ($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="delivery-btn light">Maps</a>
                @endif
                <form method="POST" action="{{ route('pharmacy-deliveries.take', $delivery->id) }}">
                    @csrf
                    <button type="submit" class="delivery-btn primary" style="width:100%;">Ambil</button>
                </form>
            </div>
        @elseif ($mode === 'mine')
            <div class="delivery-actions {{ $mapsUrl ? '' : 'single' }}">
                @if ($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="delivery-btn light">Maps</a>
                @endif
                <form method="POST" action="{{ route('pharmacy-deliveries.complete', $delivery->id) }}" onsubmit="return confirm('Tandai pengantaran ini selesai?')">
                    @csrf
                    <button type="submit" class="delivery-btn success" style="width:100%;">Selesai</button>
                </form>
            </div>
        @elseif ($mapsUrl)
            <div class="delivery-actions single">
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="delivery-btn light">Lihat Maps</a>
            </div>
        @endif
    </div>
</article>
