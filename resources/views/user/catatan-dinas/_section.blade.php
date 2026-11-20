{{-- resources/views/user/catatan-dinas/_section.blade.php --}}
{{-- Variabel: $catatan, $daftarDinas, $dinasAktif (opsional), $redirectTo (opsional) --}}

<div class="mx-auto w-full max-w-7xl px-3 py-4 text-sm leading-5 text-[#26344d] sm:px-4 sm:py-6 lg:px-6 lg:py-7">
    <section id="catatanDinasSection">
        {{-- HEADER --}}
        <div class="mb-5 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <div class="min-w-0">
                <h1 class="m-0 text-base font-bold leading-tight tracking-tight text-[#26344d] sm:text-xl">
                    Catatan Dinas
                </h1>
                <p class="mt-1 text-xs text-[#647084] sm:text-sm">
                    Kelola dan lihat catatan selama pelaksanaan dinas.
                </p>
            </div>

            <button
                type="button"
                onclick="openAddModal()"
                class="inline-flex min-h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#1e3156] px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#29436f] sm:px-4 sm:text-sm"
            >
                <span class="text-base leading-none" aria-hidden="true">+</span>
                <span>Tambah Catatan</span>
            </button>
        </div>

        {{-- NOTIFIKASI --}}
        @if (session('success'))
            <div
                class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-800 sm:text-sm"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        {{-- DAFTAR CATATAN --}}
        <div class="w-full overflow-hidden rounded-xl border border-[#e5e9f0] bg-white shadow-sm">
            <div class="space-y-3 p-3 sm:hidden">
                @forelse ($catatan as $item)
                    @php
                        $tanggalMentahCatatan = $item->dinas?->getRawOriginal('tanggal_dinas');
                        $tanggalCatatan = $tanggalMentahCatatan
                            ? \Carbon\Carbon::parse($tanggalMentahCatatan)->locale('id')->translatedFormat('d M Y')
                            : '-';
                        $namaPjl = $item->user?->name ?? $item->dinas?->user?->name ?? '-';
                        $namaShift = $item->dinas?->shift?->nama_shift ?? '-';
                    @endphp

                    <article class="min-w-0 rounded-lg border border-[#e5e9f0] bg-white p-3">
                        <div class="mb-2 flex items-center justify-between gap-2 border-b border-[#edf0f3] pb-2">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-[#7b8798]">
                                Catatan #{{ $loop->iteration }}
                            </span>

                            <button
                                type="button"
                                class="shrink-0 rounded-md border border-[#dbe6fb] bg-[#f5f8ff] px-3 py-1.5 text-[11px] font-semibold text-blue-600 transition hover:bg-[#e6efff]"
                                aria-label="Lihat detail catatan tanggal {{ $tanggalCatatan }}"
                                onclick="viewDetail(@js($tanggalCatatan), @js($namaPjl), @js($namaShift), @js($item->isi_catatan))"
                            >
                                Lihat detail
                            </button>
                        </div>

                        <dl class="space-y-2 text-xs">
                            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2">
                                <dt class="text-[#7b8798]">Tanggal</dt>
                                <dd class="m-0 min-w-0 break-words font-medium text-[#26344d]">
                                    {{ $tanggalCatatan }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2">
                                <dt class="text-[#7b8798]">PJL</dt>
                                <dd class="m-0 min-w-0 break-words text-[#3d4758]">
                                    {{ $namaPjl }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2">
                                <dt class="text-[#7b8798]">Shift</dt>
                                <dd class="m-0 min-w-0 break-words text-[#3d4758]">
                                    <span class="inline-block rounded-full bg-[#edf3fb] px-2 py-1 text-[10px] font-semibold text-[#1e3156]">
                                        {{ $namaShift }}
                                    </span>
                                </dd>
                            </div>

                            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2">
                                <dt class="text-[#7b8798]">Catatan</dt>
                                <dd class="m-0 min-w-0 whitespace-pre-wrap break-words text-[#5b6574]">{{ $item->isi_catatan }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <div class="px-3 py-8 text-center text-xs text-[#647084]">
                        <span class="mb-2 block text-2xl text-slate-400" aria-hidden="true">▤</span>
                        <p class="m-0 font-semibold text-[#26344d]">
                            {{ ($dinasAktif ?? null) ? 'Belum ada catatan dinas.' : 'Dinas belum dimulai.' }}
                        </p>
                        <p class="mb-0 mt-1">
                            {{ ($dinasAktif ?? null)
                                ? 'Klik tombol Tambah Catatan untuk membuat catatan baru.'
                                : 'Mulai dinas terlebih dahulu untuk melihat catatan pada JPL Anda.' }}
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- TABEL KHUSUS LAYAR BESAR --}}
            <div class="hidden w-full overflow-x-auto sm:block">
                <table class="w-full border-collapse text-xs lg:text-sm">
                    <thead class="bg-[#1e3156] text-white">
                        <tr>
                            <th class="w-14 px-3 py-3 text-center text-xs font-semibold" scope="col">No</th>
                            <th class="w-32 px-3 py-3 text-left text-xs font-semibold" scope="col">Tanggal</th>
                            <th class="w-44 px-3 py-3 text-left text-xs font-semibold" scope="col">PJL</th>
                            <th class="w-32 px-3 py-3 text-left text-xs font-semibold" scope="col">Shift</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold" scope="col">Catatan</th>
                            <th class="w-24 px-3 py-3 text-center text-xs font-semibold" scope="col">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="catatanTable">
                        @forelse ($catatan as $item)
                            @php
                                $tanggalMentahCatatan = $item->dinas?->getRawOriginal('tanggal_dinas');
                                $tanggalCatatan = $tanggalMentahCatatan
                                    ? \Carbon\Carbon::parse($tanggalMentahCatatan)->locale('id')->translatedFormat('d M Y')
                                    : '-';
                                $namaPjl = $item->user?->name ?? $item->dinas?->user?->name ?? '-';
                                $namaShift = $item->dinas?->shift?->nama_shift ?? '-';
                            @endphp

                            <tr class="border-b border-[#edf0f3] transition last:border-b-0 hover:bg-[#f8faff]">
                                <td class="px-3 py-3 text-center text-xs text-gray-700">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-xs font-medium text-[#26344d]">
                                    {{ $tanggalCatatan }}
                                </td>

                                <td class="break-words px-3 py-3 text-xs text-[#3d4758]">
                                    {{ $namaPjl }}
                                </td>

                                <td class="px-3 py-3 text-xs text-gray-700">
                                    <span class="inline-block rounded-full bg-[#edf3fb] px-2 py-1 text-[10px] font-semibold text-[#1e3156]">
                                        {{ $namaShift }}
                                    </span>
                                </td>

                                <td class="max-w-sm whitespace-pre-wrap break-words px-3 py-3 text-xs leading-relaxed text-[#5b6574]">
                                    {{ $item->isi_catatan }}
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-[#dbe6fb] bg-[#f5f8ff] px-3 py-1.5 text-[11px] font-semibold text-blue-600 transition hover:bg-[#e6efff]"
                                        aria-label="Lihat detail catatan tanggal {{ $tanggalCatatan }}"
                                        onclick="viewDetail(@js($tanggalCatatan), @js($namaPjl), @js($namaShift), @js($item->isi_catatan))"
                                    >
                                        Lihat
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-xs text-[#647084]">
                                    <span class="mb-2 block text-2xl text-slate-400" aria-hidden="true">▤</span>
                                    <p class="m-0 font-semibold text-[#26344d]">
                                        {{ ($dinasAktif ?? null) ? 'Belum ada catatan dinas.' : 'Dinas belum dimulai.' }}
                                    </p>
                                    <p class="mb-0 mt-1">
                                        {{ ($dinasAktif ?? null)
                                            ? 'Klik tombol Tambah Catatan untuk membuat catatan baru.'
                                            : 'Mulai dinas terlebih dahulu untuk melihat catatan pada JPL Anda.' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[#e5e9f0] bg-white px-3 py-3 text-xs text-[#647084] sm:px-5">
                Menampilkan {{ $catatan->count() }} catatan dinas
            </div>
        </div>
    </section>

    {{-- MODAL TAMBAH CATATAN --}}
    <div
        id="addModal"
        class="fixed inset-0 z-[1100] hidden items-center justify-center overflow-y-auto bg-slate-900/50 p-3 backdrop-blur-sm sm:p-5"
        onclick="closeOutside(event)"
        aria-hidden="true"
    >
        <div
            class="my-auto max-h-[calc(100dvh-1.5rem)] w-full max-w-[560px] overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white p-4 shadow-2xl sm:max-h-[calc(100vh-2.5rem)] sm:rounded-2xl sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="addModalTitle"
            onclick="event.stopPropagation()"
        >
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-[#e5e9f0] pb-3">
                <h2 id="addModalTitle" class="m-0 text-base font-bold text-[#26344d] sm:text-lg">
                    Tambah Catatan Dinas
                </h2>

                <button
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-[#647084] hover:bg-slate-100"
                    aria-label="Tutup modal"
                    onclick="closeAddModal()"
                >
                    ×
                </button>
            </div>

            <form id="catatanForm" action="{{ route('user.catatan-dinas.store') }}" method="POST">
                @csrf

                <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? '' }}">

                <div class="mb-4">
                    <label class="mb-1.5 block text-xs font-semibold text-[#344256]" for="catatanTanggal">
                        Tanggal
                    </label>
                    <input
                        type="date"
                        class="block h-10 w-full rounded-lg border border-[#d8e0e9] bg-white px-3 text-sm text-slate-700 outline-none focus:border-[#557da9] focus:ring-2 focus:ring-[#557da9]/15"
                        id="catatanTanggal"
                        name="tanggal"
                        value="{{ old('tanggal') }}"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="mb-1.5 block text-xs font-semibold text-[#344256]" for="catatanPjl">
                        PJL
                    </label>
                    <input
                        type="text"
                        class="block h-10 w-full rounded-lg border border-[#d8e0e9] bg-slate-50 px-3 text-sm text-slate-700 outline-none"
                        id="catatanPjl"
                        value="{{ auth()->user()->name ?? '' }}"
                        readonly
                    >
                </div>

                <div class="mb-4">
                    <label class="mb-1.5 block text-xs font-semibold text-[#344256]" for="catatanShift">
                        Shift
                    </label>
                    <select
                        class="block h-10 w-full rounded-lg border border-[#d8e0e9] bg-white px-3 text-sm text-slate-700 outline-none focus:border-[#557da9] focus:ring-2 focus:ring-[#557da9]/15"
                        id="catatanShift"
                        name="shift_id"
                        required
                    >
                        <option value="">Pilih tanggal terlebih dahulu</option>

                        @foreach ($daftarDinas as $dinas)
                            @php
                                $tanggalMentah = $dinas->getRawOriginal('tanggal_dinas');
                                $tanggalDinas = $tanggalMentah ? substr(trim((string) $tanggalMentah), 0, 10) : '';
                                $namaShiftOption = $dinas->shift?->nama_shift;
                            @endphp

                            @if ($dinas->shift && $tanggalDinas !== '')
                                <option
                                    value="{{ $dinas->shift_id }}"
                                    data-tanggal="{{ $tanggalDinas }}"
                                    @selected(
                                        (string) old('shift_id') === (string) $dinas->shift_id
                                        && old('tanggal') === $tanggalDinas
                                    )
                                >
                                    {{ $namaShiftOption }} — {{ $tanggalDinas }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <div id="shiftHelp" class="mt-1.5 text-xs leading-relaxed text-[#647084]" aria-live="polite">
                        Pilih tanggal dinas untuk menampilkan shift yang tersedia.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="mb-1.5 block text-xs font-semibold text-[#344256]" for="catatan">
                        Catatan
                    </label>
                    <textarea
                        class="block min-h-[110px] w-full resize-y rounded-lg border border-[#d8e0e9] bg-white px-3 py-2 text-sm leading-relaxed text-slate-700 outline-none focus:border-[#557da9] focus:ring-2 focus:ring-[#557da9]/15"
                        id="catatan"
                        name="catatan"
                        maxlength="5000"
                        required
                        placeholder="Tuliskan catatan selama pelaksanaan dinas..."
                    >{{ old('catatan') }}</textarea>

                    <div class="mt-1.5 text-xs text-[#647084]">
                        Maksimal 5.000 karakter.
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-5 flex flex-col-reverse justify-end gap-2 sm:flex-row">
                    <button
                        type="button"
                        onclick="closeAddModal()"
                        class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#d8dee6] bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        id="catatanSubmit"
                        class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#1e3156] bg-[#1e3156] px-4 text-xs font-semibold text-white hover:bg-[#29436f]"
                    >
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL CATATAN --}}
    <div
        id="detailModal"
        class="fixed inset-0 z-[1100] hidden items-center justify-center overflow-y-auto bg-slate-900/50 p-3 backdrop-blur-sm sm:p-5"
        onclick="closeDetailOutside(event)"
        aria-hidden="true"
    >
        <div
            class="my-auto max-h-[calc(100dvh-1.5rem)] w-full max-w-[560px] overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white p-4 shadow-2xl sm:max-h-[calc(100vh-2.5rem)] sm:rounded-2xl sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="detailModalTitle"
            onclick="event.stopPropagation()"
        >
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-[#e5e9f0] pb-3">
                <h2 id="detailModalTitle" class="m-0 text-base font-bold text-[#26344d] sm:text-lg">
                    Detail Catatan Dinas
                </h2>

                <button
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-[#647084] hover:bg-slate-100"
                    aria-label="Tutup modal"
                    onclick="closeDetailModal()"
                >
                    ×
                </button>
            </div>

            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2 border-b border-[#edf0f3] py-3 sm:grid-cols-[90px_minmax(0,1fr)]">
                <div class="text-xs font-semibold text-[#647084]">Tanggal</div>
                <div id="detailTanggal" class="min-w-0 break-words text-xs leading-relaxed text-gray-700 sm:text-sm"></div>
            </div>

            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2 border-b border-[#edf0f3] py-3 sm:grid-cols-[90px_minmax(0,1fr)]">
                <div class="text-xs font-semibold text-[#647084]">PJL</div>
                <div id="detailPjl" class="min-w-0 break-words text-xs leading-relaxed text-gray-700 sm:text-sm"></div>
            </div>

            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2 border-b border-[#edf0f3] py-3 sm:grid-cols-[90px_minmax(0,1fr)]">
                <div class="text-xs font-semibold text-[#647084]">Shift</div>
                <div id="detailShift" class="min-w-0 break-words text-xs leading-relaxed text-gray-700 sm:text-sm"></div>
            </div>

            <div class="grid grid-cols-[68px_minmax(0,1fr)] gap-2 border-b border-[#edf0f3] py-3 sm:grid-cols-[90px_minmax(0,1fr)]">
                <div class="text-xs font-semibold text-[#647084]">Catatan</div>
                <div id="detailCatatan" class="min-w-0 whitespace-pre-wrap break-words text-xs leading-relaxed text-gray-700 sm:text-sm"></div>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    type="button"
                    onclick="closeDetailModal()"
                    class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#d8dee6] bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>