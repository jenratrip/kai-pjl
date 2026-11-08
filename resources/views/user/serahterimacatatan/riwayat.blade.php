@extends('layouts.user')

@section('title', 'Riwayat Serah Terima Dinas - Sistem PJL')

@section('content')
    @php
        // [teks, kelas badge, kelas titik, kelas garis kiri kartu HP]
        $label = [
            'draft' => [
                'Draft',
                'bg-slate-100 text-slate-700',
                'bg-slate-400',
                'border-l-slate-300',
            ],
            'menunggu_penerima' => [
                'Menunggu tanda tangan penerima',
                'bg-amber-100 text-amber-800',
                'bg-amber-500',
                'border-l-amber-400',
            ],
            'selesai' => [
                'Sudah ditandatangani',
                'bg-green-100 text-green-800',
                'bg-green-500',
                'border-l-green-500',
            ],
        ];

        $labelDefault = [
            null,
            'bg-slate-100 text-slate-700',
            'bg-slate-400',
            'border-l-slate-300',
        ];

        /*
         * Data disiapkan sekali untuk tabel desktop dan kartu HP.
         *
         * Dokumen masuk:
         * penerima_user_id = akun yang sedang login.
         *
         * Dokumen keluar:
         * dokumen dibuat oleh akun yang sedang login.
         */
        $baris = $riwayat->getCollection()->map(function ($item) use ($label, $labelDefault) {
            $status = (string) $item->status;

            [$teks, $kelas, $titik, $garis] =
                $label[$status] ?? $labelDefault;

            $teks ??= $status !== ''
                ? ucfirst(str_replace('_', ' ', $status))
                : '-';

            $tgl = $item->dinas?->tanggal_dinas
                ? \Carbon\Carbon::parse($item->dinas->tanggal_dinas)->locale('id')
                : null;

            $waktuSimpan = $item->waktu_serah_terima
                ?? $item->updated_at;

            $tanggal = $tgl?->translatedFormat('d M Y') ?? '-';

            $isPenerima = (int) $item->penerima_user_id === (int) auth()->id();

            $namaPenyerah = $item->dinas?->user?->name
                ?? data_get($item->ringkasan, 'nama_penyerah')
                ?? '-';

            $namaPenerima = data_get($item->ringkasan, 'nama_penerima')
                ?: ($item->penerima?->name ?? '-');

            /*
             * Status selesai berarti kedua tanda tangan sudah tersedia.
             * Status ini belum tentu berarti admin sudah menyetujui.
             */
            $terkunci = $status === 'selesai';

            /*
             * Untuk dokumen masuk, tampilkan nama penyerah.
             * Untuk dokumen keluar, tampilkan nama penerima.
             */
            $namaPihak = $isPenerima
                ? $namaPenyerah
                : $namaPenerima;

            $keteranganPihak = $isPenerima
                ? 'Dari'
                : 'Kepada';

            /*
             * Penerima membuka dokumen yang sama melalui halaman edit.
             * Controller harus membatasi penerima agar hanya dapat
             * mengisi tanda tangan penerima, bukan mengedit isi dokumen.
             */
            $labelAksi = $terkunci
                ? 'Lihat'
                : ($isPenerima ? 'Konfirmasi & tanda tangan' : 'Edit');

            return [
                'tanggal' => $tanggal,
                'hari' => $tgl?->translatedFormat('l'),
                'shift' => $item->dinas?->shift?->nama_shift ?? '-',
                'jpl' => $item->dinas?->jpl?->kode_jpl ?? '-',
                'namaJpl' => $item->dinas?->jpl?->nama_jpl,

                'namaPihak' => $namaPihak,
                'keteranganPihak' => $keteranganPihak,

                'disimpan' => $waktuSimpan
                    ? \Carbon\Carbon::parse($waktuSimpan)
                        ->timezone('Asia/Jakarta')
                        ->locale('id')
                        ->translatedFormat('d M Y, H:i') . ' WIB'
                    : '-',

                'teks' => $teks,
                'kelas' => $kelas,
                'titik' => $titik,
                'garis' => $garis,

                'isPenerima' => $isPenerima,
                'terkunci' => $terkunci,
                'labelAksi' => $labelAksi,

                'urlEdit' => route('user.serah-terima-dinas.edit', $item),
                'urlLihat' => route('user.serah-terima-dinas.show', $item),

                'aria' => 'serah terima dinas tanggal ' . $tanggal,
            ];
        });
    @endphp

    <div class="mx-auto min-h-[70vh] w-full max-w-7xl px-4 py-8 sm:px-8 lg:py-10">

        {{-- Judul halaman --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1d2d50]">
                    Riwayat Serah Terima Dinas
                </h1>

                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Riwayat menampilkan serah terima yang kamu buat maupun
                    dokumen yang ditujukan kepadamu sebagai penerima.
                    Dokumen yang menunggu tanda tangan penerima dapat dikonfirmasi
                    oleh petugas yang ditunjuk.
                </p>
            </div>

            <a
                href="{{ route('user.serah-terima-dinas') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
            >
                Kembali ke serah terima
            </a>
        </div>

        {{-- Notifikasi --}}
        @if (session('success'))
            <div
                class="mb-4 rounded-lg border border-green-200 bg-green-50 px-[18px] py-3.5 text-[15px] leading-relaxed text-green-800 max-sm:text-sm"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-[18px] py-3.5 text-[15px] leading-relaxed text-red-800 max-sm:text-sm"
                role="alert"
            >
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-[18px] py-3.5 text-[15px] leading-relaxed text-red-800 max-sm:text-sm"
                role="alert"
            >
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($riwayat->isEmpty())
            {{-- Belum ada data --}}
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <p class="font-semibold text-[#1d2d50]">
                    Belum ada riwayat serah terima
                </p>

                <p class="mx-auto mt-1 max-w-sm text-sm leading-relaxed text-slate-500">
                    Riwayat akan muncul setelah kamu menyimpan serah terima
                    atau setelah petugas lain menunjuk akunmu sebagai penerima.
                </p>

                <a
                    href="{{ route('user.serah-terima-dinas') }}"
                    class="mt-4 inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
                >
                    Buat serah terima
                </a>
            </div>
        @else

            {{-- Tabel desktop --}}
            <div class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                <table class="w-full min-w-[800px] text-left text-sm">
                    <caption class="sr-only">
                        Daftar riwayat serah terima dinas
                    </caption>

                    <thead class="bg-[#1d2d50] text-white">
                        <tr>
                            <th scope="col" class="px-4 py-4 text-[15px] font-bold">
                                Tanggal dinas
                            </th>

                            <th scope="col" class="px-4 py-4 text-[15px] font-bold">
                                Shift
                            </th>

                            <th scope="col" class="px-4 py-4 text-[15px] font-bold">
                                JPL
                            </th>

                            <th scope="col" class="px-4 py-4 text-[15px] font-bold">
                                Penyerah / Penerima
                            </th>

                            <th scope="col" class="px-4 py-4 text-[15px] font-bold">
                                Terakhir disimpan
                            </th>

                            <th scope="col" class="px-4 py-4 text-[15px] font-bold">
                                Status
                            </th>

                            <th scope="col" class="px-4 py-4 text-right text-[15px] font-bold">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($baris as $b)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-4 align-middle">
                                    <p class="font-semibold text-slate-800">
                                        {{ $b['tanggal'] }}
                                    </p>

                                    @if ($b['hari'])
                                        <p class="text-[13px] text-slate-500">
                                            {{ $b['hari'] }}
                                        </p>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 align-middle text-slate-700">
                                    {{ $b['shift'] }}
                                </td>

                                <td class="px-4 py-4 align-middle">
                                    <p class="font-semibold text-slate-800">
                                        {{ $b['jpl'] }}
                                    </p>

                                    @if ($b['namaJpl'])
                                        <p
                                            class="max-w-[220px] truncate text-[13px] text-slate-500"
                                            title="{{ $b['namaJpl'] }}"
                                        >
                                            {{ $b['namaJpl'] }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-middle">
                                    <p class="text-xs text-slate-500">
                                        {{ $b['keteranganPihak'] }}
                                    </p>

                                    <p class="font-medium text-slate-700">
                                        {{ $b['namaPihak'] }}
                                    </p>

                                    @if ($b['isPenerima'])
                                        <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                            Dokumen masuk
                                        </span>
                                    @else
                                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                            Dokumen dibuat
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 align-middle text-[13px] text-slate-500">
                                    {{ $b['disimpan'] }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 align-middle">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['kelas'] }}">
                                        <span
                                            class="h-1.5 w-1.5 rounded-full {{ $b['titik'] }}"
                                            aria-hidden="true"
                                        ></span>

                                        {{ $b['teks'] }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-right align-middle">
                                    @if ($b['terkunci'])
                                        <a
                                            href="{{ $b['urlLihat'] }}"
                                            aria-label="Lihat {{ $b['aria'] }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
                                        >
                                            Lihat
                                        </a>
                                    @elseif ($b['isPenerima'])
                                        <a
                                            href="{{ $b['urlEdit'] }}"
                                            aria-label="Konfirmasi dan tanda tangan {{ $b['aria'] }}"
                                            class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                                        >
                                            Konfirmasi & tanda tangan
                                        </a>
                                    @else
                                        <a
                                            href="{{ $b['urlEdit'] }}"
                                            aria-label="Edit {{ $b['aria'] }}"
                                            class="inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
                                        >
                                            Edit
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Kartu HP --}}
            <div class="space-y-3 md:hidden">
                @foreach ($baris as $b)
                    <article class="rounded-xl border border-l-4 border-slate-200 bg-white p-4 shadow-sm {{ $b['garis'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800">
                                    {{ $b['tanggal'] }}
                                </p>

                                @if ($b['hari'])
                                    <p class="text-xs text-slate-500">
                                        {{ $b['hari'] }}
                                    </p>
                                @endif
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['kelas'] }}">
                                <span
                                    class="h-1.5 w-1.5 rounded-full {{ $b['titik'] }}"
                                    aria-hidden="true"
                                ></span>

                                {{ $b['teks'] }}
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2.5 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500">
                                    Shift
                                </dt>

                                <dd class="font-semibold text-slate-800">
                                    {{ $b['shift'] }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs text-slate-500">
                                    JPL
                                </dt>

                                <dd class="font-semibold text-slate-800">
                                    {{ $b['jpl'] }}
                                </dd>
                            </div>

                            <div class="col-span-2">
                                <dt class="text-xs text-slate-500">
                                    {{ $b['keteranganPihak'] }}
                                </dt>

                                <dd class="font-semibold text-slate-800">
                                    {{ $b['namaPihak'] }}
                                </dd>

                                @if ($b['isPenerima'])
                                    <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                        Dokumen masuk
                                    </span>
                                @else
                                    <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                        Dokumen dibuat
                                    </span>
                                @endif
                            </div>

                            <div class="col-span-2">
                                <dt class="text-xs text-slate-500">
                                    Terakhir disimpan
                                </dt>

                                <dd class="text-slate-700">
                                    {{ $b['disimpan'] }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3 border-t border-slate-100 pt-3">
                            @if ($b['terkunci'])
                                <a
                                    href="{{ $b['urlLihat'] }}"
                                    aria-label="Lihat {{ $b['aria'] }}"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
                                >
                                    Lihat serah terima
                                </a>
                            @elseif ($b['isPenerima'])
                                <a
                                    href="{{ $b['urlEdit'] }}"
                                    aria-label="Konfirmasi dan tanda tangan {{ $b['aria'] }}"
                                    class="block w-full rounded-lg bg-amber-500 px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                                >
                                    Konfirmasi & tanda tangan
                                </a>
                            @else
                                <a
                                    href="{{ $b['urlEdit'] }}"
                                    aria-label="Edit {{ $b['aria'] }}"
                                    class="block w-full rounded-lg bg-[#1d2d50] px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
                                >
                                    Edit serah terima
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Ringkasan jumlah dan paginasi --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Menampilkan {{ $riwayat->firstItem() }}–{{ $riwayat->lastItem() }}
                    dari {{ $riwayat->total() }} data
                </p>

                <div>
                    {{ $riwayat->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
