
@extends('layouts.admin')

@section('title', 'Detail Serah Terima Dinas')

@section('content')
@php
    $status = $serahTerima->status;

    $statusClass = match ($status) {
        'selesai' => 'bg-green-100 text-green-800',
        'menunggu_penerima' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };

    $statusLabel = match ($status) {
        'selesai' => 'Selesai',
        'menunggu_penerima' => 'Menunggu konfirmasi',
        default => ucfirst(str_replace('_', ' ', $status ?? 'Tidak diketahui')),
    };

    $ringkasan = $serahTerima->ringkasan;

    if (is_string($ringkasan)) {
        $decoded = json_decode($ringkasan, true);
        $ringkasan = is_array($decoded)
            ? $decoded
            : ['catatan_serah_terima' => $ringkasan];
    }

    $ringkasan = is_array($ringkasan) ? $ringkasan : [];

    $ambil = function (array $data, array $keys, $default = null) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $default;
    };

    $formatNilai = function ($nilai) use (&$formatNilai) {
        if (is_array($nilai)) {
            if ($nilai === []) {
                return '—';
            }

            $hasil = [];

            foreach ($nilai as $key => $value) {
                $teks = $formatNilai($value);

                if (is_string($key)) {
                    $label = ucwords(str_replace(
                        ['_', '-'],
                        ' ',
                        $key
                    ));

                    $hasil[] = $label . ': ' . $teks;
                } else {
                    $hasil[] = $teks;
                }
            }

            return implode('; ', $hasil);
        }

        if (is_bool($nilai)) {
            return $nilai ? 'Ya' : 'Tidak';
        }

        if (is_scalar($nilai)) {
            $teks = trim((string) $nilai);
            return $teks !== '' ? $teks : '—';
        }

        return '—';
    };

    $labelKunci = function ($key) {
        return ucwords(str_replace(
            ['_', '-'],
            ' ',
            (string) $key
        ));
    };

    $alat = $ambil($ringkasan, [
        'alat',
        'alat_pengamanan',
        'kondisi_alat',
    ], []);

    $alatCatatan = $ambil($ringkasan, [
        'alat_catatan',
        'catatan_alat',
    ], []);

    $kaBatal = $ambil($ringkasan, [
        'ka_batal',
        'ka_batal_data',
    ], []);

    $kaBerjalan = $ambil($ringkasan, [
        'ka_berjalan',
        'ka_berjalan_data',
    ], []);

    $kaBelumLewat = $ambil($ringkasan, [
        'ka_belum_lewat',
        'ka_belum_lewat_data',
    ], []);

    $catatan = $ambil($ringkasan, [
        'catatan_serah_terima',
        'catatan',
    ]);

    $keysDikenal = [
        'alat', 'alat_pengamanan', 'kondisi_alat',
        'alat_catatan', 'catatan_alat',
        'ka_batal', 'ka_batal_data',
        'ka_berjalan', 'ka_berjalan_data',
        'ka_belum_lewat', 'ka_belum_lewat_data',
        'catatan_serah_terima', 'catatan',
    ];

    $dataTambahan = collect($ringkasan)->reject(
        fn ($value, $key) => in_array((string) $key, $keysDikenal, true)
    );

    $renderDaftar = function ($data) use ($formatNilai) {
        if (!is_array($data) || count($data) === 0) {
            return '—';
        }

        return collect($data)->map(
            fn ($item) => $formatNilai($item)
        )->implode("\n");
    };
@endphp

