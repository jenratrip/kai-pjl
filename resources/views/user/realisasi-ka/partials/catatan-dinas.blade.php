{{-- CATATAN DINAS --}}
<section class="mt-6 w-full rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" id="catatanDinasQuick">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm font-bold text-[#1d2d50]">Catatan Dinas</div>
            <p class="mt-1 text-xs text-slate-500">
                Catat kejadian penting selama pelaksanaan dinas.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('user.catatan-dinas') }}"
                class="cd-quick-link inline-flex items-center rounded-md px-2 py-2 text-xs font-semibold text-[#1d2d50] underline-offset-4 transition hover:bg-slate-50 hover:underline">
                Lihat semua catatan →
            </a>

            <button
                type="button"
                class="add-button cd-open-btn mt-0 inline-flex min-h-9 items-center justify-center rounded-md border border-[#1d2d50] bg-white px-3 py-1.5 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#55769a]/30 disabled:cursor-not-allowed disabled:opacity-50"
                onclick="openCatatanModal()"
                @disabled(!($dinasAktif ?? null))>
                + Tambah Catatan
            </button>
        </div>
    </div>

    @if (session('success'))
    <div class="cd-quick-alert success" role="status">
        {{ session('success') }}
    </div>
    @endif

    @if (!($dinasAktif ?? null))
    <div class="cd-quick-alert error">
        Belum ada dinas aktif, catatan belum bisa ditambahkan.
    </div>
    @endif
</section>

@if ($dinasAktif ?? null)
@php
    $tanggalCatatan = \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)
        ->format('Y-m-d');
@endphp

{{-- MODAL TAMBAH CATATAN --}}
<div
    class="cdm-overlay fixed inset-0 z-[1000] hidden items-center justify-center overflow-y-auto bg-slate-900/55 p-3 backdrop-blur-sm sm:p-5"
    id="catatanModal"
    aria-hidden="true"
    data-open-on-load="{{ $errors->any() ? '1' : '0' }}">

    <div
        class="cdm-modal relative my-auto max-h-[calc(100vh-2rem)] w-full max-w-[560px] overflow-y-auto rounded-xl bg-white p-4 shadow-2xl sm:p-5"
        role="dialog"
        aria-modal="true"
        aria-labelledby="catatanModalTitle">

        <div class="cdm-header mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <h2 class="cdm-title m-0 text-base font-bold text-[#1d2d50]" id="catatanModalTitle">
                Tambah Catatan Dinas
            </h2>

            <button
                type="button"
                class="cdm-close inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-slate-500 transition hover:bg-slate-100"
                aria-label="Tutup"
                onclick="closeCatatanModal()">
                ×
            </button>
        </div>

        <form
            id="catatanForm"
            action="{{ route('user.catatan-dinas.store') }}"
            method="POST">
            @csrf

            <input type="hidden" name="redirect_to" value="realisasi-ka">

            <div class="cdm-group mb-3">
                <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmTanggal">
                    Tanggal
                </label>
                <input
                    type="date"
                    class="cdm-input block h-9 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20 read-only:bg-slate-50 read-only:text-slate-500"
                    id="cdmTanggal"
                    name="tanggal"
                    value="{{ $tanggalCatatan }}"
                    readonly
                    required>
            </div>

            <div class="cdm-group mb-3">
                <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmPjl">
                    PJL
                </label>
                <input
                    type="text"
                    class="cdm-input block h-9 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20 read-only:bg-slate-50 read-only:text-slate-500"
                    id="cdmPjl"
                    value="{{ auth()->user()->name ?? '' }}"
                    readonly>
            </div>

            <div class="cdm-group mb-3">
                <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmShift">
                    Shift
                </label>
                <select
                    class="cdm-input cdm-select"
                    id="cdmShift"
                    name="shift_id"
                    required>
                    <option value="{{ $dinasAktif->shift_id }}" selected>
                        {{ $dinasAktif->shift?->nama_shift ?? '-' }}
                        — {{ $tanggalCatatan }}
                    </option>
                </select>
            </div>

            <div class="cdm-group mb-3">
                <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmCatatan">
                    Catatan
                </label>
                <textarea
                    class="cdm-textarea block min-h-[110px] w-full resize-y rounded-md border border-slate-200 bg-white px-3 py-2 text-sm leading-relaxed text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    id="cdmCatatan"
                    name="catatan"
                    maxlength="5000"
                    required
                    placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>

                <div class="cdm-help mt-1.5 text-xs text-slate-500" id="cdmCounter">
                    Maksimal 5.000 karakter.
                </div>
            </div>

            @if ($errors->any())
            <div class="cdm-error mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-relaxed text-red-900" role="alert">
                @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <div class="mt-4 flex flex-col-reverse justify-end gap-2 border-t border-slate-200 pt-3 sm:flex-row">
                <button
                    type="button"
                    class="cdm-btn cdm-cancel min-h-9 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    onclick="closeCatatanModal()">
                    Batal
                </button>

                <button
                    type="submit"
                    class="cdm-btn cdm-save min-h-9 rounded-md border border-[#1d2d50] bg-[#1d2d50] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#263e6a]">
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>
</div>

@endif