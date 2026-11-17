@extends('layouts.user')

@section('content')
    @php
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        /*
        |--------------------------------------------------------------------------
        | Persiapan Data Jadwal untuk Alpine.js
        |--------------------------------------------------------------------------
        */

        $scheduleCollection = collect($schedules ?? []);

        $schedulesByDate = $scheduleCollection
            ->filter(function ($item) {
                return ($item['type'] ?? '') !== 'holiday';
            })
            ->groupBy('date')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'date' => $item['date'] ?? '',
                        'date_display' => $item['date_display'] ?? '',
                        'day_name' => $item['day_name'] ?? '',
                        'jpl_code' => $item['jpl_code'] ?? '-',
                        'jpl_name' => $item['jpl_name'] ?? '-',
                        'shift_name' => $item['shift_name'] ?? '-',
                        'shift_type' => $item['shift_type'] ?? '',
                        'jam_mulai' => $item['jam_mulai'] ?? '-',
                        'jam_selesai' => $item['jam_selesai'] ?? '-',
                        'status' => $item['status'] ?? '',
                        'status_display' => $item['status_display'] ?? '-',
                        'is_pengganti' => $item['is_pengganti'] ?? false,
                        'user_digantikan' => $item['user_digantikan'] ?? null,
                    ];
                })->values();
            })
            ->all();

        $holidaysByDate = $scheduleCollection
            ->filter(function ($item) {
                return ($item['type'] ?? '') === 'holiday';
            })
            ->pluck('date')
            ->mapWithKeys(function ($date) {
                return [$date => true];
            })
            ->all();

        $initialSelectedDate = data_get(
            $scheduleCollection->first(),
            'date',
            today()->toDateString()
        );
    @endphp

    <div x-data="jadwalDinasApp()" class="space-y-6 px-4 py-6 sm:px-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    Jadwal Saya
                </h1>

                <p class="mt-1 text-slate-600">
                    Dashboard jadwal dinas bulanan
                </p>
            </div>

            {{-- NAVIGASI TUKAR JADWAL --}}
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('user.tukar-jadwal-dinas') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-orange-300 bg-white px-4 py-2.5 text-sm font-semibold text-orange-700 transition hover:bg-orange-50"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                    </svg>
                    Permintaan Tukar
                </a>

                <a
                    href="{{ url('/user/tukar-jadwal-dinas/riwayat') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Tukar
                </a>
            </div>
        </div>

        {{-- PERIOD NAVIGATION --}}
        <div class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="month" class="mb-2 block text-sm font-medium text-slate-700">
                    Bulan
                </label>

                <select
                    id="month"
                    x-model="selectedMonth"
                    @change="navigateMonth()"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-slate-900"
                >
                    @foreach ($monthNames as $m => $name)
                        <option value="{{ $m }}" @selected($month === $m)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1">
                <label for="year" class="mb-2 block text-sm font-medium text-slate-700">
                    Tahun
                </label>

                <select
                    id="year"
                    x-model="selectedYear"
                    @change="navigateMonth()"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 text-slate-900"
                >
                    @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" @selected($year === $y)>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- SUMMARY STATISTICS --}}
        @if ($hasData)
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="rounded-lg border border-slate-200 bg-white p-4 text-center shadow-sm">
                    <p class="text-xs font-medium uppercase text-slate-600">Bertugas</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $summary['total_hari_bertugas'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">hari</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-4 text-center shadow-sm">
                    <p class="text-xs font-medium uppercase text-slate-600">Libur</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $summary['total_hari_libur'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">hari</p>
                </div>

                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-center shadow-sm">
                    <p class="text-xs font-medium uppercase text-orange-700">Pagi</p>
                    <p class="mt-2 text-3xl font-bold text-orange-600">
                        {{ $summary['shift_pagi'] }}
                    </p>
                    <p class="mt-1 text-xs text-orange-600">shift</p>
                </div>

                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-center shadow-sm">
                    <p class="text-xs font-medium uppercase text-yellow-700">Siang</p>
                    <p class="mt-2 text-3xl font-bold text-yellow-600">
                        {{ $summary['shift_siang'] }}
                    </p>
                    <p class="mt-1 text-xs text-yellow-600">shift</p>
                </div>

                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-center shadow-sm">
                    <p class="text-xs font-medium uppercase text-indigo-700">Malam</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">
                        {{ $summary['shift_malam'] }}
                    </p>
                    <p class="mt-1 text-xs text-indigo-600">shift</p>
                </div>
            </div>
        @endif

        {{-- CALENDAR SECTION --}}
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">

            @if (!$hasData)
                {{-- EMPTY STATE --}}
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <svg class="h-16 w-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>

                    <h3 class="mt-4 text-lg font-semibold text-slate-900">
                        Jadwal Belum Ada
                    </h3>

                    <p class="mt-2 text-slate-600">
                        Tidak ada jadwal dinas untuk
                        <strong>{{ $monthNames[$month] }} {{ $year }}</strong>
                    </p>
                </div>
            @else
                {{-- CALENDAR HEADER --}}
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100 px-4 py-4 sm:px-6">
                    <h2 class="text-2xl font-bold text-slate-900">
                        {{ $monthNames[$month] }} {{ $year }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Pilih tanggal untuk melihat detail jadwal dinas.
                    </p>
                </div>

                {{-- CALENDAR GRID --}}
                <div class="overflow-x-auto">
                    <div class="min-w-full p-4 sm:p-6">
                        <div class="mb-2 grid grid-cols-7 gap-2 sm:gap-3">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $day)
                                <div class="rounded-lg bg-slate-100 p-2 text-center text-xs font-bold text-slate-700 sm:p-3">
                                    {{ substr($day, 0, 3) }}
                                </div>
                            @endforeach
                        </div>

                        @foreach ($calendar as $week)
                            <div class="mb-2 grid grid-cols-7 gap-2 sm:gap-3">
                                @foreach ($week as $day)
                                    <div
                                        @click="selectDate('{{ $day['date'] }}')"
                                        :class="{
                                            'ring-2 ring-blue-500 ring-inset': selected === '{{ $day['date'] }}',
                                        }"
                                        @class([
                                            'relative min-h-[100px] rounded-lg border-2 p-2 cursor-pointer transition-all duration-200 sm:min-h-[120px] sm:p-3',
                                            'bg-slate-50 border-slate-200 text-slate-400' => !$day['is_current_month'],
                                            'border-slate-300 bg-white hover:border-blue-300 hover:shadow-md' => $day['is_current_month'] && !$day['is_today'] && !$day['is_holiday'] && !$day['has_schedule'],
                                            'border-blue-400 bg-blue-50 shadow-md' => $day['is_today'],
                                            'border-slate-400 bg-slate-100' => $day['is_holiday'],
                                        ])
                                    >
                                        <div class="flex items-start justify-between">
                                            <span
                                                class="text-sm font-semibold"
                                                @class([
                                                    'text-blue-600' => $day['is_today'],
                                                    'text-slate-900' => !$day['is_today'] && $day['is_current_month'],
                                                    'text-slate-400' => !$day['is_current_month'],
                                                ])
                                            >
                                                {{ $day['day'] }}
                                            </span>

                                            @if ($day['is_today'])
                                                <span class="rounded bg-blue-200 px-2 py-0.5 text-xs font-bold text-blue-600">
                                                    Hari ini
                                                </span>
                                            @endif
                                        </div>

                                        @if ($day['is_holiday'])
                                            <div class="mt-2 rounded bg-slate-200 px-2 py-1 text-center text-xs font-semibold text-slate-700">
                                                LIBUR
                                            </div>
                                        @else
                                            <div class="mt-2 space-y-1">
                                                @foreach ($day['shifts'] as $shift)
                                                    @php
                                                        $badgeClass = match ($shift['shift_type']) {
                                                            'pagi' => 'bg-orange-100 text-orange-700',
                                                            'siang' => 'bg-yellow-100 text-yellow-700',
                                                            'malam' => 'bg-indigo-100 text-indigo-700',
                                                            default => 'bg-slate-100 text-slate-700',
                                                        };

                                                        $shortName = match ($shift['shift_type']) {
                                                            'pagi' => 'P',
                                                            'siang' => 'S',
                                                            'malam' => 'M',
                                                            default => substr($shift['shift_name'], 0, 1),
                                                        };
                                                    @endphp

                                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $badgeClass }}">
                                                        {{ $shortName }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- DETAIL SECTION --}}
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-6 sm:px-6">
                    <h3 class="mb-4 text-lg font-bold text-slate-900">
                        Detail Jadwal
                    </h3>

                    <div id="detailContainer" class="space-y-4">
                        {{-- Diisi oleh Alpine.js --}}
                    </div>
                </div>

                {{-- SCHEDULE LIST --}}
                <div class="border-t border-slate-200">
                    <div class="px-4 py-6 sm:px-6">
                        <h3 class="mb-4 text-lg font-bold text-slate-900">
                            Daftar Jadwal Lengkap
                        </h3>

                        {{-- DESKTOP TABLE --}}
                        <div class="hidden overflow-x-auto md:block">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50">
                                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Tanggal</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Shift</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-900">JPL</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Jam</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                                        <th class="px-4 py-3 text-center font-semibold text-slate-900">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-200">
                                    @forelse ($schedules as $item)
                                        <tr
                                            class="cursor-pointer transition-colors hover:bg-slate-50"
                                            @click="selectDate('{{ $item['date'] }}')"
                                        >
                                            <td class="px-4 py-3 text-slate-900">
                                                {{ $item['date_display'] ?? $item['date'] }}
                                            </td>

                                            <td class="px-4 py-3">
                                                @php
                                                    $badgeClass = match ($item['shift_type']) {
                                                        'pagi' => 'bg-orange-100 text-orange-700',
                                                        'siang' => 'bg-yellow-100 text-yellow-700',
                                                        'malam' => 'bg-indigo-100 text-indigo-700',
                                                        'libur' => 'bg-slate-100 text-slate-700',
                                                        default => 'bg-slate-100 text-slate-700',
                                                    };
                                                @endphp

                                                <span class="inline-flex items-center rounded-full {{ $badgeClass }} px-3 py-1 font-semibold">
                                                    {{ $item['shift_name'] }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3">
                                                {{ $item['jpl_code'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 font-mono text-slate-600">
                                                {{ $item['jam_mulai'] ?? '-' }}
                                                -
                                                {{ $item['jam_selesai'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                @php
                                                    $statusClass = match ($item['status']) {
                                                        'dijadwalkan' => 'bg-blue-100 text-blue-700',
                                                        'berlangsung' => 'bg-green-100 text-green-700',
                                                        'selesai' => 'bg-gray-100 text-gray-700',
                                                        'libur' => 'bg-slate-100 text-slate-700',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    };
                                                @endphp

                                                <span class="inline-flex rounded-full {{ $statusClass }} px-2.5 py-0.5 text-xs font-semibold">
                                                    {{ $item['status_display'] }}
                                                </span>
                                            </td>

                                            {{-- TOMBOL AJUKAN TUKAR --}}
                                            <td class="px-4 py-3 text-center" @click.stop>
                                                @if (($item['status'] ?? '') === 'dijadwalkan' && ($item['type'] ?? '') !== 'holiday')
                                                    <a
                                                        href="{{ route('user.tukar-jadwal-dinas.create') }}"
                                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-orange-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-orange-600"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                                                        </svg>
                                                        Ajukan Tukar
                                                    </a>
                                                @else
                                                    <span class="text-xs text-slate-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                                Tidak ada jadwal pada periode ini
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- MOBILE CARD VIEW --}}
                        <div class="space-y-3 md:hidden">
                            @forelse ($schedules as $item)
                                <div
                                    class="cursor-pointer rounded-lg border border-slate-200 bg-white p-4 transition-shadow hover:shadow-md"
                                    @click="selectDate('{{ $item['date'] }}')"
                                >
                                    <div class="mb-2 flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-slate-900">
                                                {{ $item['date_display'] ?? $item['date'] }}
                                            </p>

                                            <p class="text-xs text-slate-600">
                                                {{ $item['day_name'] ?? '' }}
                                            </p>
                                        </div>

                                        @php
                                            $badgeClass = match ($item['shift_type']) {
                                                'pagi' => 'bg-orange-100 text-orange-700',
                                                'siang' => 'bg-yellow-100 text-yellow-700',
                                                'malam' => 'bg-indigo-100 text-indigo-700',
                                                'libur' => 'bg-slate-100 text-slate-700',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp

                                        <span class="inline-flex items-center rounded-full {{ $badgeClass }} px-3 py-1 text-sm font-semibold">
                                            {{ $item['shift_name'] }}
                                        </span>
                                    </div>

                                    @if (($item['type'] ?? '') !== 'holiday')
                                        <p class="text-sm text-slate-900">
                                            <strong>{{ $item['jpl_code'] ?? '-' }}</strong>
                                            {{ $item['jpl_name'] ?? '' }}
                                        </p>

                                        <p class="mt-1 font-mono text-sm text-slate-600">
                                            {{ $item['jam_mulai'] ?? '-' }}
                                            -
                                            {{ $item['jam_selesai'] ?? '-' }}
                                        </p>
                                    @endif

                                    @php
                                        $statusClass = match ($item['status']) {
                                            'dijadwalkan' => 'bg-blue-100 text-blue-700',
                                            'berlangsung' => 'bg-green-100 text-green-700',
                                            'selesai' => 'bg-gray-100 text-gray-700',
                                            'libur' => 'bg-slate-100 text-slate-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp

                                    <p class="mt-2 text-xs">
                                        <span class="inline-flex rounded-full {{ $statusClass }} px-2.5 py-0.5 font-semibold">
                                            {{ $item['status_display'] }}
                                        </span>
                                    </p>

                                    @if (!empty($item['is_pengganti']))
                                        <p class="mt-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            Pengganti dari:
                                            <strong>{{ $item['user_digantikan'] ?? '-' }}</strong>
                                        </p>
                                    @endif

                                    {{-- TOMBOL AJUKAN TUKAR MOBILE --}}
                                    @if (($item['status'] ?? '') === 'dijadwalkan' && ($item['type'] ?? '') !== 'holiday')
                                        <div class="mt-3 border-t border-slate-100 pt-3" @click.stop>
                                            <a
                                                href="{{ route('user.tukar-jadwal-dinas.create') }}"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                                                </svg>
                                                Ajukan Tukar Jadwal
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="py-8 text-center text-slate-500">
                                    Tidak ada jadwal pada periode ini
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- LEGEND --}}
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-6 sm:px-6">
                    <h3 class="mb-4 font-bold text-slate-900">
                        Keterangan Shift
                    </h3>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-orange-100 text-xs font-bold text-orange-700">P</span>
                            <span class="text-sm text-slate-700">Pagi (06:00-14:00)</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-yellow-100 text-xs font-bold text-yellow-700">S</span>
                            <span class="text-sm text-slate-700">Siang (14:00-22:00)</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-indigo-100 text-xs font-bold text-indigo-700">M</span>
                            <span class="text-sm text-slate-700">Malam (22:00-06:00)</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-100 text-xs font-bold text-slate-700">L</span>
                            <span class="text-sm text-slate-700">Libur</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function jadwalDinasApp() {
            return {
                selected: @json($initialSelectedDate),

                selectedMonth: @json($month),

                selectedYear: @json($year),

                schedulesByDate: @json($schedulesByDate),

                holidaysByDate: @json($holidaysByDate),

                tukarJadwalCreateUrl: @json(route('user.tukar-jadwal-dinas.create')),

                selectDate(date) {
                    this.selected = date;
                    this.updateDetail();
                },

                updateDetail() {
                    const container = document.getElementById('detailContainer');

                    if (!container) {
                        return;
                    }

                    const schedules = this.schedulesByDate[this.selected] || [];

                    const isHoliday = Object.prototype.hasOwnProperty.call(
                        this.holidaysByDate,
                        this.selected
                    );

                    if (!schedules.length && !isHoliday) {
                        container.innerHTML = `
                            <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
                                <p class="font-medium text-slate-700">
                                    Belum ada jadwal pada tanggal ini
                                </p>
                            </div>
                        `;

                        return;
                    }

                    if (isHoliday && !schedules.length) {
                        container.innerHTML = `
                            <div class="rounded-lg border-2 border-slate-300 bg-white p-6 text-center">
                                <svg
                                    class="mx-auto mb-3 h-12 w-12 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>

                                <p class="text-2xl font-bold text-slate-900">
                                    Hari Libur
                                </p>

                                <p class="mt-2 text-slate-600">
                                    Tidak ada jadwal kerja pada hari ini
                                </p>
                            </div>
                        `;

                        return;
                    }

                    const escapeHtml = (value) => {
                        const element = document.createElement('div');
                        element.textContent = value ?? '-';
                        return element.innerHTML;
                    };

                    const getShiftClass = (type) => {
                        switch (type) {
                            case 'pagi':
                                return 'bg-orange-100 text-orange-700';

                            case 'siang':
                                return 'bg-yellow-100 text-yellow-700';

                            case 'malam':
                                return 'bg-indigo-100 text-indigo-700';

                            default:
                                return 'bg-slate-100 text-slate-700';
                        }
                    };

                    const getStatusClass = (status) => {
                        switch (status) {
                            case 'dijadwalkan':
                                return 'bg-blue-100 text-blue-700';

                            case 'berlangsung':
                                return 'bg-green-100 text-green-700';

                            case 'selesai':
                                return 'bg-gray-100 text-gray-700';

                            default:
                                return 'bg-slate-100 text-slate-700';
                        }
                    };

                    container.innerHTML = schedules.map(schedule => {
                        const replacement = schedule.is_pengganti
                            ? `
                                <div class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    <strong>Pengganti dari:</strong>
                                    ${escapeHtml(schedule.user_digantikan)}
                                </div>
                            `
                            : '';

                        const swapButton = schedule.status === 'dijadwalkan'
                            ? `
                                <div class="mt-4 border-t border-slate-100 pt-4">
                                    <a
                                        href="${this.tukarJadwalCreateUrl}"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-orange-600 sm:w-auto"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                                        </svg>
                                        Ajukan Tukar Jadwal
                                    </a>
                                </div>
                            `
                            : '';

                        return `
                            <div class="rounded-lg border border-slate-200 bg-white p-4 sm:p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-slate-600">
                                            Detail tanggal
                                        </p>

                                        <p class="mt-1 font-semibold text-slate-900">
                                            ${escapeHtml(schedule.date_display || schedule.date)}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            ${escapeHtml(schedule.day_name)}
                                        </p>
                                    </div>

                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ${getShiftClass(schedule.shift_type)}">
                                        ${escapeHtml(schedule.shift_name)}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs font-medium text-slate-500">
                                            JPL
                                        </p>

                                        <p class="mt-1 font-semibold text-slate-900">
                                            ${escapeHtml(schedule.jpl_code)}
                                        </p>

                                        <p class="text-sm text-slate-600">
                                            ${escapeHtml(schedule.jpl_name)}
                                        </p>
                                    </div>

                                    <div class="rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs font-medium text-slate-500">
                                            Jam Dinas
                                        </p>

                                        <p class="mt-1 font-mono font-semibold text-slate-900">
                                            ${escapeHtml(schedule.jam_mulai)}
                                            -
                                            ${escapeHtml(schedule.jam_selesai)}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <p class="mb-1 text-xs font-medium text-slate-500">
                                        Status Jadwal
                                    </p>

                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getStatusClass(schedule.status)}">
                                        ${escapeHtml(schedule.status_display)}
                                    </span>
                                </div>

                                ${replacement}

                                ${swapButton}
                            </div>
                        `;
                    }).join('');
                },

                navigateMonth() {
                    const url = new URL(
                        @json(route('user.jadwal-dinas')),
                        window.location.origin
                    );

                    url.searchParams.set('month', this.selectedMonth);
                    url.searchParams.set('year', this.selectedYear);

                    window.location.href = url.toString();
                },

                init() {
                    this.updateDetail();
                }
            };
        }
    </script>
@endpush