<div class="mx-auto max-w-7xl px-5 py-8 sm:px-8">

    <div class="mb-6">
        <a href="{{ route('admin.serah-terima-dinas.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-900">
            <span aria-hidden="true">←</span>
            Kembali ke daftar pengajuan
        </a>

        <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-[#1d2d50]">
                    Detail Serah Terima Dinas
                </h1>
                <p class="mt-2 text-slate-500">
                    Periksa data dan konfirmasikan penerimaan serah terima dari petugas PJL.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-semibold {{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">Pengajuan belum dapat diproses.</p>
            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
            Informasi Pengajuan
        </h2>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-sm text-slate-500">Petugas PJL</p>
                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->user?->name ?? 'Tidak tersedia' }}
                </p>
                <p class="text-sm text-slate-500">
                    NIP: {{ $serahTerima->dinasPenyerah?->user?->nip ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">JPL</p>
                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->jpl?->nama_jpl
                        ?? $serahTerima->dinasPenyerah?->jpl?->kode_jpl
                        ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">Waktu Serah Terima</p>
                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->waktu_serah_terima
                        ? $serahTerima->waktu_serah_terima->format('d/m/Y H:i')
                        : '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">Shift</p>
                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->shift?->nama_shift ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">Penerima</p>
                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->penerima?->name ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">ID Pengajuan</p>
                <p class="mt-1 font-semibold text-slate-800">
                    #{{ $serahTerima->id }}
                </p>
            </div>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Ringkasan Serah Terima
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Rangkuman kondisi peralatan, perjalanan KA, dan catatan petugas PJL.
            </p>
        </div>

        <div class="space-y-6">

            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    1. Kondisi Alat Pengamanan
                </h3>

                @if (is_array($alat) && count($alat))
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($alat as $nama => $kondisi)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ is_string($nama) ? $labelKunci($nama) : 'Peralatan' }}
                                </p>
                                <p class="mt-1 font-semibold text-slate-800">
                                    {{ $formatNilai($kondisi) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl bg-slate-50 p-4 text-slate-500">—</p>
                @endif
            </section>

            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    2. Catatan Alat Pengamanan
                </h3>

                @if (is_array($alatCatatan) && count($alatCatatan))
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($alatCatatan as $nama => $catatanAlat)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ is_string($nama) ? $labelKunci($nama) : 'Catatan alat' }}
                                </p>
                                <p class="mt-1 whitespace-pre-wrap text-slate-800">
                                    {{ $formatNilai($catatanAlat) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl border border-slate-200 p-4 text-slate-500">—</p>
                @endif
            </section>

            <div class="border-t border-slate-100"></div>

            @foreach ([
                '3. KA Batal' => $kaBatal,
                '4. KA Berjalan' => $kaBerjalan,
                '5. KA Belum Lewat' => $kaBelumLewat,
            ] as $judul => $dataKA)
                <section>
                    <h3 class="mb-3 font-semibold text-slate-800">
                        {{ $judul }}
                    </h3>

                    <div class="rounded-xl border border-slate-200 p-4">
                        @if (is_array($dataKA) && count($dataKA))
                            <ul class="list-inside list-disc space-y-2 text-slate-700">
                                @foreach ($dataKA as $ka)
                                    <li>{{ $formatNilai($ka) }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-slate-500">—</p>
                        @endif
                    </div>
                </section>
            @endforeach

            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    6. Catatan Serah Terima
                </h3>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="whitespace-pre-wrap break-words text-slate-700">{{ $formatNilai($catatan) }}</p>
                </div>
            </section>

            @if ($dataTambahan->isNotEmpty())
                <section class="border-t border-slate-100 pt-5">
                    <h3 class="mb-3 font-semibold text-slate-800">
                        Informasi Tambahan
                    </h3>

                    <div class="space-y-3">
                        @foreach ($dataTambahan as $key => $value)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ $labelKunci($key) }}
                                </p>
                                <p class="mt-1 break-words text-slate-800">
                                    {{ $formatNilai($value) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-[#1d2d50]">
                Tanda Tangan Penyerah
            </h2>

            @if ($serahTerima->tanda_tangan_penyerah)
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($serahTerima->tanda_tangan_penyerah) }}"
                        alt="Tanda tangan petugas PJL"
                        class="max-h-40 max-w-full object-contain"
                    >
                </div>
                <p class="mt-3 text-sm text-green-700">
                    Tanda tangan penyerah tersedia.
                </p>
            @else
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                    Belum ada tanda tangan penyerah.
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-[#1d2d50]">
                Tanda Tangan Penerima
            </h2>

            @if ($serahTerima->tanda_tangan_penerima)
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($serahTerima->tanda_tangan_penerima) }}"
                        alt="Tanda tangan penerima"
                        class="max-h-40 max-w-full object-contain"
                    >
                </div>
                <p class="mt-3 text-sm text-green-700">
                    Tanda tangan penerima tersedia.
                </p>
            @elseif ($serahTerima->status === 'menunggu_penerima')
                <form
                    action="{{ route('admin.serah-terima-dinas.konfirmasi', $serahTerima->id) }}"
                    method="POST"
                    id="formKonfirmasi"
                >
                    @csrf

                    <p class="mb-3 text-sm text-slate-600">
                        Gambarkan tanda tangan Anda untuk mengonfirmasi penerimaan dinas.
                    </p>

                    <div class="overflow-hidden rounded-xl border border-slate-300 bg-white">
                        <canvas
                            id="signatureCanvas"
                            class="block h-48 w-full touch-none"
                            aria-label="Area tanda tangan penerima"
                        ></canvas>
                    </div>

                    <input
                        type="hidden"
                        name="tanda_tangan"
                        id="tandaTanganInput"
                    >

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button
                            type="button"
                            id="clearSignature"
                            class="rounded-lg border border-slate-300 px-4 py-2 font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Hapus tanda tangan
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-[#1d2d50] px-5 py-2 font-semibold text-white hover:bg-[#293f69]"
                        >
                            Konfirmasi penerimaan
                        </button>
                    </div>
                </form>
            @else
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                    Tanda tangan penerima belum tersedia.
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@if ($serahTerima->status === 'menunggu_penerima' && !$serahTerima->tanda_tangan_penerima)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('signatureCanvas');
        const form = document.getElementById('formKonfirmasi');
        const input = document.getElementById('tandaTanganInput');
        const clearButton = document.getElementById('clearSignature');

        if (!canvas || !form || !input || !clearButton) return;

        const ctx = canvas.getContext('2d');

        let drawing = false;
        let hasSignature = false;

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;

            canvas.width = Math.max(1, Math.round(rect.width * ratio));
            canvas.height = Math.max(1, Math.round(rect.height * ratio));

            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1d2d50';
        }

        function getPosition(event) {
            const rect = canvas.getBoundingClientRect();

            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top
            };
        }

        canvas.addEventListener('pointerdown', function (event) {
            event.preventDefault();

            drawing = true;
            hasSignature = true;
            canvas.setPointerCapture(event.pointerId);

            const pos = getPosition(event);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        });

        canvas.addEventListener('pointermove', function (event) {
            if (!drawing) return;

            event.preventDefault();

            const pos = getPosition(event);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        });

        function stopDrawing() {
            drawing = false;
            ctx.beginPath();
        }

        canvas.addEventListener('pointerup', stopDrawing);
        canvas.addEventListener('pointercancel', stopDrawing);
        canvas.addEventListener('lostpointercapture', stopDrawing);

        clearButton.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            input.value = '';
        });

        form.addEventListener('submit', function (event) {
            if (!hasSignature) {
                event.preventDefault();
                alert('Silakan bubuhkan tanda tangan terlebih dahulu.');
                return;
            }

            input.value = canvas.toDataURL('image/png');
        });

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
    });
    </script>
    @endpush
@endif
