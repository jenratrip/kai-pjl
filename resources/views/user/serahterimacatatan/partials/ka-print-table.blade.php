@php
    $rows = collect($rows ?? [])
        ->filter(fn ($ka) => is_array($ka))
        ->values();

    $indicators = [
        'genta' => 'Genta',
        'semboyan21' => 'S.21',
        'pintu' => 'Pintu perlintasan',
        'semboyan35' => 'S.35',
    ];
@endphp

<table class="serah-terima-ka-print-table {{ ! empty($showDetails) ? 'serah-terima-ka-print-table--details' : 'serah-terima-ka-print-table--names' }}">
    @if (! empty($showDetails))
        <colgroup>
            <col class="serah-terima-ka-print-col-number">
            <col class="serah-terima-ka-print-col-train-number">
            <col class="serah-terima-ka-print-col-train-name">
            <col class="serah-terima-ka-print-col-time">
            <col class="serah-terima-ka-print-col-status">
            <col class="serah-terima-ka-print-col-checks">
            <col class="serah-terima-ka-print-col-notes">
        </colgroup>
    @else
        <colgroup>
            <col class="serah-terima-ka-print-col-simple-number">
            <col class="serah-terima-ka-print-col-simple-name">
        </colgroup>
    @endif
    <thead>
        <tr>
            @if (! empty($showDetails))
                <th scope="col">No.</th>
            @endif
            <th scope="col">Nomor KA</th>
            <th scope="col">Nama KA</th>
            @if (! empty($showDetails))
                <th scope="col">Waktu</th>
                <th scope="col">Kondisi</th>
                <th scope="col">Pemeriksaan</th>
                <th scope="col">Keterangan</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $ka)
            <tr>
                @if (! empty($showDetails))
                    <td>{{ $loop->iteration }}</td>
                @endif
                <td>{{ data_get($ka, 'nomor') ?: '-' }}</td>
                <td>{{ data_get($ka, 'nama') }}</td>
                @if (! empty($showDetails))
                    <td>
                        @if (data_get($ka, 'jadwal'))
                            <span>Jadwal: {{ data_get($ka, 'jadwal') }}</span>
                        @endif
                        @if (data_get($ka, 'aktual'))
                            <br><span>Aktual: {{ data_get($ka, 'aktual') }}</span>
                        @endif
                    </td>
                    <td>{{ data_get($ka, 'status') ?: $statusLabel }}</td>
                    <td>
                        @foreach ($indicators as $field => $label)
                            @php
                                $value = mb_strtolower(trim((string) data_get($ka, $field, '')));
                                $symbol = match ($value) {
                                    'yes', 'ya', 'centang', '✓' => '✓',
                                    'no', 'tidak', 'silang', '✕', '✗' => '✗',
                                    default => '',
                                };
                            @endphp
                            <span>{{ $label }}: {{ $symbol }}</span>@if (! $loop->last)<br>@endif
                        @endforeach
                    </td>
                    <td>{{ data_get($ka, 'keterangan') }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ ! empty($showDetails) ? 7 : 2 }}">Tidak ada data KA pada kategori ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>
