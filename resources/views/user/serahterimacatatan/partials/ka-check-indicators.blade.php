@php
    $checkIndicators = [
        'semboyan21' => 'Semboyan 21',
        'genta' => 'Genta',
        'pintu' => 'Pintu perlintasan',
        'semboyan35' => 'Semboyan 35',
    ];

    $formatCheckIndicator = static function ($value): string {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            'yes', 'ya', 'centang' => '✓',
            'no', 'tidak', 'silang' => '✗',
            default => (string) $value,
        };
    };
@endphp

<dl class="mt-3 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
    @foreach ($checkIndicators as $field => $label)
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
            <dt class="text-slate-500">{{ $label }}</dt>
            <dd class="mt-1 min-h-5 font-bold text-slate-800">
                {{ $formatCheckIndicator(data_get($ka, $field)) }}
            </dd>
        </div>
    @endforeach
</dl>
