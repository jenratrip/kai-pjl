@extends('layouts.admin')

@section('title', 'Manajemen Dinas')

@section('content')
<div class="min-h-screen bg-slate-50 p-4 md:p-6"
     x-data="dinasPage()">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-[#1d2d50] p-3 text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14H4V6a1 1 0 011-1z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-[#1d2d50]">Manajemen Dinas</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Dashboard / Dinas / Jadwal Petugas PJL
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dinas.template') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] hover:bg-slate-100">
                ↓ Template CSV
            </a>

            <a href="{{ route('admin.dinas.export') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] hover:bg-slate-100">
                ⇩ Ekspor CSV
            </a>

            <button type="button"
                    @click="$refs.upload.click()"
                    class="rounded-lg border border-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-[#1d2d50] hover:bg-slate-100">
                ↑ Import CSV
            </button>

            <button type="button"
                    @click="openCreate = true"
                    class="rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-bold text-[#ffff]">
                + Tambah Dinas
            </button>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if(session('import_errors'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-2 font-bold">Impor dibatalkan. Periksa kesalahan berikut:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- VALIDASI FORM TAMBAH --}}
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-2 font-bold">Periksa kembali data yang dimasukkan:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Jadwal</p>
            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $dinas->total() }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Jadwal Berlangsung</p>
            <p class="mt-2 text-3xl font-bold text-green-600">
                {{ \App\Models\Dinas::where('status', 'berlangsung')->count() }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Petugas PJL Aktif</p>
            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $petugas->count() }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">JPL Aktif</p>
            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $jpl->count() }}
            </p>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.dinas.index') }}"
              class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

            <div>
                <label for="filter-user" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Petugas
                </label>
                <select id="filter-user" name="user_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#f5a400] focus:outline-none">
                    <option value="">Semua petugas</option>
                    @foreach($petugas as $p)
                        <option value="{{ $p->id }}" @selected(request('user_id') == $p->id)>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-jpl" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    JPL
                </label>
                <select id="filter-jpl" name="jpl_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#f5a400] focus:outline-none">
                    <option value="">Semua JPL</option>
                    @foreach($jpl as $lokasi)
                        <option value="{{ $lokasi->id }}" @selected(request('jpl_id') == $lokasi->id)>
                            {{ $lokasi->kode_jpl }} - {{ $lokasi->nama_jpl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-shift" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Shift
                </label>
                <select id="filter-shift" name="shift_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#f5a400] focus:outline-none">
                    <option value="">Semua shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>
                            {{ $shift->nama_shift }}
                            ({{ substr($shift->jam_mulai, 0, 5) }}–{{ substr($shift->jam_selesai, 0, 5) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-tanggal" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Tanggal Dinas
                </label>
                <input id="filter-tanggal" type="date" name="tanggal_dinas"
                       value="{{ request('tanggal_dinas') }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#f5a400] focus:outline-none">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#293e68]">
                    Terapkan
                </button>
                <a href="{{ route('admin.dinas.index') }}"
                   class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- TABEL JADWAL --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center">
            <div>
                <h2 class="font-bold text-[#1d2d50]">Daftar Jadwal Dinas</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Jadwal penugasan petugas di setiap JPL.
                </p>
            </div>
            <span class="w-fit rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">
                {{ $dinas->total() }} data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1250px] text-left text-sm">
                <thead class="bg-[#1d2d50] text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-4">No.</th>
                        <th class="px-4 py-4">Petugas</th>
                        <th class="px-4 py-4">JPL</th>
                        <th class="px-4 py-4">Shift</th>
                        <th class="px-4 py-4">Tanggal</th>
                        <th class="px-4 py-4">Jam Mulai</th>
                        <th class="px-4 py-4">Jam Selesai</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Pengganti</th>
                        <th class="px-4 py-4">Menggantikan</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($dinas as $index => $item)
                        <tr class="transition hover:bg-amber-50/40">
                            <td class="px-4 py-4 text-slate-500">
                                {{ $dinas->firstItem() + $index }}
                            </td>

                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-800">
                                    {{ $item->user?->name ?? '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    NIP {{ $item->user?->nip ?? '-' }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                <span class="font-bold text-[#1d2d50]">
                                    {{ $item->jpl?->kode_jpl ?? '-' }}
                                </span>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $item->jpl?->nama_jpl ?? '' }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                {{ $item->shift?->nama_shift ?? '-' }}
                                <p class="mt-1 text-xs text-slate-400">
                                    @if($item->shift)
                                        {{ substr($item->shift->jam_mulai, 0, 5) }}–{{ substr($item->shift->jam_selesai, 0, 5) }}
                                    @endif
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                {{ $item->tanggal_dinas?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-700">
                                {{ $item->jam_mulai?->format('H:i') ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-700">
                                {{ $item->jam_selesai?->format('H:i') ?? '-' }}
                            </td>

                            <td class="px-4 py-4">
                                @php
                                    $statusClass = match($item->status) {
                                        'berlangsung' => 'bg-green-100 text-green-700',
                                        'selesai' => 'bg-slate-100 text-slate-600',
                                        default => 'bg-blue-100 text-blue-700',
                                    };
                                @endphp
                                <span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($item->status ?? 'Tidak diketahui') }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                @if($item->is_pengganti)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                        Ya
                                    </span>
                                @else
                                    <span class="text-slate-500">Tidak</span>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-slate-600">
                                @if($item->is_pengganti)
                                    {{ $item->userDigantikan?->name ?? '-' }}
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $item->userDigantikan?->nip ?? '' }}
                                    </p>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.dinas.edit', $item) }}"
                                       title="Edit jadwal"
                                       aria-label="Edit jadwal"
                                       class="rounded-lg bg-[#f5a400] p-2 text-[#1d2d50] hover:bg-amber-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15.232 5.232l3.536 3.536M4 20l4.5-1 11-11a2.5 2.5 0 00-3.536-3.536l-11 11L4 20z"/>
                                        </svg>
                                    </a>

                                    <form action="{{ route('admin.dinas.destroy', $item) }}"
                                          method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Hapus jadwal"
                                                aria-label="Hapus jadwal"
                                                class="rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 7h12m-10 0V5h8v2m-9 0l1 13h8l1-13M10 11v5m4-5v5"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">
                                    📅
                                </div>
                                <p class="font-semibold text-slate-700">Belum ada jadwal dinas</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Tambahkan jadwal baru atau impor melalui CSV.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col justify-between gap-3 border-t border-slate-200 p-4 sm:flex-row sm:items-center">
            <p class="text-sm text-slate-500">
                Menampilkan {{ $dinas->firstItem() ?? 0 }}–{{ $dinas->lastItem() ?? 0 }}
                dari {{ $dinas->total() }} data
            </p>
            <div>{{ $dinas->withQueryString()->links() }}</div>
        </div>
    </div>

    {{-- FORM IMPOR TERSEMBUNYI --}}
    <form x-ref="importForm"
          action="{{ route('admin.dinas.import') }}"
          method="POST"
          enctype="multipart/form-data"
          class="hidden">
        @csrf
        <input x-ref="upload"
               type="file"
               name="file"
               accept=".csv,.txt,text/csv"
               @change="readCsv($event)">
    </form>

    {{-- MODAL PREVIEW CSV --}}
    <div x-cloak x-show="showPreview"
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-3 sm:p-6"
         @keydown.escape.window="showPreview = false">

        <div @click.outside="showPreview = false"
             class="my-auto max-h-[94vh] w-full max-w-7xl overflow-y-auto rounded-2xl bg-white shadow-2xl">

            <div class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-slate-200 bg-white p-5">
                <div>
                    <h2 class="text-xl font-bold text-[#1d2d50]">Preview Impor Jadwal Dinas</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Periksa format data sebelum mengirimkannya ke server.
                    </p>
                </div>
                <button type="button"
                        @click="showPreview = false"
                        aria-label="Tutup preview"
                        class="rounded-lg p-2 text-2xl text-slate-500 hover:bg-slate-100">
                    &times;
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-[250px_minmax(0,1fr)]">

                {{-- INFORMASI FILE --}}
                <aside class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-2xl">
                            📄
                        </div>
                        <p class="break-all font-semibold text-slate-800" x-text="fileName"></p>
                        <p class="mt-1 text-xs text-slate-500">
                            <span x-text="rowCount"></span> baris data
                        </p>
                        <button type="button"
                                @click="$refs.upload.click()"
                                class="mt-4 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-[#1d2d50] hover:bg-slate-100">
                            Ganti File
                        </button>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                        <p class="mb-2 font-bold">Petunjuk impor</p>
                        <ul class="list-inside list-disc space-y-2 text-xs leading-5">
                            <li>Gunakan template CSV terbaru.</li>
                            <li>Pemisah kolom menggunakan titik koma (;).</li>
                            <li>Tanggal berformat YYYY-MM-DD atau DD/MM/YYYY.</li>
                            <li>Jam mulai dan selesai boleh dikosongkan, dihitung otomatis berdasarkan shift.</li>
                            <li>Pastikan NIP dan kode JPL benar.</li>
                            <li>Jika ada baris tidak valid, perbaiki file sebelum mengimpor.</li>
                        </ul>
                    </div>

                    <div x-show="readError" x-cloak
                         class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                         x-text="readError"></div>
                </aside>

                {{-- TABEL PREVIEW --}}
                <section class="min-w-0">
                    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Total Baris</p>
                            <p class="mt-1 text-2xl font-bold text-[#1d2d50]" x-text="rowCount"></p>
                        </div>
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                            <p class="text-xs text-green-700">Format Sesuai</p>
                            <p class="mt-1 text-2xl font-bold text-green-700" x-text="validCount"></p>
                        </div>
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <p class="text-xs text-red-700">Format Bermasalah</p>
                            <p class="mt-1 text-2xl font-bold text-red-700" x-text="errorCount"></p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full min-w-[950px] text-left text-xs">
                            <thead class="bg-[#1d2d50] text-white">
                                <tr>
                                    <th class="px-3 py-3">No.</th>
                                    <th class="px-3 py-3">NIP Petugas</th>
                                    <th class="px-3 py-3">Kode JPL</th>
                                    <th class="px-3 py-3">ID Shift</th>
                                    <th class="px-3 py-3">Tanggal Dinas</th>
                                    <th class="px-3 py-3">Jam Mulai</th>
                                    <th class="px-3 py-3">Jam Selesai</th>
                                    <th class="px-3 py-3">Pengganti</th>
                                    <th class="px-3 py-3">NIP Digantikan</th>
                                    <th class="px-3 py-3">Validasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(row, i) in previewRows" :key="row.line">
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-3" x-text="row.line"></td>
                                        <td class="px-3 py-3 font-medium" x-text="row.nip || '-'"></td>
                                        <td class="px-3 py-3" x-text="row.kode_jpl || '-'"></td>
                                        <td class="px-3 py-3" x-text="row.id_shift || '-'"></td>
                                        <td class="whitespace-nowrap px-3 py-3" x-text="row.tanggal || '-'"></td>
                                        <td class="px-3 py-3" x-text="row.jam_mulai || 'Otomatis'"></td>
                                        <td class="px-3 py-3" x-text="row.jam_selesai || 'Otomatis'"></td>
                                        <td class="px-3 py-3" x-text="row.pengganti === '1' ? 'Ya' : (row.pengganti === '0' ? 'Tidak' : '-')"></td>
                                        <td class="px-3 py-3" x-text="row.nip_digantikan || '-'"></td>
                                        <td class="px-3 py-3">
                                            <span :class="row.error ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                                                  class="rounded-full px-2 py-1 font-semibold"
                                                  x-text="row.error ? 'Periksa' : 'Format OK'"></span>
                                            <p x-show="row.error"
                                               class="mt-1 max-w-48 whitespace-normal text-red-600"
                                               x-text="row.error"></p>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="previewRows.length === 0">
                                    <td colspan="10" class="px-4 py-10 text-center text-slate-500">
                                        Belum ada data untuk ditampilkan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        Preview memeriksa format CSV di browser. Server tetap memeriksa
                        keberadaan NIP, JPL, shift, pengganti, dan bentrok jadwal.
                    </p>

                    <div class="mt-5 flex flex-col-reverse justify-end gap-3 sm:flex-row">
                        <button type="button"
                                @click="showPreview = false"
                                class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            Batal
                        </button>

                        <button type="button"
                                @click="confirmImport()"
                                :disabled="previewRows.length === 0 || errorCount > 0 || importing || !!readError"
                                class="rounded-lg bg-[#f5a400] px-5 py-3 text-sm font-bold text-[#1d2d50] hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="importing ? 'Mengirim...' : '✓ Konfirmasi dan Impor'"></span>
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH DINAS --}}
    <div x-cloak x-show="openCreate"
         x-transition.opacity
         class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
         @keydown.escape.window="openCreate = false">

        <div @click.outside="openCreate = false"
             class="my-auto w-full max-w-2xl rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b border-slate-200 p-5">
                <div>
                    <h2 class="text-xl font-bold text-[#1d2d50]">Tambah Jadwal Dinas</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Jam dinas mengikuti konfigurasi shift.
                    </p>
                </div>
                <button type="button"
                        @click="openCreate = false"
                        aria-label="Tutup formulir"
                        class="rounded-lg p-2 text-2xl text-slate-500 hover:bg-slate-100">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.dinas.store') }}"
                  method="POST"
                  class="space-y-4 p-5">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="user_id" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Petugas PJL
                        </label>
                        <select id="user_id" name="user_id" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Pilih petugas</option>
                            @foreach($petugas as $p)
                                <option value="{{ $p->id }}" @selected(old('user_id') == $p->id)>
                                    {{ $p->name }} ({{ $p->nip }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="jpl_id" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            JPL
                        </label>
                        <select id="jpl_id" name="jpl_id" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Pilih JPL</option>
                            @foreach($jpl as $lokasi)
                                <option value="{{ $lokasi->id }}" @selected(old('jpl_id') == $lokasi->id)>
                                    {{ $lokasi->kode_jpl }} - {{ $lokasi->nama_jpl }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="shift_id" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Shift
                        </label>
                        <select id="shift_id" name="shift_id" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Pilih shift</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>
                                    {{ $shift->nama_shift }}
                                    ({{ substr($shift->jam_mulai, 0, 5) }}–{{ substr($shift->jam_selesai, 0, 5) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tanggal_dinas" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Tanggal Dinas
                        </label>
                        <input id="tanggal_dinas"
                               type="date"
                               name="tanggal_dinas"
                               value="{{ old('tanggal_dinas', now()->format('Y-m-d')) }}"
                               required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox"
                                   name="is_pengganti"
                                   value="1"
                                   x-model="isReplacement"
                                   @checked(old('is_pengganti'))
                                   class="rounded border-slate-300 text-[#f5a400] focus:ring-[#f5a400]">
                            Jadwal ini merupakan penugasan pengganti
                        </label>
                    </div>

                    <div class="sm:col-span-2" x-show="isReplacement" x-cloak>
                        <label for="user_digantikan_id"
                               class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Petugas yang Digantikan
                        </label>
                        <select id="user_digantikan_id"
                                name="user_digantikan_id"
                                :required="isReplacement"
                                :disabled="!isReplacement"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Pilih petugas yang digantikan</option>
                            @foreach($petugas as $p)
                                <option value="{{ $p->id }}" @selected(old('user_digantikan_id') == $p->id)>
                                    {{ $p->name }} ({{ $p->nip }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
                    Jam mulai dan jam selesai ditentukan otomatis dari shift yang dipilih.
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button"
                            @click="openCreate = false"
                            class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-[#f5a400] px-5 py-2.5 text-sm font-bold text-[#1d2d50] hover:bg-amber-400">
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<script>
function dinasPage() {
    return {
        openCreate: {{ $errors->any() ? 'true' : 'false' }},
        isReplacement: {{ old('is_pengganti') ? 'true' : 'false' }},
        showPreview: false,
        importing: false,
        fileName: '',
        previewRows: [],
        readError: '',

        // Harus sama persis dengan csvHeaders() di DinasController (8 kolom).
        expectedHeaders: [
            'nip',
            'kode_jpl',
            'id_shift',
            'tanggal_dinas',
            'jam_mulai',
            'jam_selesai',
            'is_pengganti',
            'nip_digantikan'
        ],

        get rowCount() {
            return this.previewRows.length;
        },

        get validCount() {
            return this.previewRows.filter(row => !row.error).length;
        },

        get errorCount() {
            return this.previewRows.filter(row => !!row.error).length;
        },

        // Hapus BOM asli (\uFEFF) maupun BOM yang salah decode (ï»¿).
        stripBom(text) {
            return String(text ?? '')
                .replace(/^\uFEFF/, '')
                .replace(/^\u00EF\u00BB\u00BF/, '');
        },

        parseCsvLine(line) {
            const result = [];
            let value = '';
            let quoted = false;

            for (let i = 0; i < line.length; i++) {
                const char = line[i];

                if (char === '"') {
                    if (quoted && line[i + 1] === '"') {
                        value += '"';
                        i++;
                    } else {
                        quoted = !quoted;
                    }
                } else if (char === ';' && !quoted) {
                    result.push(value.trim());
                    value = '';
                } else {
                    value += char;
                }
            }

            result.push(value.trim());

            if (quoted) {
                throw new Error('Terdapat tanda kutip CSV yang tidak berpasangan.');
            }

            return result;
        },

        // Menerima YYYY-MM-DD, DD/MM/YYYY, dan DD-MM-YYYY (sama seperti server).
        isValidDate(value) {
            let y, m, d, match;

            if ((match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/))) {
                [, y, m, d] = match;
            } else if ((match = value.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/))) {
                [, d, m, y] = match;
            } else {
                return false;
            }

            y = Number(y); m = Number(m); d = Number(d);
            const date = new Date(Date.UTC(y, m - 1, d));

            return date.getUTCFullYear() === y
                && date.getUTCMonth() === m - 1
                && date.getUTCDate() === d;
        },

        // Jam boleh kosong (otomatis dari shift), atau HH:MM:SS.
        isValidTime(value) {
            if (value === '') return true;
            const match = value.match(/^(\d{2}):(\d{2}):(\d{2})$/);
            return !!match
                && Number(match[1]) <= 23
                && Number(match[2]) <= 59
                && Number(match[3]) <= 59;
        },

        readCsv(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            this.fileName = file.name;
            this.previewRows = [];
            this.readError = '';
            this.importing = false;

            if (!/\.(csv|txt)$/i.test(file.name)) {
                this.readError = 'Pilih file berekstensi CSV atau TXT.';
                this.showPreview = true;
                event.target.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.readError = 'Ukuran file maksimal 5 MB.';
                this.showPreview = true;
                event.target.value = '';
                return;
            }

            this.showPreview = true;

            const reader = new FileReader();

            reader.onload = (e) => {
                try {
                    const content = this.stripBom(e.target.result);
                    const lines = content
                        .split(/\r\n|\n|\r/)
                        .filter(line => line.trim() !== '');

                    if (lines.length < 2) {
                        throw new Error('File harus berisi header dan minimal satu baris data.');
                    }

                    const headers = this.parseCsvLine(lines[0]).map(
                        header => this.stripBom(header).trim().toLowerCase()
                    );

                    if (JSON.stringify(headers) !== JSON.stringify(this.expectedHeaders)) {
                        throw new Error(
                            'Header CSV tidak sesuai template. Unduh Template CSV terbaru, lalu isi kolom yang tersedia tanpa mengubah nama atau urutannya.'
                        );
                    }

                    const total = this.expectedHeaders.length;

                    this.previewRows = lines.slice(1).map((line, index) => {
                        let cols = [];
                        let error = '';

                        try {
                            cols = this.parseCsvLine(line);
                        } catch (parseError) {
                            error = parseError.message;
                        }

                        if (!error && cols.length > total) {
                            error = 'Jumlah kolom tidak sesuai template.';
                        }

                        while (cols.length < total) {
                            cols.push('');
                        }

                        const [
                            nip, kodeJpl, idShift, tanggal,
                            jamMulai, jamSelesai, pengganti, nipDigantikan
                        ] = cols;

                        if (!error && !nip) {
                            error = 'NIP petugas wajib diisi.';
                        } else if (!error && !kodeJpl) {
                            error = 'Kode JPL wajib diisi.';
                        } else if (!error && !/^\d+$/.test(idShift)) {
                            error = 'ID shift harus berupa angka.';
                        } else if (!error && !this.isValidDate(tanggal)) {
                            error = 'Tanggal tidak valid. Gunakan YYYY-MM-DD atau DD/MM/YYYY.';
                        } else if (!error && !this.isValidTime(jamMulai)) {
                            error = 'Jam mulai harus berformat HH:MM:SS atau dikosongkan.';
                        } else if (!error && !this.isValidTime(jamSelesai)) {
                            error = 'Jam selesai harus berformat HH:MM:SS atau dikosongkan.';
                        } else if (!error && !['0', '1'].includes(pengganti)) {
                            error = 'is_pengganti harus bernilai 0 atau 1.';
                        } else if (!error && pengganti === '1' && !nipDigantikan) {
                            error = 'NIP petugas yang digantikan wajib diisi.';
                        } else if (!error && pengganti === '0' && nipDigantikan) {
                            error = 'Kosongkan nip_digantikan jika bukan petugas pengganti.';
                        }

                        return {
                            line: index + 2,
                            nip: nip || '',
                            kode_jpl: kodeJpl || '',
                            id_shift: idShift || '',
                            tanggal: tanggal || '',
                            jam_mulai: jamMulai || '',
                            jam_selesai: jamSelesai || '',
                            pengganti: pengganti || '',
                            nip_digantikan: nipDigantikan || '',
                            error
                        };
                    });

                    if (this.previewRows.length === 0) {
                        throw new Error('Tidak ditemukan baris data untuk diimpor.');
                    }
                } catch (error) {
                    this.previewRows = [];
                    this.readError = error.message || 'File CSV tidak dapat dibaca.';
                }
            };

            reader.onerror = () => {
                this.previewRows = [];
                this.readError = 'Gagal membaca file. Coba simpan ulang sebagai CSV UTF-8.';
            };

            reader.readAsText(file, 'UTF-8');
        },

        confirmImport() {
            if (
                this.previewRows.length === 0 ||
                this.errorCount > 0 ||
                this.readError ||
                this.importing
            ) {
                return;
            }

            if (!confirm(
                'Impor ' + this.rowCount +
                ' baris jadwal dinas? Server akan memvalidasi ulang seluruh data.'
            )) {
                return;
            }

            this.importing = true;

            // File asli tetap berada pada input form yang sama dengan yang dipreview.
            this.$refs.importForm.requestSubmit();
        }
    };
}
</script>
@endsection